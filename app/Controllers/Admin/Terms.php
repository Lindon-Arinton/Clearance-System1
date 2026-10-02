<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SchoolTermModel;
use App\Models\SchoolYearModel;
use App\Services\AuditLogger;

/**
 * School years and semesters. Exactly one term is "current" at a time.
 */
class Terms extends BaseController
{
    public function index(): string
    {
        $terms = model(SchoolTermModel::class)->withYear()
            ->select("(SELECT COUNT(*) FROM clearances c WHERE c.school_term_id = school_terms.id) AS clearance_count,
                (SELECT COUNT(*) FROM clearances c WHERE c.school_term_id = school_terms.id AND c.status = 'completed') AS completed_count,
                (SELECT COUNT(*) FROM subject_offerings so WHERE so.school_term_id = school_terms.id) AS offering_count")
            ->orderBy('school_terms.start_date', 'DESC')->findAll();

        return $this->page('admin/terms/index', [
            'title'    => 'School terms',
            'subtitle' => 'School years and semesters. The current term drives every clearance screen.',
            'years'    => model(SchoolYearModel::class)->orderBy('start_date', 'DESC')->findAll(),
            'terms'    => $terms,
        ]);
    }

    public function storeYear()
    {
        $rules = [
            'name'       => 'required|regex_match[/^\d{4}-\d{4}$/]|is_unique[school_years.name]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date'   => 'required|valid_date[Y-m-d]',
        ];
        if (! $this->validate($rules, ['name' => ['regex_match' => 'School year must look like 2027-2028.']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($rules));
        [$a, $b] = explode('-', $data['name']);
        if ((int) $b !== (int) $a + 1 || $data['end_date'] <= $data['start_date']) {
            return redirect()->back()->withInput()->with('toast_error', 'Check the school year: it should span consecutive years and end after it starts.');
        }
        $id = model(SchoolYearModel::class)->insert($data);
        AuditLogger::log('school_year.created', 'school_year', (int) $id, null, $data, 'School year created');

        return redirect()->back()->with('toast_success', "School year {$data['name']} added.");
    }

    private function termRules(): array
    {
        return [
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date'   => 'required|valid_date[Y-m-d]',
            'status'     => 'required|in_list[upcoming,open,closed]',
        ];
    }

    public function store()
    {
        $rules = $this->termRules() + ['school_year_id' => 'required|is_not_unique[school_years.id]', 'semester' => 'required|in_list[1st,2nd,summer]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($rules));
        if ($data['end_date'] <= $data['start_date']) {
            return redirect()->back()->withInput()->with('toast_error', 'The term must end after it starts.');
        }
        if (model(SchoolTermModel::class)->where('school_year_id', $data['school_year_id'])->where('semester', $data['semester'])->first()) {
            return redirect()->back()->withInput()->with('toast_error', 'That semester already exists for the school year.');
        }
        $id = model(SchoolTermModel::class)->insert($data + ['is_current' => 0]);
        AuditLogger::log('term.created', 'school_term', (int) $id, null, $data, 'School term created');

        return redirect()->back()->with('toast_success', 'Term added.');
    }

    public function update(int $id)
    {
        $term = model(SchoolTermModel::class)->find($id) ?? $this->notFound();
        if (! $this->validate($this->termRules())) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($this->termRules()));
        if ($data['end_date'] <= $data['start_date']) {
            return redirect()->back()->with('toast_error', 'The term must end after it starts.');
        }
        if ((int) $term['is_current'] === 1 && $data['status'] === 'closed') {
            return redirect()->back()->with('toast_error', 'Make another term current before closing this one.');
        }
        model(SchoolTermModel::class)->update($id, $data);
        AuditLogger::log('term.updated', 'school_term', $id, $term, $data, 'School term updated');

        return redirect()->back()->with('toast_success', 'Term updated.');
    }

    public function makeCurrent(int $id)
    {
        $term = model(SchoolTermModel::class)->findWithYear($id) ?? $this->notFound();
        if ($term['status'] === 'closed') {
            return redirect()->back()->with('toast_error', 'A closed term cannot be made current.');
        }

        $db = db_connect();
        $db->transException(true)->transStart();
        $db->table('school_terms')->update(['is_current' => 0]);
        $db->table('school_terms')->where('id', $id)->update(['is_current' => 1, 'status' => 'open']);
        AuditLogger::log('term.made_current', 'school_term', $id, null, ['is_current' => 1], term_label($term) . ' set as current term');
        $db->transComplete();

        return redirect()->back()->with('toast_success', term_label($term) . ' is now the current term. Previous clearances remain in history.');
    }
}
