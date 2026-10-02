<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolTermModel extends Model
{
    protected $table         = 'school_terms';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['school_year_id', 'semester', 'start_date', 'end_date', 'is_current', 'status'];

    public function withYear(): self
    {
        return $this->select('school_terms.*, school_years.name AS school_year')
            ->join('school_years', 'school_years.id = school_terms.school_year_id');
    }

    public function findWithYear(int $id): ?array
    {
        return $this->withYear()->where('school_terms.id', $id)->first();
    }

    public function current(): ?array
    {
        return $this->withYear()->where('school_terms.is_current', 1)->first();
    }

    /** All terms, newest first. */
    public function allWithYear(): array
    {
        return $this->withYear()->orderBy('school_terms.start_date', 'DESC')->findAll();
    }

    /**
     * The term immediately before the given one (by start date).
     */
    public function previous(array $term): ?array
    {
        return $this->withYear()
            ->where('school_terms.start_date <', $term['start_date'])
            ->orderBy('school_terms.start_date', 'DESC')
            ->first();
    }
}
