<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Services\AccountService;

class Teachers extends BaseController
{
    private function rules(?int $userId = null, ?int $teacherId = null): array
    {
        return [
            'employee_number' => 'required|regex_match[/^[A-Za-z0-9\-]{3,20}$/]|is_unique[users.username,id,' . ($userId ?? 0) . ']|is_unique[teachers.employee_number,id,' . ($teacherId ?? 0) . ']',
            'title'           => 'required|in_list[Prof.,Dr.,Mr.,Ms.,Mrs.,Engr.]',
            'first_name'      => 'required|max_length[80]',
            'middle_name'     => 'permit_empty|max_length[80]',
            'last_name'       => 'required|max_length[80]',
            'email'           => 'required|valid_email|max_length[120]|is_unique[users.email,id,' . ($userId ?? 0) . ']',
            'department'      => 'permit_empty|max_length[120]',
        ];
    }

    public function index(): string
    {
        $q     = trim((string) $this->request->getGet('q'));
        $model = model(TeacherModel::class)->detailed()
            ->select("(SELECT COUNT(*) FROM subject_offerings so JOIN school_terms st ON st.id = so.school_term_id WHERE so.teacher_id = teachers.id AND st.is_current = 1) AS current_subjects,
                (SELECT COUNT(*) FROM clearance_subjects cs JOIN clearances c ON c.id = cs.clearance_id WHERE cs.teacher_id = teachers.id AND c.status = 'in_progress' AND (cs.status = 'PENDING' OR (cs.status = 'PASSED' AND cs.signed_at IS NULL))) AS awaiting");
        if ($q !== '') {
            $model->groupStart()->like('users.first_name', $q)->orLike('users.last_name', $q)->orLike('teachers.employee_number', $q)->orLike('teachers.department', $q)->groupEnd();
        }

        return $this->page('admin/teachers/index', [
            'title'    => 'Teachers',
            'subtitle' => 'Faculty accounts and their subject load.',
            'teachers' => $model->orderBy('users.last_name')->paginate(15),
            'pager'    => model(TeacherModel::class)->pager,
            'q'        => $q,
        ]);
    }

    public function create(): string
    {
        return $this->page('admin/teachers/form', ['title' => 'New teacher', 'subtitle' => 'Create a faculty account with a temporary password.', 'teacher' => null]);
    }

    public function store()
    {
        if (! $this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $result = (new AccountService())->createTeacher($this->request->getPost());

        return redirect()->to(site_url('admin/teachers/' . $result['id']))->with('toast_success', 'Teacher account created.')->with('temp_password', $result['password']);
    }

    public function show(int $id): string
    {
        $teacher = model(TeacherModel::class)->findDetailed($id) ?? $this->notFound();
        $offerings = db_connect()->table('subject_offerings so')
            ->select("so.*, s.code, s.title, st.semester, st.is_current, sy.name AS school_year,
                (SELECT COUNT(*) FROM enrollments e WHERE e.subject_offering_id = so.id AND e.status = 'enrolled') AS students,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PASSED' AND cs.signed_at IS NOT NULL) AS passed,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'INC') AS inc,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'FAILED') AS failed,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PENDING') AS pending")
            ->join('subjects s', 's.id = so.subject_id')->join('school_terms st', 'st.id = so.school_term_id')->join('school_years sy', 'sy.id = st.school_year_id')
            ->where('so.teacher_id', $id)->orderBy('st.start_date', 'DESC')->orderBy('s.code')->get()->getResultArray();

        return $this->page('admin/teachers/show', [
            'title' => $teacher['display_name'], 'eyebrow' => 'Teacher record', 'subtitle' => $teacher['employee_number'] . ' · ' . ($teacher['department'] ?? ''),
            'teacher' => $teacher, 'offerings' => $offerings,
        ]);
    }

    public function edit(int $id): string
    {
        $teacher = model(TeacherModel::class)->findDetailed($id) ?? $this->notFound();

        return $this->page('admin/teachers/form', ['title' => 'Edit teacher', 'subtitle' => $teacher['display_name'], 'teacher' => $teacher]);
    }

    public function update(int $id)
    {
        $teacher = model(TeacherModel::class)->findDetailed($id) ?? $this->notFound();
        if (! $this->validate($this->rules((int) $teacher['user_id'], $id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new AccountService())->updateTeacher($teacher, $this->request->getPost());

        return redirect()->to(site_url('admin/teachers/' . $id))->with('toast_success', 'Teacher record updated.');
    }

    public function resetPassword(int $id)
    {
        $teacher  = model(TeacherModel::class)->find($id) ?? $this->notFound();
        $password = (new AccountService())->resetPassword((int) $teacher['user_id']);

        return redirect()->to(site_url('admin/teachers/' . $id))->with('toast_success', 'Password reset.')->with('temp_password', $password);
    }
}
