<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<button type="button" class="btn btn-soft h-10 px-3 text-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$exportQs = '?' . http_build_query(array_filter(['term' => $termId, 'program' => $programId]));
$export   = static fn (string $type, string $label) => '<a class="btn btn-light btn-sm no-print" href="' . site_url('admin/reports/export/' . $type) . $exportQs . '"><i class="bi bi-download"></i>' . $label . '</a>';
?>
<form method="get" class="surface no-print mb-6 flex flex-wrap items-end gap-3 p-4">
    <div><label class="form-label" for="term">Term</label><select class="form-select" id="term" name="term">
        <?php foreach ($terms as $t): ?><option value="<?= $t['id'] ?>" <?= $termId === (int) $t['id'] ? 'selected' : '' ?>><?= esc(term_label($t)) ?></option><?php endforeach; ?></select></div>
    <div><label class="form-label" for="program">Program</label><select class="form-select" id="program" name="program"><option value="">All programs</option>
        <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>" <?= $programId === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['code']) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-primary" type="submit"><i class="bi bi-bar-chart"></i>Run report</button>
</form>

<?php if (! $term): ?>
    <div class="surface empty-state"><i class="bi bi-calendar-x"></i>No term selected.</div>
<?php else: ?>
<h2 class="section-title mb-4"><?= esc(term_label($term)) ?></h2>

