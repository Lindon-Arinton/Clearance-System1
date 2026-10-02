<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $t = $teacher; ?>
<form action="<?= site_url($t ? 'admin/teachers/' . $t['id'] : 'admin/teachers') ?>" method="post" class="surface max-w-3xl p-6">
    <?= csrf_field() ?>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="form-label" for="employee_number">Employee ID</label><input class="form-control font-mono" id="employee_number" name="employee_number" value="<?= esc(old_or('employee_number', $t)) ?>" required placeholder="T-1006" maxlength="20"><div class="form-text">Faculty record number.</div></div>
        <div><label class="form-label" for="email">Personal email</label><input class="form-control" type="email" required id="email" name="email" value="<?= esc(old_or('email', $t)) ?>"><div class="form-text">Used to sign in.</div></div>
        <div><label class="form-label" for="title">Title</label>
            <select class="form-select" id="title" name="title"><?php foreach (['Prof.', 'Dr.', 'Mr.', 'Ms.', 'Mrs.', 'Engr.'] as $ti): ?><option <?= old_or('title', $t, 'Prof.') === $ti ? 'selected' : '' ?>><?= $ti ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="department">Department</label><input class="form-control" id="department" name="department" value="<?= esc(old_or('department', $t)) ?>" maxlength="120"></div>
        <div><label class="form-label" for="first_name">First name</label><input class="form-control" id="first_name" name="first_name" value="<?= esc(old_or('first_name', $t)) ?>" required maxlength="80"></div>
        <div><label class="form-label" for="last_name">Last name</label><input class="form-control" id="last_name" name="last_name" value="<?= esc(old_or('last_name', $t)) ?>" required maxlength="80"></div>
        <div><label class="form-label" for="middle_name">Middle name</label><input class="form-control" id="middle_name" name="middle_name" value="<?= esc(old_or('middle_name', $t)) ?>" maxlength="80"></div>
        <?php if ($t): ?>
            <div class="flex items-end"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (int) $t['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Account active (can sign in)</label></div></div>
        <?php endif; ?>
    </div>
    <div class="mt-6 flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i><?= $t ? 'Save changes' : 'Create teacher' ?></button>
        <a class="btn btn-light" href="<?= site_url($t ? 'admin/teachers/' . $t['id'] : 'admin/teachers') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
