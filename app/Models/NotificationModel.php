<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table         = 'notifications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'type', 'title', 'message', 'link', 'read_at', 'created_at'];

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('read_at', null)->countAllResults();
    }

    public function latestFor(int $userId, int $limit = 6): array
    {
        return $this->where('user_id', $userId)->orderBy('id', 'DESC')->findAll($limit);
    }
}