<!-- Summary -->
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="stat-card"><span class="stat-label">Enrolled students</span><span class="stat-value"><?= $summary['enrolled'] ?></span><span class="stat-hint"><?= $summary['not_started'] ?> have not started clearance</span></div>
    <div class="stat-card"><span class="stat-label">Receipt stage</span><span class="stat-value"><?= $summary['awaiting_receipt'] + $summary['receipt_review'] + $summary['reupload_required'] ?></span><span class="stat-hint"><?= $summary['receipt_review'] ?> in review · <?= $summary['reupload_required'] ?> re-upload</span></div>
    <div class="stat-card"><span class="stat-label">Cards in progress</span><span class="stat-value"><?= $summary['in_progress'] ?></span><span class="stat-hint">Clearance incomplete</span></div>
    <div class="stat-card"><span class="stat-label">Completed · eligible</span><span class="stat-value text-[#1f6a3f]"><?= $summary['completed'] ?></span><span class="stat-hint"><?= $summary['enrolled'] ? round($summary['completed'] / $summary['enrolled'] * 100) : 0 ?>% of enrolled</span></div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="surface overflow-hidden">
        <div class="border-b border-line p-5"><div class="eyebrow">Clearance progress</div><h3 class="section-title mt-1">By program</h3></div>
        <table class="table">
            <thead><tr><th class="ps-5">Program</th><th class="text-center">Enrolled</th><th class="text-center">Started</th><th class="text-center">Card issued</th><th class="pe-5 text-center">Completed</th></tr></thead>
            <tbody>
            <?php foreach ($byProgram as $p): ?>
                <tr><td class="ps-5 font-semibold"><?= esc($p['program']) ?></td><td class="text-center"><?= $p['enrolled'] ?></td><td class="text-center"><?= $p['started'] ?></td><td class="text-center"><?= $p['card'] ?></td><td class="pe-5 text-center font-semibold text-[#1f6a3f]"><?= $p['completed'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="surface p-5">
        <div class="eyebrow">Tuition receipts</div><h3 class="section-title mt-1">Validation</h3>
        <dl class="mb-0 mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
            <?php foreach (['submitted' => 'Submitted', 'under_review' => 'Under review', 'approved' => 'Approved', 'reupload_required' => 'Re-upload'] as $k => $label): ?>
                <div class="surface-muted p-3"><dt class="text-xs font-semibold text-ink-muted"><?= $label ?></dt><dd class="mb-0 text-2xl font-bold"><?= (int) ($receipts[$k] ?? 0) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <p class="mb-0 mt-3 text-xs text-ink-muted"><?= (int) ($receipts['attempts'] ?? 0) ?> total upload attempts this term (including re-uploads).</p>
    </section>
</div>

<!-- Subject outcomes -->
<section class="surface mt-6 overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5">
        <div><div class="eyebrow">Teacher clearance</div><h3 class="section-title mt-1">Subject outcomes</h3></div><?= $export('subjects', 'Export CSV') ?>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th class="ps-5">Subject</th><th>Teacher</th><th class="text-center">Students</th><th class="text-center">Pending</th><th class="text-center">Passed</th><th class="text-center">Unsigned</th><th class="text-center">INC</th><th class="pe-5 text-center">Failed</th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $s): ?>
                <tr><td class="ps-5"><span class="font-semibold"><?= esc($s['code']) ?></span> · <?= esc($s['title']) ?></td><td><?= esc($s['teacher_name']) ?></td>
                    <td class="text-center"><?= $s['students'] ?></td><td class="text-center"><?= $s['pending'] ?></td><td class="text-center text-[#1f6a3f]"><?= $s['passed'] ?></td>
                    <td class="text-center"><?= $s['awaiting_sign'] ?></td><td class="text-center text-[#a4521a]"><?= $s['inc'] ?></td><td class="pe-5 text-center text-[#a3362a]"><?= $s['failed'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <section class="surface overflow-hidden" id="inc">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5"><div><div class="eyebrow">Follow-ups</div><h3 class="section-title mt-1">INC cases (<?= count($incCases) ?>)</h3></div><?= $export('inc', 'Export CSV') ?></div>
        <ul class="m-0 list-none p-0">
            <?php foreach ($incCases as $c): ?>
                <li class="border-b border-line px-5 py-3 text-sm last:border-b-0"><div class="flex justify-between gap-2"><span class="font-semibold"><?= esc(person_name($c)) ?> · <?= esc($c['subject_code']) ?></span><?= (int) $c['reported'] ? status_badge('reported') : status_badge('INC') ?></div>
                    <div class="text-xs text-ink-muted"><?= esc($c['teacher_name']) ?> · <?= esc($c['requirements'] ?? 'All requirements verified') ?></div></li>
            <?php endforeach; ?>
            <?php if ($incCases === []): ?><li class="empty-state py-8">No INC cases.</li><?php endif; ?>
        </ul>
    </section>
    <section class="surface overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5"><div><div class="eyebrow">Re-enrollment</div><h3 class="section-title mt-1">Failed subjects (<?= count($failed) ?>)</h3></div><?= $export('failed', 'Export CSV') ?></div>
        <ul class="m-0 list-none p-0">
            <?php foreach ($failed as $c): ?>
                <li class="border-b border-line px-5 py-3 text-sm last:border-b-0"><div class="flex justify-between gap-2"><span class="font-semibold"><?= esc(person_name($c)) ?> · <?= esc($c['subject_code']) ?></span><?= status_badge($c['reenrollment_status'] ?? 'FAILED') ?></div>
                    <div class="text-xs text-ink-muted"><?= esc($c['teacher_name']) ?> · failed <?= fmt_date($c['decided_at']) ?></div></li>
            <?php endforeach; ?>
            <?php if ($failed === []): ?><li class="empty-state py-8">No failed subjects.</li><?php endif; ?>
        </ul>
    </section>
</div>

<!-- Eligibility -->
<section class="surface mt-6 overflow-hidden" id="eligibility">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-5">
        <div><div class="eyebrow">Enrollment eligibility</div><h3 class="section-title mt-1"><?= $summary['eligible'] ?> eligible · <?= $summary['not_eligible'] ?> not yet eligible</h3></div><?= $export('eligibility', 'Export CSV') ?>
    </div>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Student</th><th>Program</th><th>Clearance</th><th class="text-center">Resolved</th><th class="pe-5">Eligibility</th></tr></thead>
            <tbody>
            <?php foreach ($statuses as $s): ?>
                <tr>
                    <td class="ps-5"><?php if ($s['clearance_id']): ?><a class="font-semibold text-ink hover:text-brand-700" href="<?= site_url('admin/clearances/' . $s['clearance_id']) ?>"><?= esc(person_name($s, true)) ?></a><?php else: ?><span class="font-semibold"><?= esc(person_name($s, true)) ?></span><?php endif; ?><div class="font-mono text-xs text-ink-muted"><?= esc($s['student_number']) ?></div></td>
                    <td><?= esc($s['program_code']) ?> · <?= year_level_label($s['year_level']) ?></td>
                    <td><?= status_badge($s['clearance_status'] ?? 'not_started') ?></td>
                    <td class="text-center"><?= (int) $s['resolved'] ?>/<?= (int) $s['subjects'] ?></td>
                    <td class="pe-5"><?= status_badge((int) $s['enrollment_eligible'] === 1 ? 'ELIGIBLE' : 'NOT_ELIGIBLE') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<?= $this->endSection() ?>
