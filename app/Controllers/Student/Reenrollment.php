<?php

namespace App\Controllers\Student;

use App\Models\ReenrollmentReceiptModel;
use App\Services\FileStorage;
use App\Services\ReceiptService;

class Reenrollment extends StudentController
{
    public function index(): string
    {
        $subjects = db_connect()->table('clearance_subjects cs')
            ->select('cs.*, c.reference_no, c.status AS clearance_status, st.semester, sy.name AS school_year, tu.email AS teacher_email')
            ->join('clearances c', 'c.id = cs.clearance_id')
            ->join('school_terms st', 'st.id = c.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->join('teachers t', 't.id = cs.teacher_id')
            ->join('users tu', 'tu.id = t.user_id')
            ->where('c.student_id', $this->studentId())
            ->where('cs.status', 'FAILED')
            ->orderBy('st.start_date', 'DESC')
            ->get()->getResultArray();

        $receipts = [];
        if ($subjects !== []) {
            foreach (model(ReenrollmentReceiptModel::class)->whereIn('clearance_subject_id', array_column($subjects, 'id'))->orderBy('id', 'DESC')->findAll() as $r) {
                $receipts[(int) $r['clearance_subject_id']][] = $r;
            }
        }

        return $this->page('student/reenrollment', [
            'title'    => 'Re-enrollment',
            'subtitle' => 'Failed subjects must be re-enrolled and paid before your clearance can be completed.',
            'subjects' => $subjects,
            'receipts' => $receipts,
            'maxBytes' => FileStorage::maxBytes(),
        ]);
    }

    public function upload(int $csId)
    {
        return $this->act(function () use ($csId) {
            (new ReceiptService())->submitReenrollment(
                $this->studentId(),
                $csId,
                $this->request->getFile('receipt'),
                $this->request->getPost(['or_number', 'amount', 'payment_date']),
            );

            return 'Re-enrollment receipt submitted for registrar validation.';
        }, '', 'student/reenrollment');
    }
}
