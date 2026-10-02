<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthTokenModel extends Model
{
    protected $table          = 'auth_tokens';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = false;
    protected $allowedFields  = ['user_id', 'selector', 'validator_hash', 'expires_at', 'created_at'];
}
