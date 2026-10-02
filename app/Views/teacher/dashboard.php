<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('teacher/clearance') ?>" class="btn btn-primary h-10 px-3 text-sm"><i class="bi bi-clipboard-check"></i>Open clearance reviews</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-5">
    <div class="eyebrow"><?= $term ? esc(term_label($term)) : 'No active term' ?></div>
    <p class="mt-1 text-ink-soft">Welcome, <strong><?= esc($authProfile['title'] . ' ' . person_name($authUser)) ?></strong>. <?= $totals['pending'] + $totals['awaiting_sign'] + $totals['reported'] > 0 ? 'Some students are waiting on you.' : 'You are all caught up.' ?></p>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-card"><span class="stat-label">Needs your action</span><span class="stat-value"><?= $totals['pending'] + $totals['awaiting_sign'] + $totals['reported'] ?></span><span class="stat-hint"><?= $totals['pending'] ?> decisions · <?= $totals['awaiting_sign'] ?> signatures · <?= $totals['reported'] ?> INC reports</span></div>
    <div class="stat-card"><span class="stat-label">Passed &amp; signed</span><span class="stat-value"><?= $totals['passed'] ?></span><span class="stat-hint">Across <?= count($offerings) ?> subject(s)</span></div>
    <div class="stat-card"><span class="stat-label">INC</span><span class="stat-value text-[#a4521a]"><?= $totals['inc'] ?></span><span class="stat-hint">Awaiting requirements</span></div>
    <div class="stat-card"><span class="stat-label">Failed</span><span class="stat-value text-[#a3362a]"><?= $totals['failed'] ?></span><span class="stat-hint">In re-enrollment</span></div>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_400px]">
    <section>
        <h2 class="section-title mb-3">My subjects</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($offerings as $o): ?>
                <a href="<?= site_url('teacher/subjects/' . $o['id']) ?>" class="surface block p-5 text-ink transition hover:-translate-y-0.5 hover:shadow-lift">
                    <div class="flex items-start justify-between gap-2">
                        <div><div class="eyebrow"><?= esc($o['code']) ?> · Section <?= esc($o['section']) ?></div><div class="mt-1 text-lg font-bold"><?= esc($o['title']) ?></div></div>
                        <?php if ($o['pending'] + $o['awaiting_sign'] + $o['reported'] > 0): ?><span class="badge-status badge-blue"><i class="bi bi-bell-fill" aria-hidden="true"></i><?= $o['pending'] + $o['awaiting_sign'] + $o['reported'] ?> to do</span><?php endif; ?>
                    </div>
                    <dl class="mb-0 mt-4 grid grid-cols-4 gap-2 text-center">
                        <div class="rounded-lg bg-[#f6f8f4] py-2"><dt class="text-[11px] font-semibold text-ink-muted">Students</dt><dd class="mb-0 text-lg font-bold"><?= (int) $o['students'] ?></dd></div>
                        <div class="rounded-lg bg-[#eef7f1] py-2"><dt class="text-[11px] font-semibold text-[#1f6a3f]">Passed</dt><dd class="mb-0 text-lg font-bold text-[#1f6a3f]"><?= (int) $o['passed'] ?></dd></div>
                        <div class="rounded-lg bg-[#fdf1e6] py-2"><dt class="text-[11px] font-semibold text-[#a4521a]">INC</dt><dd class="mb-0 text-lg font-bold text-[#a4521a]"><?= (int) $o['inc'] ?></dd></div>
                        <div class="rounded-lg bg-[#fbeae6] py-2"><dt class="text-[11px] font-semibold text-[#a3362a]">Failed</dt><dd class="mb-0 text-lg font-bold text-[#a3362a]"><?= (int) $o['failed'] ?></dd></div>
                    </dl>
                    <?php $done = (int) $o['passed'] + (int) $o['failed']; $pct = $o['on_cards'] ? round($done / $o['on_cards'] * 100) : 0; ?>
                    <div class="mt-4 flex items-center gap-3 text-xs text-ink-muted">
                        <div class="progress-track flex-1"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div><?= $done ?>/<?= (int) $o['on_cards'] ?> decided
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if ($offerings === []): ?><div class="surface empty-state md:col-span-2"><i class="bi bi-journal-x"></i>No subjects assigned to you this term.</div><?php endif; ?>
        </div>
    </section>

    <section class="surface h-fit overflow-hidden">
        <div class="border-b border-line p-5">
            <div class="eyebrow">Queue</div>
            <h2 class="section-title mt-1">Students requiring action</h2>
        </div>
        <ul class="m-0 list-none p-0">
            <?php foreach ($queue as $q): ?>
                <li>
                    <a class="list-row" href="<?= site_url('teacher/subjects/' . $q['subject_offering_id']) ?>?cs=<?= $q['id'] ?>">
                        <span class="avatar h-9 w-9 text-xs"><?= esc(initials(person_name($q))) ?></span>
                        <span class="min-w-0 flex-1"><span class="block truncate font-semibold"><?= esc(person_name($q)) ?></span><span class="block text-xs text-ink-muted"><?= esc($q['subject_code']) ?> · <?= esc($q['student_number']) ?></span></span>
                        <?php if ($q['status'] === 'PENDING'): ?><?= status_badge('PENDING', 'Decide') ?>
                        <?php elseif ($q['status'] === 'PASSED'): ?><?= status_badge('AWAITING_SIGNATURE', 'Sign') ?>
                        <?php else: ?><?= status_badge('reported', 'Verify INC') ?><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if ($queue === []): ?><li class="empty-state"><i class="bi bi-check2-all"></i>Nothing waiting on you.</li><?php endif; ?>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
