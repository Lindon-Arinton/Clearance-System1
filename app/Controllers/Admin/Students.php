<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClearanceModel;
use App\Models\ProgramModel;
use App\Models\SchoolTermModel;
use App\Models\StudentModel;
use App\Services\AccountService;
use App\Services\EligibilityService;
use App\Services\EnrollmentService;

class Students extends BaseController
{
    private function rules(?int $userId = null, ?int $studentId = null): array
    {
        return [
            'student_number' => 'required|regex_match[/^\d{3,10}$/]|is_unique[users.username,id,' . ($userId ?? 0) . ']|is_unique[students.student_number,id,' . ($studentId ?? 0) . ']',
            'first_name'     => 'required|max_length[80]',
            'middle_name'    => 'permit_empty|max_length[80]',
            'last_name'      => 'required|max_length[80]',
            'email'          => 'required|valid_email|max_length[120]|is_unique[users.email,id,' . ($userId ?? 0) . ']',
            'program_id'     => 'required|is_not_unique[programs.id]',
            'year_level'     => 'required|integer|greater_than[0]|less_than[7]',
            'section'        => 'permit_empty|max_length[20]',
            'contact_number' => 'permit_empty|max_length[30]',
        ];
    }

    private const MESSAGES = ['student_number' => ['regex_match' => 'Student ID number must be digits only (e.g. 9785).', 'is_unique' => 'That student ID number is already registered.']];

    public function index(): string
    {
        $q       = trim((string) $this->request->getGet('q'));
        $program = (int) $this->request->getGet('program');
        $status  = (string) $this->request->getGet('status');
        $term    = model(SchoolTermModel::class)->current();

        $model = model(StudentModel::class)->detailed()
            ->select('c.status AS clearance_status, c.id AS clearance_id')
            ->join('clearances c', 'c.student_id = students.id AND c.school_term_id = ' . (int) ($term['id'] ?? 0), 'left');
        if ($q !== '') {
            $model->groupStart()->like('users.first_name', $q)->orLike('users.last_name', $q)->orLike('students.student_number', $q)->orLike('users.email', $q)->groupEnd();
        }
        if ($program) {
            $model->where('students.program_id', $program);
        }
        if ($status === 'pending') {
            $model->where('users.approval_status', 'pending');
        } elseif (in_array($status, ['active', 'inactive', 'graduated'], true)) {
            $model->where('students.status', $status)->where('users.approval_status', 'approved');
        }

        $students = $model->orderBy('users.last_name')->paginate(15);

        return $this->page('admin/students/index', [
            'title'    => 'Students',
            'subtitle' => 'Student records, enrollment and clearance progress.',
            'students' => $students,
            'pager'    => model(StudentModel::class)->pager,
            'programs' => model(ProgramModel::class)->orderBy('code')->findAll(),
            'q'        => $q, 'program' => $program, 'status' => $status, 'term' => $term,
            'pendingCount' => model(\App\Models\UserModel::class)->where('role', 'student')->where('approval_status', 'pending')->countAllResults(),
        ]);
    }

    public function create(): string
    {
        return $this->page('admin/students/form', [
            'title' => 'New student', 'subtitle' => 'Create a student account with a temporary password.',
            'student' => null, 'programs' => model(ProgramModel::class)->where('is_active', 1)->orderBy('code')->findAll(),
        ]);
    }

