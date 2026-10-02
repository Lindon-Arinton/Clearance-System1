<div class="eyebrow mt-6"><?= esc(setting('school_short_name')) ?></div>
<h1 class="mt-1 font-serif text-[2.1rem] leading-tight text-brand-900">Welcome back.</h1>
<p class="mt-2 text-[15px] leading-relaxed text-ink-muted">Sign in to continue your clearance journey and keep every academic requirement moving.</p>

<?php if (session('login_error')): ?>
    <div class="alert alert-danger mb-0 mt-4 flex items-start gap-2 rounded-xl py-2.5 text-sm" role="alert">
        <i class="bi bi-exclamation-octagon-fill mt-0.5" aria-hidden="true"></i><span><?= esc(session('login_error')) ?></span>
    </div>
<?php elseif (session('login_info') || session('toast_error')): ?>
    <div class="alert alert-success mb-0 mt-4 flex items-start gap-2 rounded-xl py-2.5 text-sm" role="status">
        <i class="bi bi-info-circle-fill mt-0.5" aria-hidden="true"></i><span><?= esc(session('login_info') ?? session('toast_error')) ?></span>
    </div>
<?php endif; ?>

<form action="<?= site_url('login') ?>" method="post" class="mt-5" novalidate data-loading-form="Signing in…">
    <?= csrf_field() ?>
    <div>
        <label for="login_id" class="form-label">Student ID number or personal email</label>
        <div class="auth-field">
            <i class="bi bi-person-vcard pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
            <input type="text" class="form-control py-2.5 pl-10" id="login_id" name="login_id" value="<?= esc(old('login_id')) ?>" placeholder="9785 or you@gmail.com" autocomplete="username" required maxlength="120" aria-describedby="loginIdHelp">
        </div>
        <div class="form-text" id="loginIdHelp">Teachers and registrar staff can use their employee ID or email.</div>
    </div>

    <div class="mt-4">
        <div class="flex items-center justify-between">
            <label for="password" class="form-label">Password</label>
            <a href="<?= site_url('forgot-password') ?>" class="mb-2 text-[13px] font-semibold text-brand-700 hover:underline">Forgot password?</a>
        </div>
        <div class="auth-field">
            <i class="bi bi-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
            <input type="password" class="form-control py-2.5 pl-10 pr-11" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required maxlength="128" data-caps-hint="#capsHint">
            <button type="button" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg bg-transparent text-ink-muted hover:text-brand-700" data-toggle-password="#password" aria-label="Show password" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div class="caps-hint" id="capsHint" role="status" hidden><i class="bi bi-capslock-fill" aria-hidden="true"></i>Caps Lock is on</div>
    </div>

    <div class="form-check mt-4">
        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember" <?= old('remember') ? 'checked' : '' ?>>
        <label class="form-check-label text-sm text-ink-soft" for="remember">Keep me signed in on this device</label>
    </div>

    <button type="submit" class="btn btn-primary btn-shine mt-5 w-full py-3 text-[15px] shadow-lift">Sign in to portal <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>

<p class="mb-0 mt-4 text-center text-sm text-ink-muted">New student? <a href="<?= site_url('signup') ?>" class="font-semibold text-brand-700 hover:underline" data-auth-switch="signup">Create an account</a></p>

<div class="mt-6 flex items-start gap-2 border-t border-line pt-4 text-xs leading-relaxed text-ink-muted">
    <i class="bi bi-shield-check text-gold-600" aria-hidden="true"></i>
    <span>Your session is protected by <?= esc(setting('school_short_name')) ?> security. Access is limited to your assigned role and records.</span>
</div>
