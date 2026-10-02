<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<section class="surface overflow-hidden">
    <div class="border-b border-line p-5">
        <div class="eyebrow">Clearance history</div>
        <h2 class="section-title mt-1">Your clearances</h2>
        <p class="mt-1 text-sm text-ink-muted">Completed clearances are never overwritten when a new semester begins.</p>
    </div>
    <ul class="m-0 list-none p-0">
        <?php foreach ($clearances as $c): ?>
            <li class="flex flex-wrap items-center gap-4 border-b border-line px-5 py-4 last:border-b-0">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl <?= $c['status'] === 'completed' ? 'bg-[#e3f1e7] text-[#1f6a3f]' : 'bg-[#e2edf7] text-[#285f94]' ?>">
                    <i class="bi <?= $c['status'] === 'completed' ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?> text-lg" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold"><?= esc(term_label($c)) ?></div>
                    <div class="text-xs text-ink-muted"><?= esc($c['reference_no']) ?> · <?= (int) $c['subject_count'] ?> subjects · <?= $c['completed_at'] ? 'Completed ' . fmt_date($c['completed_at']) : 'Started ' . fmt_date($c['started_at']) ?></div>
                </div>
                <?= status_badge($c['status'] === 'in_progress' ? 'incomplete' : $c['status'], $c['status'] === 'in_progress' ? 'In progress' : null) ?>
                <a class="btn btn-light btn-sm" href="<?= site_url('student/history/' . $c['id']) ?>">View <i class="bi bi-arrow-right"></i></a>
            </li>
        <?php endforeach; ?>
        <?php if ($clearances === []): ?>
            <li class="empty-state"><i class="bi bi-clock-history"></i>No clearances yet.</li>
        <?php endif; ?>
    </ul>
</section>
<?= $this->endSection() ?>
