<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('student/history') ?>" class="btn btn-light h-10 px-3 text-sm"><i class="bi bi-arrow-left"></i>Back</a>
<button type="button" class="btn btn-soft h-10 px-3 text-sm" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/clearance_record', ['bundle' => $bundle, 'reenrollments' => $reenrollments, 'requirements' => $requirements]) ?>
<?= $this->endSection() ?>
