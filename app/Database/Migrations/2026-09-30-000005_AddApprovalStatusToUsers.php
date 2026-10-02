<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Self-registered accounts wait for registrar approval before they can sign in.
 */
class AddApprovalStatusToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'approval_status' => [
                'type'       => 'ENUM',
                'constraint' => ['approved', 'pending'],
                'default'    => 'approved',
                'after'      => 'is_active',
            ],
        ]);
        $this->db->query('ALTER TABLE users ADD INDEX users_approval_status (approval_status)');
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'approval_status');
    }
}
