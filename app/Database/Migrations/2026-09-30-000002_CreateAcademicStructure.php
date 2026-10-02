<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicStructure extends Migration
{
    private function timestamps(): array
    {
        return [
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'department' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('programs');

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'student_number' => ['type' => 'VARCHAR', 'constraint' => 20],
            'program_id'     => ['type' => 'INT', 'unsigned' => true],
            'year_level'     => ['type' => 'TINYINT', 'unsigned' => true],
            'section'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'contact_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'graduated'], 'default' => 'active'],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('student_number');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('program_id', 'programs', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('students');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true],
            'employee_number' => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Prof.'],
            'department'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'signature_path'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->addUniqueKey('employee_number');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teachers');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 9],
            'start_date' => ['type' => 'DATE'],
            'end_date'   => ['type' => 'DATE'],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('school_years');

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year_id' => ['type' => 'INT', 'unsigned' => true],
            'semester'       => ['type' => 'ENUM', 'constraint' => ['1st', '2nd', 'summer']],
            'start_date'     => ['type' => 'DATE'],
            'end_date'       => ['type' => 'DATE'],
            'is_current'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'         => ['type' => 'ENUM', 'constraint' => ['upcoming', 'open', 'closed'], 'default' => 'upcoming'],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['school_year_id', 'semester']);
        $this->forge->addKey('start_date');
        $this->forge->addForeignKey('school_year_id', 'school_years', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('school_terms');

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'units'       => ['type' => 'DECIMAL', 'constraint' => '3,1', 'default' => 3],
            'program_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('program_id', 'programs', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('subjects');

        // subject_offerings — a subject taught by a teacher in a given term/section.
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_term_id' => ['type' => 'INT', 'unsigned' => true],
            'subject_id'     => ['type' => 'INT', 'unsigned' => true],
            'teacher_id'     => ['type' => 'INT', 'unsigned' => true],
            'section'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'schedule'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['school_term_id', 'subject_id', 'section']);
        $this->forge->addKey('teacher_id');
        $this->forge->addForeignKey('school_term_id', 'school_terms', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('subject_offerings');

        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'student_id'          => ['type' => 'INT', 'unsigned' => true],
            'subject_offering_id' => ['type' => 'INT', 'unsigned' => true],
            'enrollment_type'     => ['type' => 'ENUM', 'constraint' => ['regular', 're_enrollment'], 'default' => 'regular'],
            'status'              => ['type' => 'ENUM', 'constraint' => ['enrolled', 'dropped'], 'default' => 'enrolled'],
            'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ] + $this->timestamps());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['student_id', 'subject_offering_id']);
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('subject_offering_id', 'subject_offerings', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('enrollments');
    }

    public function down()
    {
        foreach (['enrollments', 'subject_offerings', 'subjects', 'school_terms', 'school_years', 'teachers', 'students', 'programs'] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
