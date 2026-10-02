<?php

namespace App\Services;

use App\Models\ClearanceModel;
use App\Models\SchoolTermModel;

/**
 * Enrollment eligibility is derived from clearance completion — never set by hand.
 */
class EligibilityService
{
    /**
     * Eligibility for next-semester enrollment based on the current term's clearance.
     *
     * @return array{status: string, term: ?array, clearance: ?array}
     */
    public function forStudent(int $studentId): array
    {
        $term      = model(SchoolTermModel::class)->current();
        $clearance = $term ? model(ClearanceModel::class)->forStudentTerm($studentId, (int) $term['id']) : null;
        $eligible  = $clearance && $clearance['status'] === 'completed' && (int) $clearance['enrollment_eligible'] === 1;

        return ['status' => $eligible ? 'ELIGIBLE' : 'NOT_ELIGIBLE', 'term' => $term, 'clearance' => $clearance];
    }

    /**
     * Whether a student may be enrolled into subjects of the given term.
     * Rule: the most recent earlier term in which the student had subjects must
     * have a completed clearance. New students (no earlier subjects) are eligible.
     *
     * @return array{ok: bool, reason: string, previous: ?array}
     */
    public function canEnrollInTerm(int $studentId, array $term): array
    {
        $previous = db_connect()->table('enrollments e')
            ->select('st.id, st.semester, st.start_date, sy.name AS school_year')
            ->join('subject_offerings so', 'so.id = e.subject_offering_id')
            ->join('school_terms st', 'st.id = so.school_term_id')
            ->join('school_years sy', 'sy.id = st.school_year_id')
            ->where('e.student_id', $studentId)
            ->where('e.status', 'enrolled')
            ->where('st.start_date <', $term['start_date'])
            ->orderBy('st.start_date', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        if (! $previous) {
            return ['ok' => true, 'reason' => 'No earlier term on record.', 'previous' => null];
        }

        $clearance = model(ClearanceModel::class)->where('student_id', $studentId)->where('school_term_id', $previous['id'])->first();
        if ($clearance && $clearance['status'] === 'completed' && (int) $clearance['enrollment_eligible'] === 1) {
            return ['ok' => true, 'reason' => 'Clearance for ' . term_label($previous) . ' is completed.', 'previous' => $previous];
        }

        return [
            'ok'       => false,
            'reason'   => 'Not eligible: clearance for ' . term_label($previous) . ' is ' . ($clearance ? status_meta($clearance['status'])['label'] : 'not started') . '.',
            'previous' => $previous,
        ];
    }
}
