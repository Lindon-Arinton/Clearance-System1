<?php

namespace App\Controllers;

use App\Libraries\Auth as AuthLib;

class Auth extends BaseController
{
    /**
     * Sign-in and sign-up live on one page; the URL only decides which panel shows first.
     */
    public function login(): string
    {
        return $this->portal('login');
    }

    private function portal(string $mode): string
    {
        return view('auth/portal', [
            'mode'     => $mode,
            'title'    => $mode === 'signup' ? 'Create account' : 'Sign in',
            'programs' => model(\App\Models\ProgramModel::class)->where('is_active', 1)->orderBy('code')->findAll(),
        ]);
    }

    public function attempt()
    {
        $rules = [
            'login_id' => 'required|max_length[120]',
            'password' => 'required|max_length[128]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->to(site_url('login'))->withInput()->with('login_error', 'Enter your student ID number or personal email, and your password.');
        }

        // Per-IP throttle on top of the per-account lockout.
        $throttler = service('throttler');
        if ($throttler->check(md5('login-' . $this->request->getIPAddress()), 20, MINUTE) === false) {
            return redirect()->to(site_url('login'))->withInput()->with('login_error', 'Too many sign-in attempts from this device. Please wait a minute and try again.');
        }

        // The account itself decides the role — no role picker on the form.
        $result = $this->auth->attempt(trim((string) $this->request->getPost('login_id')), (string) $this->request->getPost('password'));

        if (! $result['ok']) {
            return redirect()->to(site_url('login'))->withInput()->with('login_error', $result['error']);
        }

        $this->auth->login($result['user'], $this->postBool('remember'));
        $role = $result['user']['role'];

        $intended = session('intended_url');
        session()->remove('intended_url');
        $home = site_url(AuthLib::homeFor($role));
        $rolePrefix = site_url($role . '/');
        $target = is_string($intended) && (str_starts_with($intended, $rolePrefix) || str_starts_with($intended, site_url('notifications')) || str_starts_with($intended, site_url('profile')))
            ? $intended : $home;

        return redirect()->to($target)->withCookies()->with('toast_success', 'Welcome back, ' . $result['user']['first_name'] . '.');
    }

    public function logout()
    {
        $this->auth->logout();

        return redirect()->to(site_url('login'))->withCookies()->with('login_info', 'You have been signed out.');
    }

    public function signup(): string
    {
        return $this->portal('signup');
    }

    public function register()
    {
        $rules = [
            'student_number'   => 'required|regex_match[/^\d{3,10}$/]|is_unique[students.student_number]|is_unique[users.username]',
            'first_name'       => 'required|max_length[80]',
            'middle_name'      => 'permit_empty|max_length[80]',
            'last_name'        => 'required|max_length[80]',
            'email'            => 'required|valid_email|max_length[120]|is_unique[users.email]',
            'program_id'       => 'required|is_not_unique[programs.id]',
            'year_level'       => 'required|integer|greater_than[0]|less_than[7]',
            'section'          => 'permit_empty|max_length[20]',
            'contact_number'   => 'permit_empty|max_length[30]',
            'password'         => 'required|min_length[8]|max_length[72]|regex_match[/(?=.*[A-Za-z])(?=.*\d)/]',
            'confirm_password' => 'required|matches[password]',
            'agree'            => 'required',
        ];
        $messages = [
            'student_number'   => ['regex_match' => 'Enter your student ID number using digits only (e.g. 9785).', 'is_unique' => self::NUMBER_TAKEN],
            'email'            => ['is_unique' => self::EMAIL_TAKEN],
            'password'         => ['regex_match' => 'Your password must contain at least one letter and one number.'],
            'confirm_password' => ['matches' => 'The password confirmation does not match.'],
            'agree'            => ['required' => 'Please confirm that your details are correct.'],
        ];

        $labels = [
            'student_number' => 'Student ID number', 'first_name' => 'First name', 'middle_name' => 'Middle name', 'last_name' => 'Last name',
            'email' => 'Personal email', 'program_id' => 'Program', 'year_level' => 'Year level', 'section' => 'Section',
            'contact_number' => 'Contact number', 'password' => 'Password', 'confirm_password' => 'Password confirmation', 'agree' => 'Confirmation',
        ];
        $messages['program_id'] = ['is_not_unique' => 'Choose a program from the list.'];
        foreach ($rules as $field => $rule) {
            $rules[$field] = ['label' => $labels[$field], 'rules' => $rule];
        }

        $data          = $this->request->getPost(array_keys($rules));
        $data['email'] = strtolower(trim((string) $data['email']));
        if (! $this->validateData($data, $rules, $messages)) {
            return redirect()->to(site_url('signup'))->withInput()->with('errors', $this->validator->getErrors());
        }

        if ((new \App\Services\AccountService())->studentNameTaken($data['first_name'], $data['middle_name'], $data['last_name'])) {
            return redirect()->to(site_url('signup'))->withInput()->with('errors', [
                'first_name' => self::NAME_TAKEN,
                'last_name'  => '',
            ]);
        }

        // Limit automated sign-ups from a single device.
        if (service('throttler')->check(md5('signup-' . $this->request->getIPAddress()), 5, HOUR) === false) {
            return redirect()->to(site_url('signup'))->withInput()->with('errors', ['Too many sign-ups from this device. Please try again later.']);
        }

        (new \App\Services\AccountService())->registerStudent($data);

        return redirect()->to(site_url('login'))->with('login_info', 'Account created! The registrar will verify your details. You can sign in with your student ID number or email once it is approved.');
    }

    public const NAME_TAKEN   = 'An account with this name already exists. If this is you, sign in instead. If you are a different person with the same name, please contact the registrar.';
    public const EMAIL_TAKEN  = 'This email is already registered. Sign in instead, or use a different email.';
    public const NUMBER_TAKEN = 'This student ID number already has an account. Sign in instead, or contact the registrar if this is a mistake.';

    /**
     * Live duplicate check for the sign-up form (JSON). Only answers "taken or not"
     * for the values sent, and is rate-limited per device.
     */
    public function checkAvailability()
    {
        if (service('throttler')->check(md5('signup-check-' . $this->request->getIPAddress()), 60, MINUTE) === false) {
            return $this->response->setStatusCode(429)->setJSON(['error' => 'Too many checks. Please slow down.']);
        }

        $out     = [];
        $email   = strtolower(trim((string) $this->request->getGet('email')));
        $number  = trim((string) $this->request->getGet('student_number'));
        $first   = (string) $this->request->getGet('first_name');
        $last    = (string) $this->request->getGet('last_name');
        $middle  = (string) $this->request->getGet('middle_name');
        $users   = model(\App\Models\UserModel::class);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $out['email'] = $users->where('email', $email)->countAllResults() > 0 ? self::EMAIL_TAKEN : null;
        }
        if ($number !== '' && preg_match('/^\d{3,10}$/', $number)) {
            $taken = model(\App\Models\StudentModel::class)->where('student_number', $number)->countAllResults() > 0
                || $users->where('username', $number)->countAllResults() > 0;
            $out['student_number'] = $taken ? self::NUMBER_TAKEN : null;
        }
        if (trim($first) !== '' && trim($last) !== '') {
            $out['name'] = (new \App\Services\AccountService())->studentNameTaken($first, $middle, $last) ? self::NAME_TAKEN : null;
        }

        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON($out);
    }

    public function forgot(): string
    {
        return view('auth/forgot');
    }
}
