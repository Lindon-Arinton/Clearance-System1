<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/students/new') ?>" class="btn btn-primary h-10 px-3 text-sm"><i class="bi bi-person-plus"></i>New student</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php if ($pendingCount > 0 && $status !== 'pending'): ?>
    <a href="<?= site_url('admin/students?status=pending') ?>" class="mb-5 flex items-center gap-3 rounded-xl border border-[#f0dcc0] bg-[#fdf6ec] px-4 py-3 text-sm text-[#7a4a12] hover:bg-[#fbefdd]">
        <i class="bi bi-person-exclamation text-lg" aria-hidden="true"></i>
        <span class="flex-1"><strong><?= $pendingCount ?> student sign-up<?= $pendingCount > 1 ? 's' : '' ?></strong> waiting for your approval.</span>
        <span class="font-semibold">Review <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
    </a>
<?php endif; ?>
<section class="surface overflow-hidden">
    <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        <div class="min-w-[220px] flex-1"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= esc($q) ?>" placeholder="Name, student number or email"></div>
        <div><label class="form-label" for="program">Program</label>
            <select class="form-select" id="program" name="program"><option value="">All programs</option>
                <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>" <?= $program === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['code']) ?></option><?php endforeach; ?>
            </select></div>
        <div><label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status"><option value="">Any</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending approval (<?= $pendingCount ?>)</option>
                <?php foreach (['active', 'inactive', 'graduated'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select></div>
        <button class="btn btn-light" type="submit"><i class="bi bi-funnel"></i>Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Student</th><th>Program</th><th>Year</th><th>Account</th><th><?= $term ? esc(term_label($term, true)) : 'Current' ?> clearance</th><th class="pe-5"></th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td class="ps-5"><a href="<?= site_url('admin/students/' . $s['id']) ?>" class="font-semibold text-ink hover:text-brand-700"><?= esc(person_name($s, true)) ?></a><div class="font-mono text-xs text-ink-muted"><?= esc($s['student_number'] ?? 'No student number yet') ?> · <?= esc($s['email'] ?? '') ?></div></td>
                    <td><?= esc($s['program_code']) ?></td>
                    <td><?= year_level_label($s['year_level']) ?><?= $s['section'] ? ' · ' . esc($s['section']) : '' ?></td>
                    <td><?= $s['approval_status'] === 'pending' ? status_badge('pending_approval') : status_badge($s['status']) ?></td>
                    <td><?= status_badge($s['clearance_status'] ?? 'not_started') ?></td>
                    <td class="pe-5 text-end"><a href="<?= site_url('admin/students/' . $s['id']) ?>" class="btn btn-light btn-sm">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($students === []): ?><tr><td colspan="6" class="py-10 text-center text-ink-muted">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>
<?= $this->endSection() ?>
