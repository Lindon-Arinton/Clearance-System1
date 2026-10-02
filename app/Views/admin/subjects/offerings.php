<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="mb-4 flex gap-6 border-b border-line">
    <a href="<?= site_url('admin/subjects') ?>" class="tab-link">Catalogue</a>
    <a href="<?= site_url('admin/offerings') ?>" class="tab-link active">Offerings &amp; teacher assignment</a>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
    <section class="surface overflow-hidden">
        <form method="get" class="flex flex-wrap items-center gap-3 border-b border-line p-4">
            <label class="form-label mb-0" for="term">Term</label>
            <select class="form-select w-auto" id="term" name="term" onchange="this.form.submit()">
                <?php foreach ($terms as $t): ?><option value="<?= $t['id'] ?>" <?= (int) $t['id'] === $termId ? 'selected' : '' ?>><?= esc(term_label($t)) ?><?= (int) $t['is_current'] ? ' (current)' : '' ?></option><?php endforeach; ?>
            </select>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th class="ps-5">Subject</th><th>Teacher · Section · Schedule</th><th class="text-center">Students</th><th class="pe-5"></th></tr></thead>
                <tbody>
                <?php foreach ($offerings as $o): ?>
                    <tr>
                        <td class="ps-5"><div class="font-semibold"><?= esc($o['code']) ?></div><div class="text-xs text-ink-muted"><?= esc($o['title']) ?></div></td>
                        <td>
                            <form action="<?= site_url('admin/offerings/' . $o['id']) ?>" method="post" class="flex flex-wrap items-center gap-2"
                                  data-confirm="Save this assignment? Undecided clearance rows move to the selected teacher; decided rows keep their original teacher." data-confirm-button="Save">
                                <?= csrf_field() ?>
                                <label class="visually-hidden" for="t<?= $o['id'] ?>">Teacher</label>
                                <select class="form-select form-select-sm w-auto" id="t<?= $o['id'] ?>" name="teacher_id">
                                    <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= (int) $t['id'] === (int) $o['teacher_id'] ? 'selected' : '' ?>><?= esc($t['display_name']) ?></option><?php endforeach; ?>
                                </select>
                                <input class="form-control form-control-sm w-16" name="section" value="<?= esc($o['section']) ?>" aria-label="Section" required>
                                <input class="form-control form-control-sm w-36" name="schedule" value="<?= esc($o['schedule'] ?? '') ?>" aria-label="Schedule" placeholder="Schedule">
                                <button class="btn btn-light btn-sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td class="text-center font-semibold"><?= (int) $o['students'] ?></td>
                        <td class="pe-5 text-end">
                            <?php if ((int) $o['students'] === 0): ?>
                                <form action="<?= site_url('admin/offerings/' . $o['id'] . '/delete') ?>" method="post" data-confirm="Remove this offering?" data-confirm-variant="danger" data-confirm-button="Remove">
                                    <?= csrf_field() ?><button class="btn btn-light btn-sm text-[#a3362a]" type="submit" aria-label="Remove offering"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($offerings === []): ?><tr><td colspan="4" class="py-10 text-center text-ink-muted">No offerings for this term yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="surface h-fit p-5">
        <div class="eyebrow">Add offering</div>
        <h2 class="section-title mt-1">Assign a teacher</h2>
        <form action="<?= site_url('admin/offerings') ?>" method="post" class="mt-4 flex flex-col gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="school_term_id" value="<?= $termId ?>">
            <div><label class="form-label" for="subject_id">Subject</label>
                <select class="form-select" id="subject_id" name="subject_id" required><option value="">Choose…</option>
                    <?php foreach ($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['code'] . ' · ' . $s['title']) ?></option><?php endforeach; ?></select></div>
            <div><label class="form-label" for="teacher_id">Teacher</label>
                <select class="form-select" id="teacher_id" name="teacher_id" required><option value="">Choose…</option>
                    <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= esc($t['display_name']) ?></option><?php endforeach; ?></select></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="form-label" for="section">Section</label><input class="form-control" id="section" name="section" value="A" required maxlength="20"></div>
                <div><label class="form-label" for="schedule">Schedule</label><input class="form-control" id="schedule" name="schedule" maxlength="100" placeholder="MWF 9:00"></div>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i>Add offering</button>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
