<?php

namespace App\Controllers;

use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Services\AuditLogger;
use App\Services\FileStorage;
use App\Services\WorkflowException;

class Profile extends BaseController
{
    public function index(): string
    {
        return $this->page('shared/profile', [
            'title'    => 'Profile',
            'eyebrow'  => 'Account',
            'subtitle' => 'Your details, password and security.',
            'mustChange' => (int) $this->auth->user()['must_change_password'] === 1,
        ]);
    }

    public function password()
    {
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]|max_length[72]|regex_match[/(?=.*[A-Za-z])(?=.*\d)/]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        $messages = [
            'new_password'     => ['regex_match' => 'The new password must contain at least one letter and one number.'],
            'confirm_password' => ['matches' => 'The password confirmation does not match.'],
        ];
        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $user = $this->auth->user();
        if (! password_verify((string) $this->request->getPost('current_password'), $user['password_hash'])) {
            return redirect()->back()->with('toast_error', 'Your current password is incorrect.');
        }
        if (password_verify((string) $this->request->getPost('new_password'), $user['password_hash'])) {
            return redirect()->back()->with('toast_error', 'Choose a password different from your current one.');
        }

        model(UserModel::class)->update($user['id'], [
            'password_hash'        => password_hash((string) $this->request->getPost('new_password'), PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);
        session()->regenerate(true);
        AuditLogger::log('auth.password_changed', 'user', (int) $user['id'], null, null, 'Password changed');

        return redirect()->to(site_url('profile'))->with('toast_success', 'Your password has been updated.');
    }

    public function signature()
    {
        $teacher = $this->auth->profile();

        try {
            $meta = FileStorage::store($this->request->getFile('signature'), 'signatures', FileStorage::SIGNATURE_TYPES, 2 * 1024 * 1024);
        } catch (WorkflowException $e) {
            return redirect()->back()->with('toast_error', $e->getMessage());
        }

        // The previous image is kept on disk: clearances already signed keep showing the signature used at the time.
        $firstTime = empty($teacher['signature_path']);
        model(TeacherModel::class)->update($teacher['id'], ['signature_path' => $meta['file_path']]);
        AuditLogger::log('teacher.signature_uploaded', 'teacher', (int) $teacher['id'], ['signature_path' => $teacher['signature_path']], ['signature_path' => $meta['file_path'], 'file_hash' => $meta['file_hash']], $firstTime ? 'E-signature uploaded' : 'E-signature replaced');

        return $firstTime
            ? redirect()->to(site_url('teacher/dashboard'))->with('toast_success', 'E-signature saved. It will be added automatically to every student you mark PASSED.')
            : redirect()->back()->with('toast_success', 'E-signature updated. New approvals will use this signature; earlier approvals keep the original.');
    }
}
