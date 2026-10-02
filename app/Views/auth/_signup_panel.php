<?php
$errors   = (array) session('errors');
$invalid  = static fn (string $f): string => isset($errors[$f]) ? ' is-invalid' : '';
$feedback = static fn (string $f): string => isset($errors[$f]) && $errors[$f] !== '' ? '<div class="invalid-feedback d-block">' . esc($errors[$f]) . '</div>' : '';
$general  = array_filter($errors, 'is_int', ARRAY_FILTER_USE_KEY);
$steps    = [1 => ['About you', 'bi-person'], 2 => ['Academics', 'bi-mortarboard'], 3 => ['Security', 'bi-shield-lock']];
?>
<div class="eyebrow mt-6">Student sign-up</div>
<h1 class="mt-1 font-serif text-[2.1rem] leading-tight text-brand-900">Create your account.</h1>
<p class="mt-2 text-[15px] leading-relaxed text-ink-muted">Use your student ID number and a personal email you can access. The registrar verifies every new account before you can sign in.</p>

<?php if ($errors): ?>
    <div class="alert alert-danger mb-0 mt-4 rounded-xl py-2.5 text-sm" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-1" aria-hidden="true"></i>
        <?= $general ? esc(implode(' ', $general)) : 'Please check the highlighted fields.' ?>
    </div>
<?php endif; ?>