    public function store()
    {
        if (! $this->validate($this->rules(), self::MESSAGES)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $result = (new AccountService())->createStudent($this->request->getPost());

        return redirect()->to(site_url('admin/students/' . $result['id']))
            ->with('toast_success', 'Student account created.')
            ->with('temp_password', $result['password']);
    }

    public function show(int $id): string
    {
        $student = model(StudentModel::class)->findDetailed($id);
        if (! $student) {
            $this->notFound();
        }

        $terms   = model(SchoolTermModel::class)->allWithYear();
        $current = model(SchoolTermModel::class)->current();
        $termId  = (int) ($this->request->getGet('term') ?: ($current['id'] ?? 0));
        $term    = model(SchoolTermModel::class)->findWithYear($termId);

        $enrollments = db_connect()->table('enrollments e')
            ->select("e.*, so.section, so.school_term_id, s.code, s.title, s.units, CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name, cs.status AS cs_status, cs.reenrollment_status, cs.signed_at")
            ->join('subject_offerings so', 'so.id = e.subject_offering_id')
            ->join('subjects s', 's.id = so.subject_id')
            ->join('teachers t', 't.id = so.teacher_id')
            ->join('users u', 'u.id = t.user_id')
            ->join('clearances c', 'c.student_id = e.student_id AND c.school_term_id = so.school_term_id', 'left')
            ->join('clearance_subjects cs', 'cs.clearance_id = c.id AND cs.subject_offering_id = so.id', 'left')
            ->where('e.student_id', $id)->where('so.school_term_id', $termId)
            ->orderBy('s.code')->get()->getResultArray();

        $enrolledIds = array_column(array_filter($enrollments, static fn ($e) => $e['status'] === 'enrolled'), 'subject_offering_id');
        $offerings   = db_connect()->table('subject_offerings so')
            ->select("so.id, so.section, s.code, s.title, CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name")
            ->join('subjects s', 's.id = so.subject_id')->join('teachers t', 't.id = so.teacher_id')->join('users u', 'u.id = t.user_id')
            ->where('so.school_term_id', $termId)->orderBy('s.code')->get()->getResultArray();
        $offerings = array_values(array_filter($offerings, static fn ($o) => ! in_array($o['id'], $enrolledIds)));

        $clearances = model(ClearanceModel::class)->detailed()->where('clearances.student_id', $id)->orderBy('school_terms.start_date', 'DESC')->findAll();

        return $this->page('admin/students/show', [
            'title'       => person_name($student),
            'eyebrow'     => 'Student record',
            'subtitle'    => ($student['student_number'] ?? 'No student number yet') . ' · ' . $student['program_name'],
            'student'     => $student,
            'terms'       => $terms,
            'term'        => $term,
            'enrollments' => $enrollments,
            'offerings'   => $offerings,
            'clearances'  => $clearances,
            'eligibility' => $term ? (new EligibilityService())->canEnrollInTerm($id, $term) : null,
            'currentEligibility' => (new EligibilityService())->forStudent($id),
        ]);
    }

    public function edit(int $id): string
    {
        $student = model(StudentModel::class)->findDetailed($id) ?? $this->notFound();

        return $this->page('admin/students/form', [
            'title' => 'Edit student', 'subtitle' => person_name($student) . ' · ' . ($student['student_number'] ?? 'No student number yet'),
            'student' => $student, 'programs' => model(ProgramModel::class)->orderBy('code')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $student = model(StudentModel::class)->findDetailed($id) ?? $this->notFound();
        $rules   = $this->rules((int) $student['user_id'], $id) + ['status' => 'required|in_list[active,inactive,graduated]'];
        if (! $this->validate($rules, self::MESSAGES)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        (new AccountService())->updateStudent($student, $this->request->getPost());

        return redirect()->to(site_url('admin/students/' . $id))->with('toast_success', 'Student record updated.');
    }

    public function resetPassword(int $id)
    {
        $student  = model(StudentModel::class)->find($id) ?? $this->notFound();
        $password = (new AccountService())->resetPassword((int) $student['user_id']);

        return redirect()->to(site_url('admin/students/' . $id))->with('toast_success', 'Password reset.')->with('temp_password', $password);
    }

    public function approve(int $id)
    {
        $student = model(StudentModel::class)->findDetailed($id) ?? $this->notFound();

        return $this->act(function () use ($student) {
            (new AccountService())->approveStudent($student, (string) $this->request->getPost('student_number'));
        }, 'Account approved. The student can now sign in with their student ID number or email.', 'admin/students/' . $id);
    }

    public function reject(int $id)
    {
        $student = model(StudentModel::class)->findDetailed($id) ?? $this->notFound();

        return $this->act(function () use ($student) {
            (new AccountService())->rejectStudent($student, (string) $this->request->getPost('reason'));
        }, 'Sign-up rejected and removed.', 'admin/students?status=pending');
    }

    public function enroll(int $id)
    {
        return $this->act(function () use ($id) {
            (new EnrollmentService())->enroll($id, (int) $this->request->getPost('subject_offering_id'), (string) $this->request->getPost('enrollment_type'), (int) $this->auth->id());
        }, 'Student enrolled in the subject.');
    }

    public function dropEnrollment(int $enrollmentId)
    {
        return $this->act(function () use ($enrollmentId) {
            (new EnrollmentService())->drop($enrollmentId);
        }, 'Enrollment dropped.');
    }
}
