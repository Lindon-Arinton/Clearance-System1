<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="surface empty-state">
    <i class="bi bi-journal-x"></i>
    <p class="font-semibold text-ink">No subjects to review</p>
    <p>You have no subjects assigned for <?= $term ? esc(term_label($term)) : 'the current term' ?>. The registrar assigns subjects to teachers.</p>
</div>
<?= $this->endSection() ?>