<form action="<?= site_url('signup') ?>" method="post" class="mt-5" novalidate data-wizard data-check-url="<?= site_url('signup/check') ?>" data-loading-form="Creating account…"
      data-error-fields="<?= esc(json_encode(array_keys(array_filter($errors, 'is_string', ARRAY_FILTER_USE_KEY))), 'attr') ?>">
    <?= csrf_field() ?>

    <!-- Step indicator (shown once JavaScript turns the form into a wizard) -->
    <div class="wizard-indicator hidden">
        <ol class="m-0 flex list-none items-start justify-between gap-2 p-0">
            <?php foreach ($steps as $n => [$label, $icon]): ?>
                <li class="wizard-item flex flex-1 flex-col items-center gap-1 text-center" data-step-indicator="<?= $n ?>">
                    <span class="wizard-dot"><span data-dot-number><?= $n ?></span></span>
                    <span class="wizard-label"><?= $label ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <div class="mt-3 h-1 overflow-hidden rounded-full bg-[#e2e8e0]"><div class="h-full rounded-full bg-brand-600 transition-all duration-700 ease-out" style="width:33.34%" data-wizard-progress></div></div>
        <p class="visually-hidden" aria-live="polite" data-wizard-status></p>
    </div>

    <div data-steps>
        <fieldset class="auth-step mt-5" data-step="1">
            <legend class="mb-3 flex items-center gap-2 text-base font-bold text-ink"><i class="bi bi-person text-brand-600" aria-hidden="true"></i>About you</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="student_number">Student ID number</label>
                    <div class="auth-field">
                        <i class="bi bi-hash pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                        <input class="form-control pl-10 font-mono<?= $invalid('student_number') ?>" id="student_number" name="student_number" value="<?= esc(old('student_number')) ?>" placeholder="9785" inputmode="numeric" pattern="\d{3,10}" required title="Digits only, e.g. 9785">
                    </div>
                    <?= $feedback('student_number') ?>
                </div>
                <div>
                    <label class="form-label" for="email">Personal email</label>
                    <div class="auth-field">
                        <i class="bi bi-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                        <input class="form-control pl-10<?= $invalid('email') ?>" type="email" id="email" name="email" value="<?= esc(old('email')) ?>" placeholder="you@gmail.com" required maxlength="120" autocomplete="email" aria-describedby="emailHelp">
                    </div>
                    <?= $feedback('email') ?>
                    <div class="form-text" id="emailHelp">You can sign in with this email or your student ID number.</div>
                </div>
                <div>
                    <label class="form-label" for="first_name">First name</label>
                    <input class="form-control<?= $invalid('first_name') ?>" id="first_name" name="first_name" value="<?= esc(old('first_name')) ?>" placeholder="Jasrylle Nicole" required maxlength="80" autocomplete="given-name">
                    <?= $feedback('first_name') ?>
                </div>
                <div>
                    <label class="form-label" for="last_name">Last name</label>
                    <input class="form-control<?= $invalid('last_name') ?>" id="last_name" name="last_name" value="<?= esc(old('last_name')) ?>" placeholder="Botobara" required maxlength="80" autocomplete="family-name">
                    <?= $feedback('last_name') ?>
                </div>
                <div>
                    <label class="form-label" for="middle_name">Middle name or initial <span class="font-normal text-ink-muted">(optional)</span></label>
                    <input class="form-control<?= $invalid('middle_name') ?>" id="middle_name" name="middle_name" value="<?= esc(old('middle_name')) ?>" placeholder="B." maxlength="80" autocomplete="additional-name">
                    <?= $feedback('middle_name') ?>
                </div>
                <div>
                    <label class="form-label" for="contact_number">Contact number <span class="font-normal text-ink-muted">(optional)</span></label>
                    <input class="form-control<?= $invalid('contact_number') ?>" id="contact_number" name="contact_number" value="<?= esc(old('contact_number')) ?>" maxlength="30" autocomplete="tel">
                    <?= $feedback('contact_number') ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="auth-step mt-5" data-step="2">
            <legend class="mb-3 flex items-center gap-2 text-base font-bold text-ink"><i class="bi bi-mortarboard text-brand-600" aria-hidden="true"></i>Academics</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label" for="program_id">Program</label>
                    <select class="form-select<?= $invalid('program_id') ?>" id="program_id" name="program_id" required>
                        <option value="">Choose your program…</option>
                        <?php foreach ($programs as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= old('program_id') == $p['id'] ? 'selected' : '' ?>><?= esc($p['code'] . ' — ' . $p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $feedback('program_id') ?>
                </div>
                <div>
                    <label class="form-label" for="year_level">Year level</label>
                    <select class="form-select<?= $invalid('year_level') ?>" id="year_level" name="year_level" required>
                        <?php for ($y = 1; $y <= 6; $y++): ?><option value="<?= $y ?>" <?= (int) old('year_level', '1') === $y ? 'selected' : '' ?>><?= year_level_label($y) ?></option><?php endfor; ?>
                    </select>
                    <?= $feedback('year_level') ?>
                </div>
                <div>
                    <label class="form-label" for="section">Section <span class="font-normal text-ink-muted">(optional)</span></label>
                    <input class="form-control<?= $invalid('section') ?>" id="section" name="section" value="<?= esc(old('section')) ?>" maxlength="20">
                    <?= $feedback('section') ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="auth-step mt-5" data-step="3">
            <legend class="mb-3 flex items-center gap-2 text-base font-bold text-ink"><i class="bi bi-shield-lock text-brand-600" aria-hidden="true"></i>Security</legend>
            <div class="grid gap-4">
                <div>
                    <label class="form-label" for="su_password">Password</label>
                    <div class="auth-field">
                        <i class="bi bi-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                        <input class="form-control pl-10 pr-11<?= $invalid('password') ?>" type="password" id="su_password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"
                               pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" title="At least 8 characters, with a letter and a number" data-strength="#strength" data-caps-hint="#suCapsHint" aria-describedby="pwHelp strengthLabel">
                        <button type="button" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg bg-transparent text-ink-muted hover:text-brand-700" data-toggle-password="#su_password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                    </div>
                    <?= $feedback('password') ?>
                    <div id="strength" class="mt-2">
                        <div class="h-1.5 overflow-hidden rounded-full bg-[#e2e8e0]"><div class="strength-bar bg-[#c2412f]" style="width:0%" data-strength-bar></div></div>
                        <div class="mt-1 flex justify-between text-xs"><span class="text-ink-muted" id="pwHelp">At least 8 characters, with a letter and a number.</span><span class="font-semibold text-ink-muted" id="strengthLabel" data-strength-label aria-live="polite"></span></div>
                    </div>
                    <div class="caps-hint" id="suCapsHint" role="status" hidden><i class="bi bi-capslock-fill" aria-hidden="true"></i>Caps Lock is on</div>
                </div>
                <div>
                    <label class="form-label" for="su_confirm_password">Confirm password</label>
                    <div class="auth-field">
                        <i class="bi bi-lock-fill pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                        <input class="form-control pl-10 pr-10<?= $invalid('confirm_password') ?>" type="password" id="su_confirm_password" name="confirm_password" required autocomplete="new-password" data-match="#su_password">
                        <i class="bi bi-check-circle-fill pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-[#1f6a3f] opacity-0 transition-opacity duration-300" data-match-ok aria-hidden="true"></i>
                    </div>
                    <?= $feedback('confirm_password') ?>
                </div>
                <div class="form-check">
                    <input class="form-check-input<?= $invalid('agree') ?>" type="checkbox" value="1" id="agree" name="agree" <?= old('agree') ? 'checked' : '' ?> required>
                    <label class="form-check-label text-sm text-ink-soft" for="agree">I confirm these details are mine and correct. False registrations are removed by the registrar.</label>
                    <?= $feedback('agree') ?>
                </div>
            </div>
        </fieldset>
    </div>

    <!-- Wizard navigation (JavaScript) -->
    <div class="mt-6 flex items-center gap-3">
        <div class="wizard-nav hidden flex-1 items-center justify-between gap-3">
            <button type="button" class="btn btn-light py-2.5" data-wizard-back><i class="bi bi-arrow-left" aria-hidden="true"></i>Back</button>
            <button type="button" class="btn btn-primary btn-shine px-5 py-2.5" data-wizard-next>Continue <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>
        <div class="wizard-submit-row flex-1">
            <button type="submit" class="btn btn-primary btn-shine w-full py-3 text-[15px] shadow-lift">Create account <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </div>
    </div>
</form>

<p class="mb-0 mt-5 text-center text-sm text-ink-muted">Teachers and staff: your account is created by the registrar. Already have an account? <a href="<?= site_url('login') ?>" class="font-semibold text-brand-700 hover:underline" data-auth-switch="login">Sign in</a></p>
