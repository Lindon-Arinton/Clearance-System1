<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsAndAudit extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'message'    => ['type' => 'VARCHAR', 'constraint' => 500],
            'link'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'read_at']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notifications');

        // audit_logs — append-only trail of every important status change.
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'user_role'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'entity_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'old_values'  => ['type' => 'TEXT', 'null' => true],
            'new_values'  => ['type' => 'TEXT', 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->createTable('audit_logs');
    }

    public function down()
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('notifications', true);
    }
}
