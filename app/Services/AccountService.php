<?php

namespace App\Services;

use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\UserModel;

/**
 * Account management for admins. New and reset accounts get a temporary
 * password that must be changed at first sign-in.
 */
class AccountService
{
    public static function temporaryPassword(): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyz';

        return 'Nc' . $letters[random_int(0, 22)] . $letters[random_int(0, 22)] . '-' . random_int(1000, 9999) . '-' . $letters[random_int(0, 22)] . random_int(10, 99);
    }

    private function userFields(array $data): array
    {
        return [
            'first_name'  => trim($data['first_name']),
            'middle_name' => trim((string) ($data['middle_name'] ?? '')) ?: null,
            'last_name'   => trim($data['last_name']),
            'email'       => strtolower(trim((string) ($data['email'] ?? ''))) ?: null,
        ];
    }

    /**
     * True when a student account already exists with the same name.
     * First and last names must match (case-insensitive); middle names only
     * tell people apart when both have one and the initials differ.
     */
    public function studentNameTaken(string $first, ?string $middle, string $last): bool
    {
        $clean = static fn (?string $s): string => preg_replace('/\s+/u', ' ', trim((string) $s));
        $first = $clean($first);
        $last  = $clean($last);
        if ($first === '' || $last === '') {
            return false;
        }
        $initial = static fn (?string $m): string => mb_strtolower(mb_substr(ltrim($clean($m), '. '), 0, 1));
        $mine    = $initial($middle);

        // The column collation is case-insensitive, so plain equality matches "juan" and "Juan".
        $rows = model(UserModel::class)->select('middle_name')
            ->where('role', 'student')->where('first_name', $first)->where('last_name', $last)
            ->findAll();

        foreach ($rows as $row) {
            $theirs = $initial($row['middle_name']);
            if ($mine === '' || $theirs === '' || $mine === $theirs) {
                return true;
            }
        }

        return false;
    }

    /**
     * Student self-registration. The account stays inactive until the registrar approves it.
     */
    public function registerStudent(array $data): int
    {
        $db = db_connect();
        $db->transException(true)->transStart();

        $email  = strtolower(trim((string) $data['email']));
        $number = trim((string) $data['student_number']);
        $userId = model(UserModel::class)->insert($this->userFields($data) + [
            'role' => 'student', 'username' => $number, 'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'is_active' => 0, 'approval_status' => 'pending', 'must_change_password' => 0,
        ]);
        $id = model(StudentModel::class)->insert([
            'user_id' => $userId, 'student_number' => $number, 'program_id' => (int) $data['program_id'],
            'year_level' => (int) $data['year_level'], 'section' => trim((string) ($data['section'] ?? '')) ?: null,
            'contact_number' => trim((string) ($data['contact_number'] ?? '')) ?: null, 'status' => 'active',
        ]);
        AuditLogger::log('student.registered', 'student', (int) $id, null, ['student_number' => $number, 'email' => $email], 'Student self-registered (pending approval)', (int) $userId, 'student');
        Notifier::notifyAdmins('signup_pending', 'New student sign-up to approve',
            trim($data['first_name'] . ' ' . $data['last_name']) . " (ID {$number}, {$email}) created an account and is waiting for approval.", 'admin/students/' . $id);

        $db->transComplete();

        return (int) $id;
    }

    /**
     * Approve a self-registered student, confirming (or correcting) their student ID number.
     */
    public function approveStudent(array $student, string $studentNumber): void
    {
        if ($student['approval_status'] !== 'pending') {
            throw new WorkflowException('This account is not waiting for approval.');
        }
        $studentNumber = trim($studentNumber);
        if (! preg_match('/^\d{3,10}$/', $studentNumber)) {
            throw new WorkflowException('Enter the student ID number using digits only (e.g. 9785).');
        }
        $taken = model(StudentModel::class)->where('student_number', $studentNumber)->where('id !=', $student['id'])->countAllResults()
            + model(UserModel::class)->where('username', $studentNumber)->where('id !=', $student['user_id'])->countAllResults();
        if ($taken > 0) {
            throw new WorkflowException("Student ID number {$studentNumber} is already used by another account.");
        }

        $db = db_connect();
        $db->transException(true)->transStart();
        model(StudentModel::class)->update($student['id'], ['student_number' => $studentNumber]);
        model(UserModel::class)->update($student['user_id'], ['username' => $studentNumber, 'approval_status' => 'approved', 'is_active' => 1]);
        AuditLogger::log('student.approved', 'student', (int) $student['id'], ['approval_status' => 'pending'], ['approval_status' => 'approved', 'student_number' => $studentNumber], "Self-registered account approved as {$studentNumber}");
        Notifier::notify((int) $student['user_id'], 'signup_approved', 'Welcome! Your account is approved',
            "The registrar verified your details (student ID number {$studentNumber}). You can now start your clearance.", 'student/dashboard');
        $db->transComplete();
    }

    /**
     * Rejecting deletes the pending account so the student number and email can be registered correctly.
     */
    public function rejectStudent(array $student, string $reason): void
    {
        if ($student['approval_status'] !== 'pending') {
            throw new WorkflowException('Only pending sign-ups can be rejected.');
        }
        $reason = trim(strip_tags($reason));
        if (mb_strlen($reason) < 5) {
            throw new WorkflowException('Give a short reason for rejecting this sign-up.');
        }

        $db = db_connect();
        $db->transException(true)->transStart();
        AuditLogger::log('student.rejected', 'student', (int) $student['id'],
            ['student_number' => $student['student_number'], 'email' => $student['email'], 'name' => person_name($student)], ['reason' => $reason], 'Self-registration rejected and removed');
        model(StudentModel::class)->delete($student['id']);
        model(UserModel::class)->delete($student['user_id']);
        $db->transComplete();
    }

    /**
     * @return array{id: int, password: string}
     */
    public function createStudent(array $data): array
    {
        $password = self::temporaryPassword();
        $db       = db_connect();
        $db->transException(true)->transStart();

        $userId = model(UserModel::class)->insert($this->userFields($data) + [
            'role' => 'student', 'username' => trim($data['student_number']), 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1, 'must_change_password' => 1,
        ]);
        $id = model(StudentModel::class)->insert([
            'user_id' => $userId, 'student_number' => trim($data['student_number']), 'program_id' => (int) $data['program_id'],
            'year_level' => (int) $data['year_level'], 'section' => trim((string) ($data['section'] ?? '')) ?: null,
            'contact_number' => trim((string) ($data['contact_number'] ?? '')) ?: null, 'status' => 'active',
        ]);
        AuditLogger::log('student.created', 'student', (int) $id, null, ['student_number' => $data['student_number']], 'Student account created');

        $db->transComplete();

        return ['id' => (int) $id, 'password' => $password];
    }

    public function updateStudent(array $student, array $data): void
    {
        $db = db_connect();
        $db->transException(true)->transStart();

        $userUpdate = $this->userFields($data) + ['username' => trim($data['student_number']), 'is_active' => $data['status'] === 'inactive' ? 0 : 1];
        model(UserModel::class)->update($student['user_id'], $userUpdate);
        $update = [
            'student_number' => trim($data['student_number']), 'program_id' => (int) $data['program_id'], 'year_level' => (int) $data['year_level'],
            'section' => trim((string) ($data['section'] ?? '')) ?: null, 'contact_number' => trim((string) ($data['contact_number'] ?? '')) ?: null,
            'status' => $data['status'],
        ];
        model(StudentModel::class)->update($student['id'], $update);
        AuditLogger::log('student.updated', 'student', (int) $student['id'],
            array_intersect_key($student, $update + ['first_name' => 1, 'last_name' => 1, 'email' => 1]), $update + $this->userFields($data), 'Student record updated');

        $db->transComplete();
    }

    /**
     * @return array{id: int, password: string}
     */
    public function createTeacher(array $data): array
    {
        $password = self::temporaryPassword();
        $db       = db_connect();
        $db->transException(true)->transStart();

        $userId = model(UserModel::class)->insert($this->userFields($data) + [
            'role' => 'teacher', 'username' => trim($data['employee_number']), 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1, 'must_change_password' => 1,
        ]);
        $id = model(TeacherModel::class)->insert([
            'user_id' => $userId, 'employee_number' => trim($data['employee_number']), 'title' => trim($data['title']) ?: 'Prof.',
            'department' => trim((string) ($data['department'] ?? '')) ?: null,
        ]);
        AuditLogger::log('teacher.created', 'teacher', (int) $id, null, ['employee_number' => $data['employee_number']], 'Teacher account created');

        $db->transComplete();

        return ['id' => (int) $id, 'password' => $password];
    }

    public function updateTeacher(array $teacher, array $data): void
    {
        $db = db_connect();
        $db->transException(true)->transStart();

        model(UserModel::class)->update($teacher['user_id'], $this->userFields($data) + [
            'username' => trim($data['employee_number']), 'is_active' => ! empty($data['is_active']) ? 1 : 0,
        ]);
        $update = ['employee_number' => trim($data['employee_number']), 'title' => trim($data['title']) ?: 'Prof.', 'department' => trim((string) ($data['department'] ?? '')) ?: null];
        model(TeacherModel::class)->update($teacher['id'], $update);
        AuditLogger::log('teacher.updated', 'teacher', (int) $teacher['id'], array_intersect_key($teacher, $update), $update, 'Teacher record updated');

        $db->transComplete();
    }

    /**
     * @return string the new temporary password
     */
    public function createAdmin(array $data): string
    {
        $password = self::temporaryPassword();
        $id       = model(UserModel::class)->insert($this->userFields($data) + [
            'role' => 'admin', 'username' => trim($data['username']), 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => 1, 'must_change_password' => 1,
        ]);
        AuditLogger::log('admin.created', 'user', (int) $id, null, ['username' => $data['username']], 'Administrator account created');

        return $password;
    }

    public function resetPassword(int $userId): string
    {
        $password = self::temporaryPassword();
        model(UserModel::class)->update($userId, [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'must_change_password' => 1, 'failed_logins' => 0, 'locked_until' => null,
        ]);
        db_connect()->table('auth_tokens')->where('user_id', $userId)->delete();
        AuditLogger::log('auth.password_reset', 'user', $userId, null, null, 'Password reset by administrator');

        return $password;
    }
}
