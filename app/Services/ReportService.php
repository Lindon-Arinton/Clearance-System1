<?php

namespace App\Services;

/**
 * Read-only statistics for the admin dashboard, reports and CSV exports.
 */
class ReportService
{
    private function db()
    {
        return db_connect();
    }

    /**
     * One row per student enrolled in the term, with clearance + eligibility state.
     */
    public function studentStatuses(int $termId, ?int $programId = null): array
    {
        $b = $this->db()->table('students s')
            ->select("s.id AS student_id, s.student_number, s.year_level, p.code AS program_code, u.first_name, u.last_name,
                c.id AS clearance_id, c.reference_no, c.status AS clearance_status, c.completed_at, c.enrollment_eligible,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = c.id) AS subjects,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = c.id AND cs.is_resolved = 1) AS resolved,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = c.id AND cs.status = 'INC') AS inc,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.clearance_id = c.id AND cs.status = 'FAILED') AS failed")
            ->join('users u', 'u.id = s.user_id')
            ->join('programs p', 'p.id = s.program_id')
            ->join('clearances c', 'c.student_id = s.id AND c.school_term_id = ' . $termId, 'left')
            ->where("EXISTS (SELECT 1 FROM enrollments e JOIN subject_offerings so ON so.id = e.subject_offering_id WHERE e.student_id = s.id AND e.status = 'enrolled' AND so.school_term_id = {$termId})", null, false);
        if ($programId) {
            $b->where('s.program_id', $programId);
        }

        return $b->orderBy('u.last_name')->get()->getResultArray();
    }

    /**
     * @return array{enrolled: int, not_started: int, awaiting_receipt: int, receipt_review: int, reupload_required: int, in_progress: int, completed: int, eligible: int, not_eligible: int}
     */
    public function summary(array $statuses): array
    {
        $s = ['enrolled' => count($statuses), 'not_started' => 0, 'awaiting_receipt' => 0, 'receipt_review' => 0, 'reupload_required' => 0, 'in_progress' => 0, 'completed' => 0, 'eligible' => 0, 'not_eligible' => 0];
        foreach ($statuses as $row) {
            $s[$row['clearance_status'] ?? 'not_started']++;
            (int) $row['enrollment_eligible'] === 1 ? $s['eligible']++ : $s['not_eligible']++;
        }

        return $s;
    }

    public function byProgram(array $statuses): array
    {
        $out = [];
        foreach ($statuses as $row) {
            $p = $row['program_code'];
            $out[$p] ??= ['program' => $p, 'enrolled' => 0, 'started' => 0, 'card' => 0, 'completed' => 0];
            $out[$p]['enrolled']++;
            if ($row['clearance_status']) {
                $out[$p]['started']++;
            }
            if (in_array($row['clearance_status'], ['in_progress', 'completed'], true)) {
                $out[$p]['card']++;
            }
            if ($row['clearance_status'] === 'completed') {
                $out[$p]['completed']++;
            }
        }
        ksort($out);

        return array_values($out);
    }

    public function subjectOutcomes(int $termId): array
    {
        return $this->db()->table('subject_offerings so')
            ->select("so.id, so.section, s.code, s.title, CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.subject_offering_id = so.id AND e.status = 'enrolled') AS students,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PENDING') AS pending,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PASSED' AND cs.signed_at IS NOT NULL) AS passed,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'PASSED' AND cs.signed_at IS NULL) AS awaiting_sign,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'INC') AS inc,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status = 'FAILED') AS failed")
            ->join('subjects s', 's.id = so.subject_id')->join('teachers t', 't.id = so.teacher_id')->join('users u', 'u.id = t.user_id')
            ->where('so.school_term_id', $termId)->orderBy('s.code')->get()->getResultArray();
    }

    public function incCases(int $termId, ?int $limit = null): array
    {
        $b = $this->db()->table('clearance_subjects cs')
            ->select("cs.id, cs.subject_code, cs.subject_title, cs.teacher_name, cs.decided_at, c.id AS clearance_id, s.student_number, u.first_name, u.last_name,
                (SELECT COUNT(*) FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending') AS pending_reqs,
                (SELECT COUNT(*) FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending' AND r.student_reported_at IS NOT NULL) AS reported,
                (SELECT GROUP_CONCAT(r.description SEPARATOR '; ') FROM inc_requirements r WHERE r.clearance_subject_id = cs.id AND r.status = 'pending') AS requirements")
            ->join('clearances c', 'c.id = cs.clearance_id')->join('students s', 's.id = c.student_id')->join('users u', 'u.id = s.user_id')
            ->where('c.school_term_id', $termId)->where('cs.status', 'INC')->orderBy('cs.decided_at');

        return ($limit ? $b->limit($limit) : $b)->get()->getResultArray();
    }

    public function failedCases(int $termId, ?int $limit = null): array
    {
        $b = $this->db()->table('clearance_subjects cs')
            ->select('cs.id, cs.subject_code, cs.subject_title, cs.teacher_name, cs.decided_at, cs.reenrollment_status, cs.reenrollment_confirmed_at, cs.final_grade,
                c.id AS clearance_id, s.student_number, u.first_name, u.last_name')
            ->join('clearances c', 'c.id = cs.clearance_id')->join('students s', 's.id = c.student_id')->join('users u', 'u.id = s.user_id')
            ->where('c.school_term_id', $termId)->where('cs.status', 'FAILED')->orderBy('cs.reenrollment_status')->orderBy('cs.decided_at');

        return ($limit ? $b->limit($limit) : $b)->get()->getResultArray();
    }

    public function receiptStats(int $termId): array
    {
        $rows = $this->db()->query("SELECT r.status, COUNT(*) AS n FROM tuition_receipts r JOIN clearances c ON c.id = r.clearance_id
            WHERE c.school_term_id = ? AND r.id = (SELECT MAX(r2.id) FROM tuition_receipts r2 WHERE r2.clearance_id = r.clearance_id) GROUP BY r.status", [$termId])->getResultArray();
        $out = ['submitted' => 0, 'under_review' => 0, 'approved' => 0, 'reupload_required' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['attempts'] = (int) $this->db()->query('SELECT COUNT(*) AS n FROM tuition_receipts r JOIN clearances c ON c.id = r.clearance_id WHERE c.school_term_id = ?', [$termId])->getRow('n');

        return $out;
    }

    public function pendingReenrollmentReceipts(): int
    {
        return $this->db()->table('reenrollment_receipts r')->whereIn('r.status', ['submitted', 'under_review'])
            ->where('r.id = (SELECT MAX(r2.id) FROM reenrollment_receipts r2 WHERE r2.clearance_subject_id = r.clearance_subject_id)', null, false)
            ->countAllResults();
    }
}
