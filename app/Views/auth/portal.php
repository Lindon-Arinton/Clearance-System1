<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>
<?php
/**
 * Sign-in and sign-up on one card. JavaScript switches panels in place (no page load)
 * and keeps the URL in sync; without JavaScript the switch links are normal pages.
 *
 * @var string $mode 'login' or 'signup'
 */
$isSignup  = $mode === 'signup';
$hasErrors = session('login_error') || session('errors');
?>
<div class="auth-card auth-stagger w-full max-w-[520px] rounded-[28px] bg-paper/95 p-7 shadow-lift backdrop-blur-xl sm:p-8"
     data-auth-portal data-mode="<?= $mode ?>" <?= $hasErrors ? 'data-shake' : '' ?>>
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
            <img src="<?= base_url('assets/img/logo-192.png') ?>" alt="<?= esc(setting('school_name'), 'attr') ?> seal" class="h-12 w-12 rounded-full shadow-card">
            <div class="leading-tight">
                <div class="text-[13px] font-bold uppercase tracking-[.16em] text-brand-900"><?= esc(setting('school_short_name')) ?></div>
                <div class="text-[11px] uppercase tracking-[.08em] text-ink-muted"><?= esc(setting('office_name')) ?></div>
            </div>
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700" title="Secure sign-in"><i class="bi bi-shield-check text-lg" aria-hidden="true"></i></span>
    </div>

    <!-- Sign in / Create account switch -->
    <div class="auth-toggle mt-6" role="tablist" aria-label="Choose sign in or create account">
        <span class="auth-toggle-pill" aria-hidden="true"></span>
        <a href="<?= site_url('login') ?>" class="auth-toggle-tab" role="tab" id="tab-login" aria-controls="panel-login"
           aria-selected="<?= $isSignup ? 'false' : 'true' ?>" data-auth-switch="login"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Sign in</a>
        <a href="<?= site_url('signup') ?>" class="auth-toggle-tab" role="tab" id="tab-signup" aria-controls="panel-signup"
           aria-selected="<?= $isSignup ? 'true' : 'false' ?>" data-auth-switch="signup"><i class="bi bi-person-plus" aria-hidden="true"></i>Create account</a>
    </div>

    <div class="auth-panels" data-auth-panels>
        <section class="auth-panel <?= $isSignup ? '' : 'is-entering' ?>" id="panel-login" role="tabpanel" aria-labelledby="tab-login"
                 data-panel="login" data-title="Sign in" <?= $isSignup ? 'hidden inert' : '' ?>>
            <?= view('auth/_login_panel') ?>
        </section>
        <section class="auth-panel <?= $isSignup ? 'is-entering' : '' ?>" id="panel-signup" role="tabpanel" aria-labelledby="tab-signup"
                 data-panel="signup" data-title="Create account" <?= $isSignup ? '' : 'hidden inert' ?>>
            <?= view('auth/_signup_panel', ['programs' => $programs]) ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
