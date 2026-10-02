<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/students/' . $bundle['clearance']['student_id']) ?>" class="btn btn-light h-10 px-3 text-sm"><i class="bi bi-person"></i>Student record</a>
<button type="button" class="btn btn-soft h-10 px-3 text-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/clearance_record', ['bundle' => $bundle, 'reenrollments' => $reenrollments, 'requirements' => $requirements]) ?>

<?php if ($bundle['blockers'] !== []): ?>
    <section class="surface mt-6 p-5">
        <div class="eyebrow">Why this clearance is not complete</div>
        <ul class="mb-0 mt-3 flex list-none flex-col gap-2 p-0">
            <?php foreach ($bundle['blockers'] as $b): ?>
                <li class="flex flex-wrap items-center gap-3 rounded-lg border border-line px-3 py-2 text-sm"><?= status_badge($b['status']) ?><span class="font-semibold"><?= esc($b['title']) ?></span><span class="text-ink-muted"><?= esc($b['detail']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<section class="surface no-print mt-6 p-5">
    <div class="eyebrow">Audit trail</div>
    <h2 class="section-title mt-1">Status changes</h2>
    <ol class="mb-0 mt-4 flex list-none flex-col gap-3 p-0">
        <?php foreach ($timeline as $t): ?>
            <li class="flex gap-3 text-sm">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-400" aria-hidden="true"></span>
                <span class="min-w-0"><span class="font-semibold"><?= esc($t['description'] ?: $t['action']) ?></span>
                    <span class="block text-xs text-ink-muted"><?= esc(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')) ?: 'System') ?> (<?= esc($t['user_role'] ?? '—') ?>) · <?= fmt_datetime($t['created_at']) ?> · <?= esc($t['action']) ?></span></span>
            </li>
        <?php endforeach; ?>
        <?php if ($timeline === []): ?><li class="text-sm text-ink-muted">No recorded changes (seeded record).</li><?php endif; ?>
    </ol>
</section>
<?= $this->endSection() ?>
