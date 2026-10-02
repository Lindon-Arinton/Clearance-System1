<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClearanceTables extends Migration
{
    private function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    /** Shared columns for uploaded payment receipts (tuition and re-enrollment). */
    private function receiptFields(): array
    {
        return [
            'attempt_no'           => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'or_number'            => ['type' => 'VARCHAR', 'constraint' => 40],
            'amount'               => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'payment_date'         => ['type' => 'DATE'],
            'file_path'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'file_size'            => ['type' => 'INT', 'unsigned' => true],
            'file_hash'            => ['type' => 'CHAR', 'constraint' => 64],
            'status'               => ['type' => 'ENUM', 'constraint' => ['submitted', 'under_review', 'approved', 'reupload_required'], 'default' => 'submitted'],
            'submitted_at'         => ['type' => 'DATETIME'],
            'review_started_at'    => ['type' => 'DATETIME', 'null' => true],
            'reviewed_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at'          => ['type' => 'DATETIME', 'null' => true],
            'reupload_reason_code' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'reupload_reason'      => ['type' => 'TEXT', 'null' => true],
        ];
    }

    public function up()
    {
        // clearances — the central record for one student in one term.
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference_no'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'student_id'          => ['type' => 'INT', 'unsigned' => true],
            'school_term_id'      => ['type' => 'INT', 'unsigned' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['awaiting_receipt', 'receipt_review', 'reupload_required', 'in_progress', 'completed'], 'default' => 'awaiting_receipt'],
            'started_at'          => ['type' => 'DATETIME'],
            'card_issued_at'      => ['type' => 'DATETIME', 'null' => true],
            'completed_at'        => ['type' => 'DATETIME', 'null' => true],
            'enrollment_eligible' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'eligible_at'         => ['type' => 'DATETIME', 'null' => true],
            'student_snapshot'    => ['type' => 'TEXT', 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference_no');
        $this->forge->addUniqueKey(['student_id', 'school_term_id']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('school_term_id', 'school_terms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('clearances');

        // tuition_receipts — every upload attempt is kept; the latest one is current.
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clearance_id' => ['type' => 'INT', 'unsigned' => true],
            'student_id'   => ['type' => 'INT', 'unsigned' => true],
            'checklist'    => ['type' => 'TEXT', 'null' => true],
        ] + $this->receiptFields() + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['clearance_id', 'status']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('clearance_id', 'clearances', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('tuition_receipts');

        // clearance_subjects — one row per enrolled subject on the clearance card.
        // Subject/teacher names are snapshotted so history is never rewritten.
        $this->forge->addField([
            'id'                        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clearance_id'              => ['type' => 'INT', 'unsigned' => true],
            'enrollment_id'             => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'subject_offering_id'       => ['type' => 'INT', 'unsigned' => true],
            'subject_id'                => ['type' => 'INT', 'unsigned' => true],
            'teacher_id'                => ['type' => 'INT', 'unsigned' => true],
            'subject_code'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'subject_title'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'units'                     => ['type' => 'DECIMAL', 'constraint' => '3,1'],
            'teacher_name'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'                    => ['type' => 'ENUM', 'constraint' => ['PENDING', 'PASSED', 'INC', 'FAILED'], 'default' => 'PENDING'],
            'reenrollment_status'       => ['type' => 'ENUM', 'constraint' => ['RE_ENROLLMENT_REQUIRED', 'RE_ENROLLMENT_RECEIPT_PENDING', 'RE_ENROLLMENT_APPROVED'], 'null' => true],
            'final_grade'               => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'remarks'                   => ['type' => 'TEXT', 'null' => true],
            'was_incomplete'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'decided_by'                => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decided_at'                => ['type' => 'DATETIME', 'null' => true],
            'signed_by'                 => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'signed_at'                 => ['type' => 'DATETIME', 'null' => true],
            'signature_hash'            => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'reenrollment_confirmed_at' => ['type' => 'DATETIME', 'null' => true],
            'is_resolved'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'resolved_at'               => ['type' => 'DATETIME', 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['clearance_id', 'subject_offering_id']);
        $this->forge->addKey(['teacher_id', 'status']);
        $this->forge->addKey('subject_offering_id');
        $this->forge->addForeignKey('clearance_id', 'clearances', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('enrollment_id', 'enrollments', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('subject_offering_id', 'subject_offerings', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('clearance_subjects');

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clearance_subject_id' => ['type' => 'INT', 'unsigned' => true],
            'description'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'instructions'        => ['type' => 'TEXT', 'null' => true],
            'due_date'            => ['type' => 'DATE', 'null' => true],
            'status'              => ['type' => 'ENUM', 'constraint' => ['pending', 'completed'], 'default' => 'pending'],
            'student_reported_at' => ['type' => 'DATETIME', 'null' => true],
            'student_note'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'          => ['type' => 'INT', 'unsigned' => true],
            'verified_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'verified_at'         => ['type' => 'DATETIME', 'null' => true],
            'verification_notes'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['clearance_subject_id', 'status']);
        $this->forge->addForeignKey('clearance_subject_id', 'clearance_subjects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inc_requirements');

        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'clearance_subject_id' => ['type' => 'INT', 'unsigned' => true],
            'student_id'           => ['type' => 'INT', 'unsigned' => true],
        ] + $this->receiptFields() + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['clearance_subject_id', 'status']);
        $this->forge->addKey('status');
        $this->forge->addForeignKey('clearance_subject_id', 'clearance_subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('reenrollment_receipts');
    }

    public function down()
    {
        foreach (['reenrollment_receipts', 'inc_requirements', 'clearance_subjects', 'tuition_receipts', 'clearances'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
