<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/receipts') ?>" class="btn btn-primary h-10 px-3 text-sm"><i class="bi bi-receipt"></i>Validate receipts</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$inProgress = $summary['awaiting_receipt'] + $summary['receipt_review'] + $summary['reupload_required'] + $summary['in_progress'];
$pending    = $receipts['submitted'] + $receipts['under_review'];
?>
<?php if ($pendingSignups > 0): ?>
    <a href="<?= site_url('admin/students?status=pending') ?>" class="mb-5 flex items-center gap-3 rounded-xl border border-[#f0dcc0] bg-[#fdf6ec] px-4 py-3 text-sm text-[#7a4a12] hover:bg-[#fbefdd]">
        <i class="bi bi-person-exclamation text-lg" aria-hidden="true"></i>
        <span class="flex-1"><strong><?= $pendingSignups ?> student sign-up<?= $pendingSignups > 1 ? 's' : '' ?></strong> waiting for approval.</span>
        <span class="font-semibold">Review <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
    </a>
<?php endif; ?>
<div class="eyebrow mb-4"><?= $term ? esc(term_label($term)) : 'No active term' ?> · Registrar desk · <?= date('j F Y') ?></div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <a href="<?= site_url('admin/students') ?>" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">Students</span><span class="stat-value"><?= number_format($students) ?></span><span class="stat-hint">Active accounts</span></a>
    <a href="<?= site_url('admin/receipts') ?>" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">Pending receipts</span><span class="stat-value text-[#285f94]"><?= $pending ?></span><span class="stat-hint"><?= $reenrollPending ?> re-enrollment receipt(s) too</span></a>
    <a href="<?= site_url('admin/receipts?filter=reupload') ?>" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">Re-upload required</span><span class="stat-value text-[#a4521a]"><?= $receipts['reupload_required'] ?></span><span class="stat-hint">Waiting on students</span></a>
    <a href="<?= site_url('admin/clearances?status=in_progress') ?>" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">In progress</span><span class="stat-value"><?= $inProgress ?></span><span class="stat-hint"><?= $summary['in_progress'] ?> with active cards</span></a>
    <a href="<?= site_url('admin/clearances?status=completed') ?>" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">Completed</span><span class="stat-value text-[#1f6a3f]"><?= $summary['completed'] ?></span><span class="stat-hint">This term</span></a>
    <a href="<?= site_url('admin/reports') ?>#eligibility" class="stat-card text-ink hover:shadow-lift"><span class="stat-label">Not yet eligible</span><span class="stat-value text-[#a3362a]"><?= $summary['not_eligible'] ?></span><span class="stat-hint">of <?= $summary['enrolled'] ?> enrolled</span></a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
    <div class="flex min-w-0 flex-col gap-6">
        <!-- Clearance progress by program -->
        <section class="surface p-5">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div><div class="eyebrow">Clearance progress</div><h2 class="section-title mt-1">By program</h2></div>
                <div class="flex flex-wrap gap-3 text-xs text-ink-muted">
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-brand-700"></span>Completed</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-brand-300"></span>Card active</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-gold-300"></span>Receipt stage</span>
                    <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-sm bg-[#e2e8e0]"></span>Not started</span>
                </div>
            </div>
            <div class="mt-5 flex flex-col gap-4">
                <?php foreach ($byProgram as $p):
                    $t = max(1, $p['enrolled']);
                    $seg = [
                        ['bg-brand-700', $p['completed'], 'completed'],
                        ['bg-brand-300', $p['card'] - $p['completed'], 'card active'],
                        ['bg-gold-300', $p['started'] - $p['card'], 'receipt stage'],
                    ];
                ?>
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span class="font-semibold"><?= esc($p['program']) ?></span><span class="text-ink-muted"><?= $p['completed'] ?> of <?= $p['enrolled'] ?> completed</span></div>
                        <div class="flex h-3 overflow-hidden rounded-full bg-[#e2e8e0]" role="img" aria-label="<?= esc($p['program']) ?>: <?= $p['completed'] ?> completed, <?= $p['card'] - $p['completed'] ?> with active cards, <?= $p['started'] - $p['card'] ?> at receipt stage, <?= $p['enrolled'] - $p['started'] ?> not started">
                            <?php foreach ($seg as [$cls, $n]): if ($n > 0): ?><div class="<?= $cls ?> h-full border-r-2 border-paper last:border-r-0" style="width: <?= round($n / $t * 100, 1) ?>%"></div><?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($byProgram === []): ?><p class="text-sm text-ink-muted">No enrolled students this term.</p><?php endif; ?>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- INC -->
            <section class="surface overflow-hidden">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <div><div class="eyebrow">Follow-ups</div><h2 class="section-title mt-1">Students with INC</h2></div>
                    <a href="<?= site_url('admin/reports') ?>#inc" class="text-sm font-semibold text-brand-700">All</a>
                </div>
                <ul class="m-0 list-none p-0">
                    <?php foreach ($incCases as $c): ?>
                        <li><a class="list-row" href="<?= site_url('admin/clearances/' . $c['clearance_id']) ?>">
                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold"><?= esc(person_name($c)) ?></span><span class="block truncate text-xs text-ink-muted"><?= esc($c['subject_code']) ?> · <?= esc($c['teacher_name']) ?></span></span>
                            <?= (int) $c['reported'] ? status_badge('reported', 'Verify') : status_badge('INC') ?>
                        </a></li>
                    <?php endforeach; ?>
                    <?php if ($incCases === []): ?><li class="empty-state py-8"><i class="bi bi-check2-all"></i>No INC subjects.</li><?php endif; ?>
                </ul>
            </section>
            <!-- Failed -->
            <section class="surface overflow-hidden">
                <div class="flex items-center justify-between border-b border-line p-5">
                    <div><div class="eyebrow">Re-enrollment</div><h2 class="section-title mt-1">Failed subjects</h2></div>
                    <a href="<?= site_url('admin/reenrollment') ?>" class="text-sm font-semibold text-brand-700">Queue</a>
                </div>
                <ul class="m-0 list-none p-0">
                    <?php foreach ($failedCases as $c): ?>
                        <li><a class="list-row" href="<?= site_url('admin/clearances/' . $c['clearance_id']) ?>">
                            <span class="min-w-0 flex-1"><span class="block truncate font-semibold"><?= esc(person_name($c)) ?></span><span class="block truncate text-xs text-ink-muted"><?= esc($c['subject_code']) ?> · <?= esc($c['teacher_name']) ?></span></span>
                            <?= status_badge($c['reenrollment_status'] ?? 'FAILED', ['RE_ENROLLMENT_REQUIRED' => 'Needs receipt', 'RE_ENROLLMENT_RECEIPT_PENDING' => 'In review', 'RE_ENROLLMENT_APPROVED' => 'Confirmed'][$c['reenrollment_status']] ?? null) ?>
                        </a></li>
                    <?php endforeach; ?>
                    <?php if ($failedCases === []): ?><li class="empty-state py-8"><i class="bi bi-check2-all"></i>No failed subjects.</li><?php endif; ?>
                </ul>
            </section>
        </div>
    </div>

    <aside class="flex flex-col gap-6">
        <!-- Receipt queue -->
        <section class="surface overflow-hidden">
            <div class="flex items-center justify-between border-b border-line p-5">
                <div><div class="eyebrow">Validation queue</div><h2 class="section-title mt-1">Oldest receipts first</h2></div>
                <span class="badge-status badge-blue"><i class="bi bi-eye-fill" aria-hidden="true"></i><?= $pending ?></span>
            </div>
            <ul class="m-0 list-none p-0">
                <?php foreach ($queue as $r): ?>
                    <li><a class="list-row" href="<?= site_url('admin/receipts?id=' . $r['id']) ?>">
                        <span class="min-w-0 flex-1"><span class="block truncate font-semibold"><?= esc(person_name($r)) ?></span><span class="block text-xs text-ink-muted"><?= esc($r['student_number']) ?> · <?= esc($r['program_code']) ?> · <?= time_ago($r['submitted_at']) ?></span></span>
                        <span class="text-right"><span class="block text-sm font-semibold"><?= peso($r['amount']) ?></span><?= status_badge($r['status']) ?></span>
                    </a></li>
                <?php endforeach; ?>
                <?php if ($queue === []): ?><li class="empty-state py-8"><i class="bi bi-inbox"></i>Queue is clear.</li><?php endif; ?>
            </ul>
        </section>

        <!-- Eligibility -->
        <section class="surface p-5" id="eligibility">
            <div class="eyebrow">Enrollment eligibility</div>
            <h2 class="section-title mt-1">Next semester</h2>
            <?php $pctE = $summary['enrolled'] ? round($summary['eligible'] / $summary['enrolled'] * 100) : 0; ?>
            <div class="mt-4 flex items-baseline gap-2"><span class="font-serif text-4xl text-brand-900"><?= $pctE ?>%</span><span class="text-sm text-ink-muted">eligible</span></div>
            <div class="progress-track mt-3 h-3" role="img" aria-label="<?= $summary['eligible'] ?> eligible, <?= $summary['not_eligible'] ?> not yet eligible"><div class="progress-fill" style="width: <?= $pctE ?>%"></div></div>
            <dl class="mb-0 mt-4 grid grid-cols-2 gap-3 text-sm">
                <div class="surface-muted p-3"><dt class="flex items-center gap-1 text-xs font-semibold text-[#1f6a3f]"><i class="bi bi-check-circle-fill"></i>Eligible</dt><dd class="mb-0 text-xl font-bold"><?= $summary['eligible'] ?></dd></div>
                <div class="surface-muted p-3"><dt class="flex items-center gap-1 text-xs font-semibold text-[#a3362a]"><i class="bi bi-exclamation-triangle-fill"></i>Not yet eligible</dt><dd class="mb-0 text-xl font-bold"><?= $summary['not_eligible'] ?></dd></div>
            </dl>
            <p class="mb-0 mt-3 text-xs text-ink-muted"><?= $summary['not_started'] ?> enrolled student(s) have not started a clearance.</p>
        </section>

        <!-- Recently completed -->
        <section class="surface overflow-hidden">
            <div class="border-b border-line p-5"><div class="eyebrow">Completed clearances</div><h2 class="section-title mt-1">Recently cleared</h2></div>
            <ul class="m-0 list-none p-0">
                <?php foreach ($recentCompleted as $c): ?>
                    <li><a class="list-row" href="<?= site_url('admin/clearances/' . $c['id']) ?>">
                        <i class="bi bi-patch-check-fill text-[#1f6a3f]" aria-hidden="true"></i>
                        <span class="min-w-0 flex-1"><span class="block truncate font-semibold"><?= esc(person_name($c)) ?></span><span class="block text-xs text-ink-muted"><?= esc($c['reference_no']) ?></span></span>
                        <span class="text-xs text-ink-muted"><?= time_ago($c['completed_at']) ?></span>
                    </a></li>
                <?php endforeach; ?>
                <?php if ($recentCompleted === []): ?><li class="empty-state py-8"><i class="bi bi-hourglass"></i>None yet this term.</li><?php endif; ?>
            </ul>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
