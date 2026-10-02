<?php

namespace App\Database\Seeds;

use App\Services\ClearanceService;
use App\Services\FileStorage;
use App\Services\SubjectClearanceService;
use CodeIgniter\Database\Seeder;

/**
 * Demo data covering every stage of the clearance workflow.
 * All demo accounts use the password: Clearance@2026
 *
 *   php spark migrate:refresh && php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Clearance@2026';

    private string $hash;
    private array $programs  = [];
    private array $teachers  = [];   // key => [id, user_id, name]
    private array $students  = [];   // key => [id, user_id, row]
    private array $terms     = [];   // key => id
    private array $subjects  = [];   // code => row
    private array $offerings = [];   // termKey.code => id
    private int $adminUserId;

    public function run()
    {
        helper('ui');
        $this->hash = password_hash(self::DEMO_PASSWORD, PASSWORD_DEFAULT);

        $this->seedSettings();
        $this->seedPrograms();
        $this->seedUsers();
        $this->seedTerms();
        $this->seedSubjectsAndOfferings();
        $this->seedEnrollments();
        $this->seedHistory();
        $this->seedCurrentTerm();
    }

    // ------------------------------------------------------------------

    private function ago(string $modifier): string
    {
        return date('Y-m-d H:i:s', strtotime($modifier));
    }

    private function insert(string $table, array $row): int
    {
        $this->db->table($table)->insert($row);

        return (int) $this->db->insertID();
    }

    private function seedSettings(): void
    {
        foreach (['school_name' => 'Dr. Francisco L. Calingasan Memorial Colleges Foundation, Inc.', 'school_short_name' => 'DFLCMCFI', 'office_name' => 'College Registrar',
            'school_address' => 'Nasugbu / Tuy, Batangas', 'registrar_name' => 'Mara Villanueva', 'max_upload_mb' => '5'] as $k => $v) {
            $this->db->table('settings')->replace(['key' => $k, 'value' => $v, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function seedPrograms(): void
    {
        foreach ([
            ['BSIT', 'Bachelor of Science in Information Technology', 'College of Computing'],
            ['BSCS', 'Bachelor of Science in Computer Science', 'College of Computing'],
            ['BSBA', 'Bachelor of Science in Business Administration', 'College of Business'],
            ['BSEd', 'Bachelor of Secondary Education', 'College of Education'],
            ['BSN', 'Bachelor of Science in Nursing', 'College of Nursing'],
        ] as [$code, $name, $dept]) {
            $this->programs[$code] = $this->insert('programs', ['code' => $code, 'name' => $name, 'department' => $dept, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        }
    }

    private function user(string $role, string $username, string $first, string $last, ?string $email): int
    {
        return $this->insert('users', [
            'role' => $role, 'username' => $username, 'email' => $email, 'password_hash' => $this->hash,
            'first_name' => $first, 'last_name' => $last, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedUsers(): void
    {
        $this->adminUserId = $this->user('admin', 'admin', 'Mara', 'Villanueva', 'mara.villanueva@example.com');
        $this->user('admin', 'rgonzales', 'Rafael', 'Gonzales', 'rafael.gonzales@example.com');

        foreach ([
            'santos' => ['T-1001', 'Jose', 'Santos', 'Prof.', 'College of Computing'],
            'reyes'  => ['T-1002', 'Carmen', 'Reyes', 'Prof.', 'College of Computing'],
            'cruz'   => ['T-1003', 'Antonio', 'Cruz', 'Dr.', 'Department of Mathematics'],
            'ramos'  => ['T-1004', 'Alyssa', 'Ramos', 'Prof.', 'College of Computing'],
            'garcia' => ['T-1005', 'Ramon', 'Garcia', 'Prof.', 'College of Business'],
        ] as $key => [$emp, $first, $last, $title, $dept]) {
            $uid = $this->user('teacher', $emp, $first, $last, strtolower($first . '.' . $last) . '@example.com');
            $id  = $this->insert('teachers', ['user_id' => $uid, 'employee_number' => $emp, 'title' => $title, 'department' => $dept, 'created_at' => date('Y-m-d H:i:s')]);
            $this->teachers[$key] = ['id' => $id, 'user_id' => $uid, 'name' => "{$title} {$first} {$last}"];
        }

        // Teachers must upload an e-signature before they can clear students.
        // Prof. Garcia is left without one to demonstrate the first sign-in setup step.
        foreach (['santos' => 'Jose Santos', 'reyes' => 'Carmen Reyes', 'cruz' => 'Antonio Cruz', 'ramos' => 'Alyssa Ramos'] as $key => $signName) {
            $sig = $this->writeSignature($signName);
            $this->db->table('teachers')->where('id', $this->teachers[$key]['id'])->update(['signature_path' => $sig]);
        }

        foreach ([
            'juan'   => ['9785', 'Jasrylle Nicole', 'Botobara', 'BSIT', 4, '4A'],
            'sofia'  => ['9786', 'Sofia', 'Martinez', 'BSBA', 3, '3A'],
            'miguel' => ['9787', 'Miguel', 'Reyes', 'BSEd', 4, '4A'],
            'nina'   => ['9788', 'Nina', 'Bautista', 'BSN', 2, '2A'],
            'carlo'  => ['9789', 'Carlo', 'Mendoza', 'BSIT', 4, '4A'],
            'paolo'  => ['9790', 'Paolo', 'Santiago', 'BSIT', 4, '4A'],
            'andrea' => ['9791', 'Andrea', 'Lim', 'BSIT', 4, '4A'],
        ] as $key => [$num, $first, $last, $prog, $year, $section]) {
            $uid = $this->user('student', $num, $first, $last, strtolower(explode(' ', $first)[0] . '.' . str_replace(' ', '', $last)) . '@example.com');
            $id  = $this->insert('students', [
                'user_id' => $uid, 'student_number' => $num, 'program_id' => $this->programs[$prog], 'year_level' => $year,
                'section' => $section, 'status' => 'active', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->students[$key] = ['id' => $id, 'user_id' => $uid, 'name' => "{$first} {$last}", 'number' => $num, 'program' => $prog];
        }

        // Main demo student: Jasrylle Nicole B. Botobara, ID 9785.
        $this->db->table('users')->where('id', $this->students['juan']['user_id'])->update(['middle_name' => 'B.']);
        $this->students['juan']['name'] = 'Jasrylle Nicole B. Botobara';
    }

    private function seedTerms(): void
    {
        $y1 = $this->insert('school_years', ['name' => '2025-2026', 'start_date' => '2025-08-01', 'end_date' => '2026-05-31', 'created_at' => date('Y-m-d H:i:s')]);
        $y2 = $this->insert('school_years', ['name' => '2026-2027', 'start_date' => '2026-08-01', 'end_date' => '2027-05-31', 'created_at' => date('Y-m-d H:i:s')]);

        $this->terms['2025-1'] = $this->insert('school_terms', ['school_year_id' => $y1, 'semester' => '1st', 'start_date' => '2025-08-11', 'end_date' => '2025-12-19', 'status' => 'closed', 'is_current' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        $this->terms['2025-2'] = $this->insert('school_terms', ['school_year_id' => $y1, 'semester' => '2nd', 'start_date' => '2026-01-12', 'end_date' => '2026-05-22', 'status' => 'closed', 'is_current' => 0, 'created_at' => date('Y-m-d H:i:s')]);
        $this->terms['2026-1'] = $this->insert('school_terms', ['school_year_id' => $y2, 'semester' => '1st', 'start_date' => '2026-08-10', 'end_date' => '2026-12-18', 'status' => 'open', 'is_current' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        $this->terms['2026-2'] = $this->insert('school_terms', ['school_year_id' => $y2, 'semester' => '2nd', 'start_date' => '2027-01-11', 'end_date' => '2027-05-21', 'status' => 'upcoming', 'is_current' => 0, 'created_at' => date('Y-m-d H:i:s')]);
    }

    private function seedSubjectsAndOfferings(): void
    {
        foreach ([
            ['IT101', 'Introduction to Computing', 'BSIT'], ['IT102', 'Computer Programming Fundamentals', 'BSIT'],
            ['IT104', 'Discrete Structures', 'BSIT'], ['IT106', 'Data Structures and Algorithms', 'BSIT'],
            ['GE101', 'Purposive Communication', null], ['GE102', 'Ethics', null],
            ['IT201', 'Programming 1', 'BSIT'], ['IT203', 'Database Systems', 'BSIT'], ['IT205', 'Web Development', 'BSIT'],
            ['MATH104', 'Mathematics', null], ['BA210', 'Financial Management', 'BSBA'], ['ED220', 'Assessment of Learning', 'BSEd'],
            ['NCM201', 'Health Assessment', 'BSN'],
        ] as [$code, $title, $prog]) {
            $id = $this->insert('subjects', ['code' => $code, 'title' => $title, 'units' => 3, 'program_id' => $prog ? $this->programs[$prog] : null, 'is_active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
            $this->subjects[$code] = ['id' => $id, 'code' => $code, 'title' => $title];
        }

        $plan = [
            '2025-1' => ['IT101' => 'santos', 'GE101' => 'garcia', 'IT104' => 'cruz'],
            '2025-2' => ['IT102' => 'ramos', 'IT106' => 'reyes', 'GE102' => 'garcia'],
            '2026-1' => ['IT201' => 'santos', 'IT203' => 'reyes', 'MATH104' => 'cruz', 'IT205' => 'ramos', 'BA210' => 'garcia', 'ED220' => 'garcia', 'NCM201' => 'cruz'],
        ];
        foreach ($plan as $termKey => $subjects) {
            foreach ($subjects as $code => $teacher) {
                $this->offerings["{$termKey}.{$code}"] = $this->insert('subject_offerings', [
                    'school_term_id' => $this->terms[$termKey], 'subject_id' => $this->subjects[$code]['id'],
                    'teacher_id' => $this->teachers[$teacher]['id'], 'section' => 'A', 'schedule' => 'MWF 9:00–10:30',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function enroll(string $student, string $termKey, array $codes): void
    {
        foreach ($codes as $code) {
            $this->insert('enrollments', [
                'student_id' => $this->students[$student]['id'], 'subject_offering_id' => $this->offerings["{$termKey}.{$code}"],
                'enrollment_type' => 'regular', 'status' => 'enrolled', 'created_by' => $this->adminUserId, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedEnrollments(): void
    {
        $bsitCurrent = ['IT201', 'IT203', 'MATH104', 'IT205'];
        foreach (['juan', 'carlo', 'paolo'] as $s) {
            $this->enroll($s, '2025-1', ['IT101', 'GE101', 'IT104']);
            $this->enroll($s, '2025-2', ['IT102', 'IT106', 'GE102']);
            $this->enroll($s, '2026-1', $bsitCurrent);
        }
        $this->enroll('andrea', '2026-1', $bsitCurrent);
        $this->enroll('sofia', '2026-1', ['BA210', 'MATH104', 'IT205']);
        $this->enroll('miguel', '2026-1', ['ED220', 'MATH104', 'IT205']);
        $this->enroll('nina', '2026-1', ['NCM201', 'MATH104', 'IT205']);
    }

    // ------------------------------------------------------------------
    // Clearances
    // ------------------------------------------------------------------

    private function clearance(string $student, string $termKey, string $status, string $started, array $extra = []): int
    {
        $s    = $this->students[$student];
        $term = $this->db->table('school_terms st')->select('st.*, sy.name AS school_year')->join('school_years sy', 'sy.id = st.school_year_id')->where('st.id', $this->terms[$termKey])->get()->getRowArray();
        $id   = $this->insert('clearances', $extra + [
            'reference_no' => 'TMP-' . bin2hex(random_bytes(4)), 'student_id' => $s['id'], 'school_term_id' => $this->terms[$termKey],
            'status' => $status, 'started_at' => $started, 'created_at' => $started,
        ]);
        $ref = sprintf('CLR-%s-%s-%05d', substr($term['school_year'], 0, 4), strtoupper(substr($term['semester'], 0, 1)), $id);
        $this->db->table('clearances')->where('id', $id)->update(['reference_no' => $ref]);

        return $id;
    }

    private function snapshot(string $student): string
    {
        $row = model(\App\Models\StudentModel::class)->findDetailed($this->students[$student]['id']);

        return json_encode([
            'name' => person_name($row), 'student_number' => $row['student_number'], 'program_code' => $row['program_code'],
            'program_name' => $row['program_name'], 'year_level' => (int) $row['year_level'], 'section' => $row['section'],
        ]);
    }

    private function receipt(int $clearanceId, string $student, string $or, float $amount, string $termLabel, string $status, string $submitted, array $extra = [], string $format = 'png', bool $blurry = false): int
    {
        $s    = $this->students[$student];
        $meta = $format === 'pdf'
            ? $this->writeReceiptPdf('receipts/tuition', $s['name'], $s['number'], $or, $amount, $termLabel, substr($submitted, 0, 10))
            : $this->writeReceiptImage('receipts/tuition', $s['name'], $s['number'], $or, $amount, $termLabel, substr($submitted, 0, 10), $blurry);
        $attempt = $this->db->table('tuition_receipts')->where('clearance_id', $clearanceId)->countAllResults() + 1;

        return $this->insert('tuition_receipts', $extra + $meta + [
            'clearance_id' => $clearanceId, 'student_id' => $s['id'], 'attempt_no' => $attempt, 'or_number' => $or, 'amount' => $amount,
            'payment_date' => substr($submitted, 0, 10), 'status' => $status, 'submitted_at' => $submitted, 'created_at' => $submitted,
        ]);
    }

    /**
     * Create card rows from enrollments and apply outcomes: code => [status, signed?, extra].
     */
    private function card(int $clearanceId, string $student, string $termKey, array $outcomes, string $when): array
    {
        $svc  = new ClearanceService();
        $rows = $svc->enrolledOfferings($this->students[$student]['id'], $this->terms[$termKey]);
        $ids  = [];
        foreach ($rows as $o) {
            [$status, $signed, $extra] = ($outcomes[$o['code']] ?? []) + [null, false, []];
            $teacherUser = $this->db->table('teachers')->where('id', $o['teacher_id'])->get()->getRow('user_id');
            $row = [
                'clearance_id' => $clearanceId, 'enrollment_id' => $o['enrollment_id'], 'subject_offering_id' => $o['offering_id'],
                'subject_id' => $o['subject_id'], 'teacher_id' => $o['teacher_id'], 'subject_code' => $o['code'], 'subject_title' => $o['title'],
                'units' => $o['units'], 'teacher_name' => $o['teacher_name'], 'status' => $status ?? 'PENDING', 'created_at' => $when,
            ];
            if ($status && $status !== 'PENDING') {
                $row['decided_by'] = $teacherUser;
                $row['decided_at'] = $extra['decided_at'] ?? $when;
            }
            $id = $this->insert('clearance_subjects', $extra + $row);
            if ($signed) {
                $signedAt = $extra['decided_at'] ?? $when;
                $hash     = SubjectClearanceService::signatureHash(['id' => $id, 'clearance_id' => $clearanceId], (int) $o['teacher_id'], $signedAt);
                $sigImage = $this->db->table('teachers')->where('id', $o['teacher_id'])->get()->getRow('signature_path');
                $this->db->table('clearance_subjects')->where('id', $id)->update(['signed_by' => $teacherUser, 'signed_at' => $signedAt, 'signature_hash' => $hash, 'signature_image' => $sigImage]);
            }
            $ids[$o['code']] = ['id' => $id, 'teacher_user_id' => (int) $teacherUser, 'title' => $o['title'], 'teacher' => $o['teacher_name']];
        }

        return $ids;
    }

    private function markResolved(int $clearanceId): void
    {
        foreach ($this->db->table('clearance_subjects')->where('clearance_id', $clearanceId)->get()->getResultArray() as $cs) {
            if (ClearanceService::isResolved($cs)) {
                $this->db->table('clearance_subjects')->where('id', $cs['id'])->update(['is_resolved' => 1, 'resolved_at' => $cs['signed_at'] ?? $cs['reenrollment_confirmed_at']]);
            }
        }
    }

    private function seedHistory(): void
    {
        $history = [
            '2025-1' => ['1st Semester AY 2025–2026', '2025-08-20 09:15:00', '2025-12-15 14:30:00', ['IT101', 'GE101', 'IT104'], 38200.00],
            '2025-2' => ['2nd Semester AY 2025–2026', '2026-01-20 10:05:00', '2026-05-18 11:10:00', ['IT102', 'IT106', 'GE102'], 39800.00],
        ];
        $n = 11000;
        foreach (['juan', 'carlo', 'paolo'] as $student) {
            foreach ($history as $termKey => [$label, $started, $completed, $codes, $amount]) {
                $cid = $this->clearance($student, $termKey, 'completed', $started, [
                    'card_issued_at' => date('Y-m-d H:i:s', strtotime($started . ' +2 days')),
                    'completed_at' => $completed, 'enrollment_eligible' => 1, 'eligible_at' => $completed, 'student_snapshot' => $this->snapshot($student),
                ]);
                $this->receipt($cid, $student, 'OR-' . substr($termKey, 0, 4) . '-' . (++$n), $amount, $label, 'approved', date('Y-m-d H:i:s', strtotime($started . ' +1 hour')), [
                    'reviewed_by' => $this->adminUserId, 'reviewed_at' => date('Y-m-d H:i:s', strtotime($started . ' +2 days')),
                    'checklist' => json_encode(array_fill_keys(array_keys(receipt_checklist_items()), true)),
                ]);
                $outcomes = [];
                foreach ($codes as $i => $code) {
                    $outcomes[$code] = ['PASSED', true, ['decided_at' => date('Y-m-d H:i:s', strtotime($completed . ' -' . ($i * 2) . ' days')), 'final_grade' => ['1.50', '1.75', '2.00'][$i % 3]]];
                }
                // Jasrylle had one INC last year that was completed and changed to PASSED.
                if ($student === 'juan' && $termKey === '2025-2') {
                    $outcomes['IT106'][2]['was_incomplete'] = 1;
                    $outcomes['IT106'][2]['remarks']        = 'Final project submitted and verified.';
                }
                $this->card($cid, $student, $termKey, $outcomes, date('Y-m-d H:i:s', strtotime($started . ' +2 days')));
                $this->markResolved($cid);
            }
        }
    }

    private function seedCurrentTerm(): void
    {
        $label = '1st Semester AY 2026–2027';
        $tuitionChecklist = json_encode(array_fill_keys(array_keys(receipt_checklist_items()), true));

        // Jasrylle — card active: PASSED+signed, INC, FAILED (re-enrollment required), PENDING.
        $cid = $this->clearance('juan', '2026-1', 'in_progress', $this->ago('-13 days'), ['card_issued_at' => $this->ago('-12 days'), 'student_snapshot' => $this->snapshot('juan')]);
        $this->receipt($cid, 'juan', 'OR-2026-18492', 42500, $label, 'approved', $this->ago('-13 days'), ['reviewed_by' => $this->adminUserId, 'reviewed_at' => $this->ago('-12 days'), 'checklist' => $tuitionChecklist]);
        $cs = $this->card($cid, 'juan', '2026-1', [
            'IT201'   => ['PASSED', true, ['decided_at' => $this->ago('-5 days'), 'final_grade' => '1.50', 'remarks' => 'Excellent laboratory work.']],
            'IT203'   => ['INC', false, ['decided_at' => $this->ago('-3 days'), 'remarks' => 'Missing laboratory requirement.']],
            'MATH104' => ['FAILED', false, ['decided_at' => $this->ago('-2 days'), 'reenrollment_status' => 'RE_ENROLLMENT_REQUIRED', 'final_grade' => '5.00', 'remarks' => 'Did not meet the minimum passing grade.']],
        ], $this->ago('-12 days'));
        $this->markResolved($cid);
        $this->insert('inc_requirements', [
            'clearance_subject_id' => $cs['IT203']['id'], 'description' => 'Lab completion — Final laboratory activity',
            'instructions' => 'Complete the missing requirement personally with your subject teacher.', 'due_date' => date('Y-m-d', strtotime('+14 days')),
            'status' => 'pending', 'created_by' => $cs['IT203']['teacher_user_id'], 'created_at' => $this->ago('-3 days'),
        ]);

        // Carlo — completed this term, eligible for next semester.
        $cid = $this->clearance('carlo', '2026-1', 'completed', $this->ago('-20 days'), [
            'card_issued_at' => $this->ago('-19 days'), 'completed_at' => $this->ago('-1 day'), 'enrollment_eligible' => 1,
            'eligible_at' => $this->ago('-1 day'), 'student_snapshot' => $this->snapshot('carlo'),
        ]);
        $this->receipt($cid, 'carlo', 'OR-2026-18377', 42500, $label, 'approved', $this->ago('-20 days'), ['reviewed_by' => $this->adminUserId, 'reviewed_at' => $this->ago('-19 days'), 'checklist' => $tuitionChecklist]);
        $this->card($cid, 'carlo', '2026-1', [
            'IT201' => ['PASSED', true, ['decided_at' => $this->ago('-6 days'), 'final_grade' => '1.25']],
            'IT203' => ['PASSED', true, ['decided_at' => $this->ago('-4 days'), 'final_grade' => '1.75']],
            'MATH104' => ['PASSED', true, ['decided_at' => $this->ago('-3 days'), 'final_grade' => '2.00']],
            'IT205' => ['PASSED', true, ['decided_at' => $this->ago('-1 day'), 'final_grade' => '1.50']],
        ], $this->ago('-19 days'));
        $this->markResolved($cid);

        // Paolo — PASSED awaiting signature, INC reported by student, FAILED with re-enrollment receipt submitted.
        $cid = $this->clearance('paolo', '2026-1', 'in_progress', $this->ago('-15 days'), ['card_issued_at' => $this->ago('-14 days'), 'student_snapshot' => $this->snapshot('paolo')]);
        $this->receipt($cid, 'paolo', 'OR-2026-18410', 42500, $label, 'approved', $this->ago('-15 days'), ['reviewed_by' => $this->adminUserId, 'reviewed_at' => $this->ago('-14 days'), 'checklist' => $tuitionChecklist]);
        $cs = $this->card($cid, 'paolo', '2026-1', [
            'IT201'   => ['PASSED', false, ['decided_at' => $this->ago('-1 day'), 'final_grade' => '2.25']],
            'IT203'   => ['INC', false, ['decided_at' => $this->ago('-6 days'), 'remarks' => 'Final project not submitted.']],
            'MATH104' => ['FAILED', false, ['decided_at' => $this->ago('-7 days'), 'reenrollment_status' => 'RE_ENROLLMENT_RECEIPT_PENDING', 'final_grade' => '5.00']],
        ], $this->ago('-14 days'));
        $this->markResolved($cid);
        $this->insert('inc_requirements', [
            'clearance_subject_id' => $cs['IT203']['id'], 'description' => 'Final project — normalized schema and ER diagram',
            'instructions' => 'Present the project personally to your subject teacher.', 'status' => 'pending',
            'student_reported_at' => $this->ago('-4 hours'), 'student_note' => 'I presented the project during consultation hours today.',
            'created_by' => $cs['IT203']['teacher_user_id'], 'created_at' => $this->ago('-6 days'),
        ]);
        $meta = $this->writeReceiptImage('receipts/reenrollment', 'Paolo Santiago', '9790', 'OR-2026-19021', 4800, 'Re-enrollment · MATH104', date('Y-m-d', strtotime('-1 day')));
        $reId = $this->insert('reenrollment_receipts', $meta + [
            'clearance_subject_id' => $cs['MATH104']['id'], 'student_id' => $this->students['paolo']['id'], 'attempt_no' => 1,
            'or_number' => 'OR-2026-19021', 'amount' => 4800, 'payment_date' => date('Y-m-d', strtotime('-1 day')),
            'status' => 'submitted', 'submitted_at' => $this->ago('-50 minutes'), 'created_at' => $this->ago('-50 minutes'),
        ]);

        // Sofia — receipt submitted, waiting in the registrar queue.
        $cid = $this->clearance('sofia', '2026-1', 'receipt_review', $this->ago('-1 hour'));
        $sofiaReceipt = $this->receipt($cid, 'sofia', 'OR-2026-18501', 38750, $label, 'submitted', $this->ago('-28 minutes'));

        // Nina — receipt submitted as a PDF.
        $cid = $this->clearance('nina', '2026-1', 'receipt_review', $this->ago('-3 hours'));
        $ninaReceipt = $this->receipt($cid, 'nina', 'OR-2026-18533', 45600, $label, 'submitted', $this->ago('-2 hours'), [], 'pdf');

        // Miguel — blurry upload, registrar requested a re-upload.
        $cid = $this->clearance('miguel', '2026-1', 'reupload_required', $this->ago('-1 day'));
        $this->receipt($cid, 'miguel', 'OR-2026-18466', 31200, $label, 'reupload_required', $this->ago('-1 day'), [
            'reviewed_by' => $this->adminUserId, 'reviewed_at' => $this->ago('-1 hour'), 'reupload_reason_code' => 'blurry',
            'reupload_reason' => 'Please upload a clearer image where the receipt number, student name, date, and amount are readable.',
        ], 'png', true);

        // Andrea — enrolled, clearance not started yet.

        // Notifications that match the seeded history.
        $note = fn (string $who, string $type, string $title, string $msg, string $link, string $when, bool $read = false) => $this->insert('notifications', [
            'user_id' => $who === 'admin' ? $this->adminUserId : ($this->students[$who]['user_id'] ?? $this->teachers[$who]['user_id']),
            'type' => $type, 'title' => $title, 'message' => $msg, 'link' => $link, 'created_at' => $when, 'read_at' => $read ? $when : null,
        ]);
        $note('juan', 'receipt_approved', 'Tuition receipt approved', 'Your receipt OR-2026-18492 was verified by the registrar.', 'student/receipt', $this->ago('-12 days'), true);
        $note('juan', 'clearance_created', 'Your clearance card is ready', 'Your clearance card for 1st Semester AY 2026–2027 has been created with 4 subject(s).', 'student/card', $this->ago('-12 days'), true);
        $note('juan', 'subject_passed', 'Programming 1: PASSED', 'Prof. Jose Santos marked Programming 1 as PASSED and signed your clearance.', 'student/subjects', $this->ago('-5 days'));
        $note('juan', 'subject_inc', 'Database Systems: INC', 'Prof. Carmen Reyes marked Database Systems as Incomplete. Complete the missing requirement(s) personally with your teacher.', 'student/inc', $this->ago('-3 days'));
        $note('juan', 'subject_failed', 'Mathematics: FAILED', 'Dr. Antonio Cruz marked Mathematics as FAILED. You must re-enroll in this subject and upload the re-enrollment receipt.', 'student/reenrollment', $this->ago('-2 days'));
        $note('miguel', 'receipt_reupload', 'Receipt re-upload required', 'Reason: Please upload a clearer image where the receipt number, student name, date, and amount are readable.', 'student/receipt', $this->ago('-1 hour'));
        $note('carlo', 'clearance_completed', 'Clearance completed', 'Congratulations! Your clearance for 1st Semester AY 2026–2027 is complete and has been saved to your clearance history.', 'student/dashboard', $this->ago('-1 day'));
        $note('carlo', 'eligible_next_semester', 'Eligible for next semester', 'You are now qualified to enroll for the next semester.', 'student/dashboard', $this->ago('-1 day'));
        $note('ramos', 'clearance_action_required', 'Student requires clearance action', 'Jasrylle Nicole B. Botobara (9785) needs your clearance decision for IT205.', 'teacher/clearance', $this->ago('-12 days'));
        $note('reyes', 'inc_student_completed', 'Student completed an INC requirement', 'Paolo Santiago (9790) reports completing "Final project — normalized schema and ER diagram" for IT203. Please verify.', 'teacher/inc', $this->ago('-4 hours'));
        $note('admin', 'receipt_new', 'New tuition receipt submitted', 'Sofia Martinez (9786) submitted receipt OR-2026-18501 for validation.', 'admin/receipts?id=' . $sofiaReceipt, $this->ago('-28 minutes'));
        $note('admin', 'receipt_new', 'New tuition receipt submitted', 'Nina Bautista (9788) submitted receipt OR-2026-18533 for validation.', 'admin/receipts?id=' . $ninaReceipt, $this->ago('-2 hours'));
        $note('admin', 'reenrollment_receipt_new', 'Re-enrollment receipt submitted', 'Paolo Santiago (9790) uploaded a re-enrollment receipt for MATH104.', 'admin/reenrollment?id=' . $reId, $this->ago('-50 minutes'));
    }

    // ------------------------------------------------------------------
    // Demo files (written straight into private storage)
    // ------------------------------------------------------------------

    private function storagePath(string $folder, string $ext): array
    {
        $relDir = $folder . '/' . date('Y/m');
        $absDir = FileStorage::root() . '/' . $relDir;
        if (! is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        return [$relDir . '/' . $name, $absDir . '/' . $name];
    }

    private function font(string $file): ?string
    {
        $path = 'C:/Windows/Fonts/' . $file;

        return is_file($path) ? $path : null;
    }

    private function writeReceiptImage(string $folder, string $name, string $number, string $or, float $amount, string $term, string $date, bool $blurry = false): array
    {
        [$rel, $abs] = $this->storagePath($folder, 'png');
        $w = 900;
        $h = 1150;
        $im    = imagecreatetruecolor($w, $h);
        $paper = imagecolorallocate($im, 252, 250, 243);
        $ink   = imagecolorallocate($im, 28, 43, 36);
        $muted = imagecolorallocate($im, 110, 122, 114);
        $green = imagecolorallocate($im, 23, 72, 50);
        $gold  = imagecolorallocate($im, 216, 169, 70);
        $red   = imagecolorallocate($im, 184, 65, 47);
        imagefilledrectangle($im, 0, 0, $w, $h, $paper);
        imagefilledrectangle($im, 60, 60, 150, 150, $gold);

        $regular = $this->font('arial.ttf');
        $bold    = $this->font('arialbd.ttf') ?? $regular;
        $text = function (int $size, int $x, int $y, int $color, string $s, ?string $font = null) use ($im, $regular) {
            $font ??= $regular;
            if ($font) {
                imagettftext($im, $size, 0, $x, $y, $color, $font, $s);
            } else {
                imagestring($im, 5, $x, $y - 14, $s, $color);
            }
        };

        $text(34, 88, 122, $green, 'N', $bold);
        $text(24, 180, 100, $ink, 'DFLCMCFI', $bold);
        $text(15, 180, 132, $muted, 'Office of the Treasurer');
        imagerectangle($im, 620, 70, 840, 115, $red);
        $text(16, 640, 100, $red, 'OFFICIAL RECEIPT', $bold);
        $text(22, 300, 230, $ink, 'OFFICIAL RECEIPT', $bold);
        $text(20, 330, 275, $green, $or, $bold);

        $y = 360;
        foreach ([['Received from', $name], ['Student number', $number], ['Semester', $term], ['Payment date', date('F j, Y', strtotime($date))], ['Particulars', 'Tuition and miscellaneous fees'], ['Amount paid', 'PHP ' . number_format($amount, 2)]] as [$k, $v]) {
            $text(17, 90, $y, $muted, $k);
            $text(19, 360, $y, $ink, $v, $k === 'Amount paid' ? $bold : null);
            imageline($im, 90, $y + 22, 810, $y + 22, imagecolorallocate($im, 225, 228, 220));
            $y += 80;
        }
        $text(15, 90, 930, $muted, 'Received by: Cashier 02');
        $text(15, 90, 965, $muted, 'This receipt is system-generated demo data.');
        imageline($im, 560, 950, 810, 950, $ink);
        $text(14, 600, 980, $muted, 'Authorized signature');

        if ($blurry) {
            for ($i = 0; $i < 60; $i++) {
                imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
            }
        }

        imagepng($im, $abs);
        imagedestroy($im);

        return ['file_path' => $rel, 'original_name' => 'receipt-' . strtolower($or) . '.png', 'mime_type' => 'image/png', 'file_size' => filesize($abs), 'file_hash' => hash_file('sha256', $abs)];
    }

    private function writeReceiptPdf(string $folder, string $name, string $number, string $or, float $amount, string $term, string $date): array
    {
        [$rel, $abs] = $this->storagePath($folder, 'pdf');
        $esc   = static fn (string $s) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'Windows-1252//TRANSLIT', $s));
        $lines = [
            [20, 60, 770, 'DFLCMCFI'], [11, 60, 752, 'Office of the Treasurer'], [16, 60, 700, 'OFFICIAL RECEIPT  ' . $or],
            [12, 60, 650, 'Received from:   ' . $name], [12, 60, 625, 'Student number:  ' . $number], [12, 60, 600, 'Semester:        ' . $term],
            [12, 60, 575, 'Payment date:    ' . date('F j, Y', strtotime($date))], [12, 60, 550, 'Particulars:     Tuition and miscellaneous fees'],
            [14, 60, 515, 'Amount paid:     PHP ' . number_format($amount, 2)], [9, 60, 440, 'This receipt is system-generated demo data.'],
        ];
        $stream = '';
        foreach ($lines as [$size, $x, $y, $s]) {
            $stream .= "BT /F1 {$size} Tf {$x} {$y} Td (" . $esc($s) . ") Tj ET\n";
        }
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf     = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
        file_put_contents($abs, $pdf);

        return ['file_path' => $rel, 'original_name' => 'receipt-' . strtolower($or) . '.pdf', 'mime_type' => 'application/pdf', 'file_size' => filesize($abs), 'file_hash' => hash_file('sha256', $abs)];
    }

    private function writeSignature(string $name): ?string
    {
        $font = $this->font('segoesc.ttf');
        if (! $font) {
            return null;
        }
        [$rel, $abs] = $this->storagePath('signatures', 'png');
        $im = imagecreatetruecolor(520, 160);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagettftext($im, 44, -4, 30, 95, imagecolorallocate($im, 23, 52, 110), $font, $name);
        imagepng($im, $abs);
        imagedestroy($im);

        return $rel;
    }
}
