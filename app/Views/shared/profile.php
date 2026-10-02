<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $p = $authProfile; ?>
<?php if ($mustChange): ?>
    <div class="alert alert-warning mb-6 flex items-start gap-2 rounded-xl" role="alert">
        <i class="bi bi-shield-exclamation mt-0.5" aria-hidden="true"></i>
        <span>Your password was set by the registrar. Please choose a new password to continue using the system.</span>
    </div>
<?php endif; ?>

<?php if ($authRole === 'teacher' && empty($p['signature_path']) && ! $mustChange): ?>
    <!-- First sign-in: the e-signature is required before any clearance work. -->
    <div class="mb-6"><?= view('shared/_signature_section', ['p' => $p, 'hasSignature' => false]) ?></div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="surface p-5">
        <div class="flex items-center gap-4">
            <span class="avatar h-14 w-14 text-lg"><?= esc(initials(person_name($authUser))) ?></span>
            <div>
                <h2 class="section-title"><?= esc(person_name($authUser)) ?></h2>
                <div class="text-sm text-ink-muted"><?= esc(ucfirst($authRole)) ?> · <?= esc($authUser['username']) ?></div>
            </div>
        </div>
        <dl class="mb-0 mt-5 grid grid-cols-2 gap-4 text-sm">
            <div><dt class="kv-label">Email</dt><dd class="kv-value mb-0 break-all"><?= esc($authUser['email'] ?: '—') ?></dd></div>
            <div><dt class="kv-label">Last sign-in</dt><dd class="kv-value mb-0"><?= fmt_datetime($authUser['last_login_at']) ?></dd></div>
            <?php if ($authRole === 'student'): ?>
                <div><dt class="kv-label">Student number</dt><dd class="mb-0 font-mono font-semibold"><?= esc($p['student_number']) ?></dd></div>
                <div><dt class="kv-label">Program</dt><dd class="kv-value mb-0"><?= esc($p['program_code']) ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Program name</dt><dd class="kv-value mb-0"><?= esc($p['program_name']) ?></dd></div>
                <div><dt class="kv-label">Year level</dt><dd class="kv-value mb-0"><?= year_level_label($p['year_level']) ?></dd></div>
                <div><dt class="kv-label">Section</dt><dd class="kv-value mb-0"><?= esc($p['section'] ?: '—') ?></dd></div>
            <?php elseif ($authRole === 'teacher'): ?>
                <div><dt class="kv-label">Employee ID</dt><dd class="mb-0 font-mono font-semibold"><?= esc($p['employee_number']) ?></dd></div>
                <div><dt class="kv-label">Department</dt><dd class="kv-value mb-0"><?= esc($p['department'] ?: '—') ?></dd></div>
            <?php endif; ?>
        </dl>
        <p class="mt-5 text-xs text-ink-muted">To correct your personal details, please contact the <?= esc(setting('office_name')) ?>.</p>
    </section>

    <section class="surface p-5">
        <div class="eyebrow">Security</div>
        <h2 class="section-title mt-1">Change password</h2>
        <form action="<?= site_url('profile/password') ?>" method="post" class="mt-4 flex flex-col gap-4" autocomplete="off">
            <?= csrf_field() ?>
            <div><label class="form-label" for="current_password">Current password</label><input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
            <div><label class="form-label" for="new_password">New password</label><input class="form-control" type="password" id="new_password" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="pwHelp">
                <div class="form-text" id="pwHelp">At least 8 characters, including a letter and a number.</div></div>
            <div><label class="form-label" for="confirm_password">Confirm new password</label><input class="form-control" type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"></div>
            <button type="submit" class="btn btn-primary self-start"><i class="bi bi-key"></i>Update password</button>
        </form>
    </section>

    <?php if ($authRole === 'teacher' && ! empty($p['signature_path'])): ?>
        <?= view('shared/_signature_section', ['p' => $p, 'hasSignature' => true]) ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
