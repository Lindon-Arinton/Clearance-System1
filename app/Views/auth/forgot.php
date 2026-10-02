<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<div class="auth-card auth-stagger w-full max-w-[470px] rounded-[28px] bg-paper/95 p-8 shadow-lift backdrop-blur-xl">
    <a href="<?= site_url('login') ?>" class="text-sm font-semibold text-brand-700 hover:underline"><i class="bi bi-arrow-left"></i> Back to sign in</a>
    <h1 class="mt-5 font-serif text-3xl text-brand-900">Reset your password</h1>
    <p class="mt-3 text-[15px] leading-relaxed text-ink-soft">
        For your security, passwords are reset in person. Visit the <?= esc(setting('office_name')) ?> with your school ID
        (students) or contact the registrar (faculty). You will receive a temporary password and will be asked to set a new one
        the next time you sign in.
    </p>
    <div class="surface-muted mt-5 p-4 text-sm text-ink-soft">
        <div class="font-semibold text-ink"><?= esc(setting('school_name')) ?> · <?= esc(setting('office_name')) ?></div>
        <?php if (setting('school_address')): ?><div class="mt-1"><?= esc(setting('school_address')) ?></div><?php endif; ?>
        <?php if (setting('registrar_name')): ?><div class="mt-1">Registrar: <?= esc(setting('registrar_name')) ?></div><?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
