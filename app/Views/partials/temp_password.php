<?php if (session('temp_password')): ?>
    <div class="alert alert-warning mb-6 flex flex-wrap items-center gap-3 rounded-xl" role="alert">
        <i class="bi bi-key-fill text-lg" aria-hidden="true"></i>
        <div class="flex-1">
            <div class="font-semibold">Temporary password<?= session('temp_password_for') ? ' for ' . esc(session('temp_password_for')) : '' ?></div>
            <div class="text-sm">Share it privately. It is shown only once and must be changed at first sign-in.</div>
        </div>
        <code class="rounded-lg bg-white px-3 py-2 font-mono text-base font-bold text-ink"><?= esc(session('temp_password')) ?></code>
    </div>
<?php endif; ?>
