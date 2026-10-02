<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Sign in') ?> · <?= esc(setting('school_name')) ?></title>
    <link rel="icon" href="<?= base_url('assets/img/favicon.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-body min-h-screen overflow-x-hidden bg-brand-950">
<div class="relative flex min-h-screen flex-col overflow-hidden">
    <!-- Animated backdrop: slow zoom on the campus photo, shifting tint and drifting light -->
    <div class="auth-bg absolute inset-0 bg-cover bg-center" style="background-image:url('<?= base_url('assets/img/campus.jpg') ?>')" aria-hidden="true"></div>
    <div class="auth-overlay absolute inset-0" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <span class="auth-orb -left-24 -top-32 h-[420px] w-[420px] bg-gold-400"></span>
        <span class="auth-orb bottom-[-160px] left-[30%] h-[380px] w-[380px] bg-brand-400 [animation-delay:-5s]"></span>
        <span class="auth-orb -right-20 top-[20%] h-[320px] w-[320px] bg-[#6fb3a7] [animation-delay:-10s]"></span>
    </div>

    <main class="relative z-10 flex flex-1 items-center justify-center gap-12 px-4 py-10 lg:justify-between lg:px-[6%] xl:px-[8%]">
        <!-- Story panel (desktop) -->
        <section class="auth-hero hidden max-w-[500px] text-white lg:block" aria-hidden="true">
            <img src="<?= base_url('assets/img/logo.png') ?>" alt="" class="mb-6 block h-24 w-24 rounded-full shadow-lift ring-4 ring-white/15">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[.14em] text-white/85 backdrop-blur">
                <span class="auth-live-dot"></span> Clearance Management System
            </span>
            <h2 class="mt-6 font-serif text-[3.2rem] leading-[1.05] text-white">
                Your clearance,<br>from receipt to
                <span class="relative inline-block min-w-[5ch] overflow-hidden align-bottom"><span class="auth-word" data-rotating-word>signature.</span></span>
            </h2>
            <p class="mt-5 max-w-md text-[15px] leading-relaxed text-white/75">
                Upload once, track every subject, and know exactly where you stand — until you are cleared for next semester.
            </p>
            <div class="mt-8 rounded-2xl border border-white/15 bg-white/[.07] p-4 shadow-lift backdrop-blur-md">
                <div class="mb-3 flex items-center justify-between px-1 text-xs font-semibold uppercase tracking-[.14em] text-white/60">
                    <span>Clearance journey</span><span data-journey-count>0 / 4</span>
                </div>
                <ol class="m-0 flex list-none flex-col gap-1 p-0" data-journey>
                    <li class="journey-step"><span class="journey-dot"><i class="bi bi-receipt"></i></span>Tuition receipt verified</li>
                    <li class="journey-step"><span class="journey-dot"><i class="bi bi-card-checklist"></i></span>Clearance card issued</li>
                    <li class="journey-step"><span class="journey-dot"><i class="bi bi-pen"></i></span>Subjects signed by teachers</li>
                    <li class="journey-step"><span class="journey-dot"><i class="bi bi-mortarboard"></i></span>Eligible for next semester</li>
                </ol>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="journey-bar" style="width:0%" data-journey-bar></div></div>
            </div>
        </section>

        <?= $this->renderSection('content') ?>
    </main>
    <footer class="relative z-10 px-4 pb-6 text-center text-[13px] text-white/75 lg:text-right lg:pr-[6%] xl:pr-[8%]">
        <?= esc(setting('school_short_name')) ?> <?= esc(setting('office_name')) ?> · <?= ($t = model(\App\Models\SchoolTermModel::class)->current()) ? 'Academic Year ' . esc(str_replace('-', '–', $t['school_year'])) : 'Clearance Management System' ?>
    </footer>
</div>
<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/auth.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
