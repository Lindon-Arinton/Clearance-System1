<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/teachers/' . $teacher['id'] . '/edit') ?>" class="btn btn-light h-10 px-3 text-sm"><i class="bi bi-pencil"></i>Edit</a>
<form action="<?= site_url('admin/teachers/' . $teacher['id'] . '/reset-password') ?>" method="post" data-confirm="Reset this teacher's password? A new temporary password will be shown once." data-confirm-button="Reset password">
    <?= csrf_field() ?><button class="btn btn-light h-10 px-3 text-sm" type="submit"><i class="bi bi-key"></i>Reset password</button>
</form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/temp_password') ?>
<div class="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
    <section class="surface h-fit p-5">
        <div class="flex items-center gap-3">
            <span class="avatar h-12 w-12"><?= esc(initials($teacher['display_name'])) ?></span>
            <div><div class="text-lg font-bold"><?= esc($teacher['display_name']) ?></div><div class="font-mono text-sm text-ink-muted"><?= esc($teacher['employee_number']) ?></div></div>
        </div>
        <dl class="mb-0 mt-5 flex flex-col gap-3 text-sm">
            <div><dt class="kv-label">Department</dt><dd class="mb-0 font-semibold"><?= esc($teacher['department'] ?: '—') ?></dd></div>
            <div><dt class="kv-label">Email</dt><dd class="mb-0 break-all"><?= esc($teacher['email'] ?: '—') ?></dd></div>
            <div><dt class="kv-label">Account</dt><dd class="mb-0 mt-1"><?= status_badge((int) $teacher['is_active'] ? 'active' : 'inactive') ?></dd></div>
            <div><dt class="kv-label">E-signature</dt><dd class="mb-0 mt-1">
                <?php if ($teacher['signature_path']): ?><img src="<?= site_url('files/signature/' . $teacher['id']) ?>" alt="Signature" class="h-12 rounded border border-line bg-white p-1"><?php else: ?><span class="text-[#8a6310]">Not uploaded yet — the teacher is asked to upload it at first sign-in and cannot approve students until then.</span><?php endif; ?>
            </dd></div>
        </dl>
    </section>

    <section class="surface overflow-hidden">
        <div class="flex items-center justify-between border-b border-line p-5">
            <div><div class="eyebrow">Teaching load</div><h2 class="section-title mt-1">Assigned subjects</h2></div>
            <a class="btn btn-soft btn-sm" href="<?= site_url('admin/offerings') ?>">Manage offerings</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th class="ps-5">Subject</th><th>Term</th><th class="text-center">Students</th><th class="text-center">Pending</th><th class="text-center">Passed</th><th class="text-center">INC</th><th class="pe-5 text-center">Failed</th></tr></thead>
                <tbody>
                <?php foreach ($offerings as $o): ?>
                    <tr>
                        <td class="ps-5"><div class="font-semibold"><?= esc($o['code']) ?> · <?= esc($o['title']) ?></div><div class="text-xs text-ink-muted">Section <?= esc($o['section']) ?></div></td>
                        <td><?= esc(term_label($o, true)) ?><?= (int) $o['is_current'] ? ' <span class="badge-status badge-green">Current</span>' : '' ?></td>
                        <td class="text-center"><?= (int) $o['students'] ?></td>
                        <td class="text-center"><?= (int) $o['pending'] ?></td>
                        <td class="text-center text-[#1f6a3f]"><?= (int) $o['passed'] ?></td>
                        <td class="text-center text-[#a4521a]"><?= (int) $o['inc'] ?></td>
                        <td class="pe-5 text-center text-[#a3362a]"><?= (int) $o['failed'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($offerings === []): ?><tr><td colspan="7" class="py-8 text-center text-ink-muted">No subjects assigned.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
