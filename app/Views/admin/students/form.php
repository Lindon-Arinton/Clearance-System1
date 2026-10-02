<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $s = $student; ?>
<form action="<?= site_url($s ? 'admin/students/' . $s['id'] : 'admin/students') ?>" method="post" class="surface max-w-3xl p-6">
    <?= csrf_field() ?>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="form-label" for="student_number">Student ID number</label><input class="form-control font-mono" id="student_number" name="student_number" value="<?= esc(old_or('student_number', $s)) ?>" required placeholder="9785" inputmode="numeric" pattern="\d{3,10}" title="Digits only, e.g. 9785"><div class="form-text">Digits only. Can be used to sign in.</div></div>
        <div><label class="form-label" for="email">Personal email</label><input class="form-control" type="email" required id="email" name="email" value="<?= esc(old_or('email', $s)) ?>"><div class="form-text">Used to sign in.</div></div>
        <div><label class="form-label" for="first_name">First name</label><input class="form-control" id="first_name" name="first_name" value="<?= esc(old_or('first_name', $s)) ?>" required maxlength="80"></div>
        <div><label class="form-label" for="last_name">Last name</label><input class="form-control" id="last_name" name="last_name" value="<?= esc(old_or('last_name', $s)) ?>" required maxlength="80"></div>
        <div><label class="form-label" for="middle_name">Middle name</label><input class="form-control" id="middle_name" name="middle_name" value="<?= esc(old_or('middle_name', $s)) ?>" maxlength="80"></div>
        <div><label class="form-label" for="contact_number">Contact number</label><input class="form-control" id="contact_number" name="contact_number" value="<?= esc(old_or('contact_number', $s)) ?>" maxlength="30"></div>
        <div><label class="form-label" for="program_id">Program</label>
            <select class="form-select" id="program_id" name="program_id" required><option value="">Choose…</option>
                <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>" <?= old_or('program_id', $s) == $p['id'] ? 'selected' : '' ?>><?= esc($p['code'] . ' — ' . $p['name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label" for="year_level">Year level</label>
                <select class="form-select" id="year_level" name="year_level" required>
                    <?php for ($y = 1; $y <= 6; $y++): ?><option value="<?= $y ?>" <?= (int) old_or('year_level', $s, '1') === $y ? 'selected' : '' ?>><?= year_level_label($y) ?></option><?php endfor; ?>
                </select></div>
            <div><label class="form-label" for="section">Section</label><input class="form-control" id="section" name="section" value="<?= esc(old_or('section', $s)) ?>" maxlength="20"></div>
        </div>
        <?php if ($s): ?>
            <div><label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach (['active', 'inactive', 'graduated'] as $st): ?><option value="<?= $st ?>" <?= old_or('status', $s) === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
                </select><div class="form-text">Inactive students cannot sign in.</div></div>
        <?php endif; ?>
    </div>
    <?php if (! $s): ?>
        <p class="mt-5 rounded-xl bg-brand-50 p-3 text-sm text-brand-900"><i class="bi bi-key" aria-hidden="true"></i> A temporary password is generated and shown once after saving. The student must change it at first sign-in.</p>
    <?php endif; ?>
    <div class="mt-6 flex gap-2">
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i><?= $s ? 'Save changes' : 'Create student' ?></button>
        <a class="btn btn-light" href="<?= site_url($s ? 'admin/students/' . $s['id'] : 'admin/students') ?>">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
