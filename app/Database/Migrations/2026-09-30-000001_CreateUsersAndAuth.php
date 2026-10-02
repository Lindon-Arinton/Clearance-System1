<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersAndAuth extends Migration
{
    public function up()
    {
        // users — one login account per person; role decides the workspace.
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role'                 => ['type' => 'ENUM', 'constraint' => ['student', 'teacher', 'admin']],
            'username'             => ['type' => 'VARCHAR', 'constraint' => 50],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'password_hash'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'middle_name'          => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'last_name'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'is_active'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'must_change_password' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'failed_logins'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'locked_until'         => ['type' => 'DATETIME', 'null' => true],
            'last_login_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->createTable('users');

        // auth_tokens — "keep me signed in" (selector + hashed validator).
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'selector'       => ['type' => 'CHAR', 'constraint' => 24],
            'validator_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at'     => ['type' => 'DATETIME'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('selector');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('auth_tokens');

        // settings — school profile values editable by admins.
        $this->forge->addField([
            'key'        => ['type' => 'VARCHAR', 'constraint' => 60],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('settings');
    }

    public function down()
    {
        $this->forge->dropTable('settings', true);
        $this->forge->dropTable('auth_tokens', true);
        $this->forge->dropTable('users', true);
    }
}
