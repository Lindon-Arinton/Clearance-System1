<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Students sign up with a personal email only; the registrar assigns the
 * student number when approving the account. Everyone signs in by email.
 */
class AllowPendingStudentsWithoutNumber extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('users', [
            'username' => ['name' => 'username', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ]);
        $this->forge->modifyColumn('students', [
            'student_number' => ['name' => 'student_number', 'type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('users', [
            'username' => ['name' => 'username', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
        ]);
        $this->forge->modifyColumn('students', [
            'student_number' => ['name' => 'student_number', 'type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
        ]);
    }
}
