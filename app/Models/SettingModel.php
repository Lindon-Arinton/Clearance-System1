<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'key';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['key', 'value', 'updated_at'];

    public const DEFAULTS = [
        'school_name'       => 'Dr. Francisco L. Calingasan Memorial Colleges Foundation, Inc.',
        'school_short_name' => 'DFLCMCFI',
        'office_name'       => 'College Registrar',
        'school_address'    => 'Nasugbu / Tuy, Batangas',
        'registrar_name'    => '',
        'max_upload_mb'     => '5',
    ];

    private static ?array $cache = null;

    /** @return array<string, string> */
    public function allSettings(): array
    {
        if (self::$cache === null) {
            $rows = [];
            try {
                foreach ($this->findAll() as $row) {
                    $rows[$row['key']] = (string) $row['value'];
                }
            } catch (\Throwable) {
                // Table not migrated yet — fall back to defaults.
            }
            self::$cache = array_merge(self::DEFAULTS, $rows);
        }

        return self::$cache;
    }

    public function put(string $key, string $value): void
    {
        $this->db->table($this->table)->replace(['key' => $key, 'value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
        self::$cache = null;
    }
}
