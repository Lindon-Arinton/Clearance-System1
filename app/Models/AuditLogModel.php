<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table          = 'audit_logs';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = false;
    protected $allowedFields  = ['user_id', 'user_role', 'action', 'entity_type', 'entity_id', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at'];
}
