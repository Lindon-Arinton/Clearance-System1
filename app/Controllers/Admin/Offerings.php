<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SchoolTermModel;
use App\Models\SubjectModel;
use App\Models\SubjectOfferingModel;
use App\Models\TeacherModel;
use App\Services\AuditLogger;

/**
 * Subject offerings: which teacher handles which subject/section in a term.
 */
class Offerings extends BaseController
{
    public function index(): string
    {
        $terms  = model(SchoolTermModel::class)->allWithYear();
        $termId = (int) ($this->request->getGet('term') ?: (model(SchoolTermModel::class)->current()['id'] ?? ($terms[0]['id'] ?? 0)));

        $offerings = db_connect()->table('subject_offerings so')
            ->select("so.*, s.code, s.title, CONCAT(t.title, ' ', u.first_name, ' ', u.last_name) AS teacher_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.subject_offering_id = so.id AND e.status = 'enrolled') AS students,
                (SELECT COUNT(*) FROM clearance_subjects cs WHERE cs.subject_offering_id = so.id AND cs.status <> 'PENDING') AS decided")
            ->join('subjects s', 's.id = so.subject_id')->join('teachers t', 't.id = so.teacher_id')->join('users u', 'u.id = t.user_id')
            ->where('so.school_term_id', $termId)->orderBy('s.code')->get()->getResultArray();

        return $this->page('admin/subjects/offerings', [
            'title'     => 'Subject offerings',
            'eyebrow'   => 'Subjects',
            'subtitle'  => 'Assign a teacher and section for each subject in a term.',
            'terms'     => $terms,
            'termId'    => $termId,
            'offerings' => $offerings,
            'subjects'  => model(SubjectModel::class)->where('is_active', 1)->orderBy('code')->findAll(),
            'teachers'  => model(TeacherModel::class)->options(),
        ]);
    }

    public function store()
    {
        $rules = [
            'school_term_id' => 'required|is_not_unique[school_terms.id]',
            'subject_id'     => 'required|is_not_unique[subjects.id]',
            'teacher_id'     => 'required|is_not_unique[teachers.id]',
            'section'        => 'required|max_length[20]',
            'schedule'       => 'permit_empty|max_length[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($rules));
        $dup  = model(SubjectOfferingModel::class)->where(['school_term_id' => $data['school_term_id'], 'subject_id' => $data['subject_id'], 'section' => $data['section']])->first();
        if ($dup) {
            return redirect()->back()->withInput()->with('toast_error', 'That subject and section already exist for this term.');
        }
        $id = model(SubjectOfferingModel::class)->insert($data);
        AuditLogger::log('offering.created', 'subject_offering', (int) $id, null, $data, 'Subject offering created');

        return redirect()->to(site_url('admin/offerings?term=' . $data['school_term_id']))->with('toast_success', 'Offering added.');
    }

    public function update(int $id)
    {
        $offering = model(SubjectOfferingModel::class)->find($id) ?? $this->notFound();
        $rules    = ['teacher_id' => 'required|is_not_unique[teachers.id]', 'section' => 'required|max_length[20]', 'schedule' => 'permit_empty|max_length[100]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }
        $data = $this->request->getPost(array_keys($rules));

        $db = db_connect();
        $db->transException(true)->transStart();
        model(SubjectOfferingModel::class)->update($id, $data);

        // Reassign undecided clearance rows to the new teacher; decided rows keep their original teacher.
        if ((int) $data['teacher_id'] !== (int) $offering['teacher_id']) {
            $teacher = model(TeacherModel::class)->findDetailed((int) $data['teacher_id']);
            $db->table('clearance_subjects cs')
                ->where('cs.subject_offering_id', $id)
                ->whereIn('cs.status', ['PENDING', 'INC'])
                ->where("cs.clearance_id IN (SELECT id FROM clearances WHERE status = 'in_progress')", null, false)
                ->update(['teacher_id' => $data['teacher_id'], 'teacher_name' => $teacher['display_name']]);
        }
        AuditLogger::log('offering.updated', 'subject_offering', $id, $offering, $data, 'Subject offering updated');
        $db->transComplete();

        return redirect()->back()->with('toast_success', 'Offering updated.');
    }

    public function delete(int $id)
    {
        $offering = model(SubjectOfferingModel::class)->find($id) ?? $this->notFound();
        $used     = db_connect()->table('enrollments')->where('subject_offering_id', $id)->countAllResults();
        if ($used > 0) {
            return redirect()->back()->with('toast_error', 'This offering has enrollment records and cannot be deleted.');
        }
        model(SubjectOfferingModel::class)->delete($id);
        AuditLogger::log('offering.deleted', 'subject_offering', $id, $offering, null, 'Subject offering deleted');

        return redirect()->back()->with('toast_success', 'Offering removed.');
    }
}
