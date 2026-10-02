<?php

namespace App\Libraries;

use App\Models\AuthTokenModel;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Services\AuditLogger;

/**
 * Session-based authentication with role awareness, login throttling
 * and an optional "keep me signed in" token.
 */
class Auth
{
    public const REMEMBER_COOKIE = 'ncr_remember';
    public const REMEMBER_DAYS   = 30;
    public const MAX_ATTEMPTS    = 5;
    public const LOCK_MINUTES    = 15;

    private ?array $user        = null;
    private bool $loaded        = false;
    private ?array $profile     = null;
    private bool $profileLoaded = false;

    public function user(): ?array
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $id           = session('auth_user_id');
            if ($id) {
                $user = model(UserModel::class)->find((int) $id);
                // A deactivated account loses access immediately, even mid-session.
                $this->user = ($user && (int) $user['is_active'] === 1) ? $user : null;
            }
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user() ? (int) $this->user()['id'] : null;
    }

    public function role(): ?string
    {
        return $this->user()['role'] ?? null;
    }

    /**
     * The student or teacher row linked to the signed-in user.
     */
    public function profile(): ?array
    {
        if (! $this->profileLoaded) {
            $this->profileLoaded = true;
            $user                = $this->user();
            if ($user && $user['role'] === 'student') {
                $this->profile = model(StudentModel::class)->findDetailedByUser((int) $user['id']);
            } elseif ($user && $user['role'] === 'teacher') {
                $this->profile = model(TeacherModel::class)->where('user_id', $user['id'])->first();
            }
        }

        return $this->profile;
    }

    public function studentId(): ?int
    {
        return $this->role() === 'student' ? (int) ($this->profile()['id'] ?? 0) : null;
    }

    public function teacherId(): ?int
    {
        return $this->role() === 'teacher' ? (int) ($this->profile()['id'] ?? 0) : null;
    }

    /**
     * @return array{ok: bool, error?: string, user?: array}
     */
    public function attempt(string $identifier, string $password): array
    {
        $users = model(UserModel::class);
        // Sign in with a school ID (student ID number, employee ID, admin username) or personal email.
        // IDs never contain "@", so the two lookups cannot collide.
        $identifier = trim($identifier);
        $user       = str_contains($identifier, '@')
            ? $users->where('email', strtolower($identifier))->first()
            : $users->where('username', $identifier)->first();
        $generic = 'The ID number, email or password you entered is incorrect.';

        if (! $user) {
            // Spend comparable time to avoid leaking which IDs exist.
            password_hash($password, PASSWORD_DEFAULT);

            return ['ok' => false, 'error' => $generic];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $mins = (int) ceil((strtotime($user['locked_until']) - time()) / 60);

            return ['ok' => false, 'error' => "Too many failed attempts. Try again in {$mins} minute(s)."];
        }

        if (! password_verify($password, $user['password_hash'])) {
            $failed = (int) $user['failed_logins'] + 1;
            $update = ['failed_logins' => $failed];
            if ($failed >= self::MAX_ATTEMPTS) {
                $update['locked_until']  = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
                $update['failed_logins'] = 0;
                AuditLogger::log('auth.locked', 'user', (int) $user['id'], null, null, 'Account locked after repeated failed sign-ins', (int) $user['id'], $user['role']);
            }
            $users->update($user['id'], $update);

            return ['ok' => false, 'error' => $generic];
        }

        if ($user['approval_status'] === 'pending') {
            return ['ok' => false, 'error' => 'Your account is waiting for registrar approval. You can sign in once it is approved.'];
        }

        if ((int) $user['is_active'] !== 1) {
            return ['ok' => false, 'error' => 'This account is inactive. Please contact the registrar.'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->update($user['id'], ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
        }

        return ['ok' => true, 'user' => $user];
    }

    public function login(array $user, bool $remember = false): void
    {
        $session = session();
        $session->regenerate(true); // prevent session fixation
        $session->set('auth_user_id', (int) $user['id']);
        $session->set('auth_role', $user['role']);

        model(UserModel::class)->update($user['id'], [
            'failed_logins' => 0,
            'locked_until'  => null,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $this->user          = null;
        $this->loaded        = false;
        $this->profileLoaded = false;

        if ($remember) {
            $this->issueRememberToken((int) $user['id']);
        }

        AuditLogger::log('auth.login', 'user', (int) $user['id'], null, null, 'Signed in', (int) $user['id'], $user['role']);
    }

    public function logout(): void
    {
        $cookie = get_cookie(self::REMEMBER_COOKIE);
        if ($cookie && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            model(AuthTokenModel::class)->where('selector', $selector)->delete();
        }
        delete_cookie(self::REMEMBER_COOKIE);

        if ($this->id()) {
            AuditLogger::log('auth.logout', 'user', $this->id(), null, null, 'Signed out');
        }

        session()->destroy();
        $this->user   = null;
        $this->loaded = true;
    }

    /**
     * Restore a session from a valid remember-me cookie.
     */
    public function loginFromRememberCookie(): bool
    {
        $cookie = get_cookie(self::REMEMBER_COOKIE);
        if (! $cookie || ! str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        $tokens = model(AuthTokenModel::class);
        $token  = $tokens->where('selector', $selector)->first();

        if (! $token || strtotime($token['expires_at']) < time()
            || ! hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            if ($token) {
                $tokens->delete($token['id']);
            }
            delete_cookie(self::REMEMBER_COOKIE);

            return false;
        }

        $user = model(UserModel::class)->find($token['user_id']);
        if (! $user || (int) $user['is_active'] !== 1) {
            return false;
        }

        // Rotate the token on every use.
        $tokens->delete($token['id']);
        $this->login($user, true);

        return true;
    }

    private function issueRememberToken(int $userId): void
    {
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));

        model(AuthTokenModel::class)->insert([
            'user_id'        => $userId,
            'selector'       => $selector,
            'validator_hash' => hash('sha256', $validator),
            'expires_at'     => date('Y-m-d H:i:s', time() + self::REMEMBER_DAYS * 86400),
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        set_cookie([
            'name'     => self::REMEMBER_COOKIE,
            'value'    => $selector . ':' . $validator,
            'expire'   => self::REMEMBER_DAYS * 86400,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function homeFor(?string $role): string
    {
        return match ($role) {
            'student' => 'student/dashboard',
            'teacher' => 'teacher/dashboard',
            'admin'   => 'admin/dashboard',
            default   => 'login',
        };
    }
}
