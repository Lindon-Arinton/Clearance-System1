<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProgramModel;
use App\Models\SettingModel;
use App\Models\UserModel;
use App\Services\AccountService;
use App\Services\AuditLogger;

class Settings extends BaseController
{
    public function index(): string
    {
        return $this->page('admin/settings', [
            'title'    => 'Settings',
            'subtitle' => 'School profile, academic programs and administrator accounts.',
            'settings' => model(SettingModel::class)->allSettings(),
            'programs' => model(ProgramModel::class)->select('programs.*, (SELECT COUNT(*) FROM students s WHERE s.program_id = programs.id) AS student_count')->orderBy('code')->findAll(),
            'admins'   => model(UserModel::class)->where('role', 'admin')->orderBy('last_name')->findAll(),
        ]);
    }

    public function update()
    {
        $rules = [
            'school_name'       => 'required|max_length[120]',
            'school_short_name' => 'required|max_length[40]',
            'office_name'       => 'required|max_length[80]',
            'school_address'    => 'permit_empty|max_length[200]',
            'registrar_name'    => 'permit_empty|max_length[120]',
            'max_upload_mb'     => 'required|integer|greater_than[0]|less_than_equal_to[20]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $model = model(SettingModel::class);
        $old   = $model->allSettings();
        foreach (array_keys($rules) as $key) {
            $model->put($key, trim((string) $this->request->getPost($key)));
        }
        AuditLogger::log('settings.updated', 'settings', null, array_intersect_key($old, $rules), $this->request->getPost(array_keys($rules)), 'School settings updated');

        return redirect()->back()->with('toast_success', 'Settings saved.');
    }

    public function storeProgram()
    {
        $rules = ['code' => 'required|alpha_numeric|max_length[20]|is_unique[programs.code]', 'name' => 'required|max_length[150]', 'department' => 'permit_empty|max_length[120]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $id = model(ProgramModel::class)->insert($this->request->getPost(array_keys($rules)) + ['is_active' => 1]);
        AuditLogger::log('program.created', 'program', (int) $id, null, $this->request->getPost(array_keys($rules)), 'Program created');

        return redirect()->back()->with('toast_success', 'Program added.');
    }

    public function updateProgram(int $id)
    {
        $program = model(ProgramModel::class)->find($id) ?? $this->notFound();
        $rules   = ['code' => "required|alpha_numeric|max_length[20]|is_unique[programs.code,id,{$id}]", 'name' => 'required|max_length[150]', 'department' => 'permit_empty|max_length[120]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($rules)) + ['is_active' => $this->postBool('is_active') ? 1 : 0];
        model(ProgramModel::class)->update($id, $data);
        AuditLogger::log('program.updated', 'program', $id, $program, $data, 'Program updated');

        return redirect()->back()->with('toast_success', 'Program updated.');
    }

    public function storeAdmin()
    {
        $rules = [
            'username'   => 'required|alpha_dash|min_length[4]|max_length[50]|is_unique[users.username]',
            'first_name' => 'required|max_length[80]',
            'last_name'  => 'required|max_length[80]',
            'email'      => 'required|valid_email|is_unique[users.email]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $password = (new AccountService())->createAdmin($this->request->getPost(array_keys($rules)));

        return redirect()->back()->with('toast_success', 'Administrator account created.')
            ->with('temp_password', $password)->with('temp_password_for', (string) $this->request->getPost('username'));
    }
}
