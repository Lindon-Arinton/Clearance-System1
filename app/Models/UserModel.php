<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['role', 'username', 'email', 'password_hash', 'first_name', 'middle_name', 'last_name', 'is_active', 'approval_status', 'must_change_password', 'failed_logins', 'locked_until', 'last_login_at'];
}
