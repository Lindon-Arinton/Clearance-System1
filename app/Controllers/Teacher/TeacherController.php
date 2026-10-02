<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\SchoolTermModel;

abstract class TeacherController extends BaseController
{
    protected function teacherId(): int
    {
        return (int) $this->auth->teacherId();
    }

    protected function currentTerm(): ?array
    {
        return model(SchoolTermModel::class)->current();
    }

    /**
     * The teacher's subject offerings with clearance counters.
     */
    protected function offerings(?int $termId = null): array
    {
        $b = db_connect()->table('subject_offerings so')
            ->select("so.id, so.section, so.schedule, so.school_term_id, s.code, s.title, s.units, st.semester, st.start_date, st.is_current, sy.name AS school_year,
                (SELECT COUNT(*) FROM enrollments e WHERE e.subject_offering_id = so.id AND e.status = 'enrolled') AS students,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id) AS on_cards,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PASSED' AND cs.signed_at IS NOT NULL) AS passed,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PASSED' AND cs.signed_at IS NULL) AS awaiting_sign,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'INC') AS inc,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'FAILED') AS failed,
                (SELECT COUNT(*) FROM clearance_subjects cs JOIN clearances c ON c.id = cs.clearance_id WHERE cs.subject_offering_id = so.id AND cs.status = 'PENDING' AND c.status = 'in_progress') AS pending,
                (SELECT COUNT(*) FROM inc_requirements r JOIN clearance_subjects cs ON cs.id = r.clearance_subject_id WHERE cs.subject_offering_id = so.id AND r.status = 'pending' AND r.student_reported_at IS NOT NULL) AS reported")
            ->join('subjects s', 's.id = so.subject_id')
            ->join('school_terms st', 'st.id = so.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->where('so.teacher_id', $this->teacherId());
        if ($termId !== null) {
            $b->where('so.school_term_id', $termId);
        }

        return $b->orderBy('st.start_date', 'DESC')->orderBy('s.code')->get()->getResultArray();
    }
}
