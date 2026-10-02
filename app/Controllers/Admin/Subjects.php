<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProgramModel;
use App\Models\SubjectModel;
use App\Services\AuditLogger;

class Subjects extends BaseController
{
    private function rules(int $id = 0): array
    {
        return [
            'code'        => 'required|regex_match[/^[A-Za-z]{2,6}\s?\d{2,4}[A-Za-z]?$/]|is_unique[subjects.code,id,' . $id . ']',
            'title'       => 'required|max_length[150]',
            'units'       => 'required|decimal|greater_than[0]|less_than_equal_to[10]',
            'program_id'  => 'permit_empty|is_not_unique[programs.id]',
            'description' => 'permit_empty|max_length[1000]',
        ];
    }

    private function data(): array
    {
        return [
            'code'        => strtoupper(str_replace(' ', '', (string) $this->request->getPost('code'))),
            'title'       => trim((string) $this->request->getPost('title')),
            'units'       => (string) $this->request->getPost('units'),
            'program_id'  => $this->request->getPost('program_id') ?: null,
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'is_active'   => $this->postBool('is_active') ? 1 : 0,
        ];
    }

    public function index(): string
    {
        $q     = trim((string) $this->request->getGet('q'));
        $model = model(SubjectModel::class)->select('subjects.*, programs.code AS program_code,
                (SELECT COUNT(*) FROM subject_offerings so WHERE so.subject_id = subjects.id) AS offering_count')
            ->join('programs', 'programs.id = subjects.program_id', 'left');
        if ($q !== '') {
            $model->groupStart()->like('subjects.code', $q)->orLike('subjects.title', $q)->groupEnd();
        }

        return $this->page('admin/subjects/index', [
            'title'    => 'Subjects',
            'subtitle' => 'The subject catalogue. Assign teachers per term under Offerings.',
            'subjects' => $model->orderBy('subjects.code')->paginate(20),
            'pager'    => model(SubjectModel::class)->pager,
            'programs' => model(ProgramModel::class)->orderBy('code')->findAll(),
            'q'        => $q,
        ]);
    }

    public function store()
    {
        if (! $this->validateData($this->data(), $this->rules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $id = model(SubjectModel::class)->insert($this->data() + ['is_active' => 1]);
        AuditLogger::log('subject.created', 'subject', (int) $id, null, $this->data(), 'Subject created');

        return redirect()->back()->with('toast_success', 'Subject added.');
    }

    public function update(int $id)
    {
        $subject = model(SubjectModel::class)->find($id) ?? $this->notFound();
        if (! $this->validateData($this->data(), $this->rules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        model(SubjectModel::class)->update($id, $this->data());
        AuditLogger::log('subject.updated', 'subject', $id, $subject, $this->data(), 'Subject updated');

        return redirect()->back()->with('toast_success', 'Subject updated. Existing clearance records keep the name they were issued with.');
    }
}
