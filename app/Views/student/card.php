<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<?php if (in_array($bundle['clearance']['status'] ?? '', ['in_progress', 'completed'], true)): ?>
    <button type="button" class="btn btn-soft h-10 px-3 text-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $clearance = $bundle['clearance']; ?>
<?php if (! $clearance || ! in_array($clearance['status'], ['in_progress', 'completed'], true)): ?>
    <div class="surface empty-state">
        <i class="bi bi-lock"></i>
        <p class="font-semibold text-ink">Your clearance card is locked</p>
        <p>The card is created automatically once the registrar approves your tuition receipt.</p>
        <a href="<?= site_url('student/receipt') ?>" class="btn btn-primary mt-3">Go to tuition receipt</a>
    </div>
<?php else: ?>
    <?= view('partials/clearance_card', ['clearance' => $clearance, 'subjects' => $bundle['subjects'], 'snapshot' => $bundle['snapshot'], 'reenrollments' => $reenrollments]) ?>
    <p class="no-print mt-4 text-sm text-ink-muted"><i class="bi bi-shield-check" aria-hidden="true"></i> Teacher signatures are tamper-evident: each carries a signature ID generated when the teacher approved the subject.</p>
<?php endif; ?>
<?= $this->endSection() ?>
