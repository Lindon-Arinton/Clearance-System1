<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/teachers/new') ?>" class="btn btn-primary h-10 px-3 text-sm"><i class="bi bi-person-plus"></i>New teacher</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="surface overflow-hidden">
    <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        <div class="min-w-[240px] flex-1"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= esc($q) ?>" placeholder="Name, employee ID or department"></div>
        <button class="btn btn-light" type="submit"><i class="bi bi-search"></i>Search</button>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Teacher</th><th>Employee ID</th><th>Department</th><th>E-signature</th><th class="text-center">Subjects this term</th><th class="text-center">Awaiting decision</th><th>Account</th><th class="pe-5"></th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $t): ?>
                <tr>
                    <td class="ps-5"><a class="font-semibold text-ink hover:text-brand-700" href="<?= site_url('admin/teachers/' . $t['id']) ?>"><?= esc($t['display_name']) ?></a><div class="text-xs text-ink-muted"><?= esc($t['email'] ?? '') ?></div></td>
                    <td class="font-mono"><?= esc($t['employee_number']) ?></td>
                    <td><?= esc($t['department'] ?? '—') ?></td>
                    <td><?= $t['signature_path'] ? status_badge('SIGNED', 'Uploaded') : status_badge('AWAITING_SIGNATURE', 'Missing') ?></td>
                    <td class="text-center font-semibold"><?= (int) $t['current_subjects'] ?></td>
                    <td class="text-center"><?= (int) $t['awaiting'] ? '<span class="badge-status badge-yellow"><i class="bi bi-hourglass-split"></i>' . (int) $t['awaiting'] . '</span>' : '0' ?></td>
                    <td><?= status_badge((int) $t['is_active'] ? 'active' : 'inactive') ?></td>
                    <td class="pe-5 text-end"><a class="btn btn-light btn-sm" href="<?= site_url('admin/teachers/' . $t['id']) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($teachers === []): ?><tr><td colspan="8" class="py-10 text-center text-ink-muted">No teachers found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>
<?= $this->endSection() ?>
