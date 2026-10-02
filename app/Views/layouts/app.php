<?php
/**
 * Application shell.
 *
 * @var array       $authUser
 * @var string      $authRole
 * @var array|null  $authProfile
 * @var string|null $title
 * @var string|null $eyebrow
 * @var string|null $subtitle
 */
$schoolShort = setting('school_short_name', 'DFLCMCFI');
$office      = setting('office_name', 'College Registrar');
$userName    = person_name($authUser);
$notif       = notification_summary((int) $authUser['id']);
$roleLabel   = ['student' => 'Student', 'teacher' => 'Teacher', 'admin' => 'Admin'][$authRole] ?? '';
$workspace   = match ($authRole) {
    'student' => ['Student portal', ($authProfile['program_code'] ?? '') . ' · ' . year_level_label($authProfile['year_level'] ?? 1)],
    'teacher' => ['Faculty workspace', $authProfile['department'] ?? 'Faculty'],
    default   => ['Registrar operations', 'Clearance desk'],
};
$defaultEyebrow = ['student' => 'Student workspace', 'teacher' => 'Teacher workspace', 'admin' => 'Admin workspace'][$authRole] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Dashboard') ?> · <?= esc($schoolShort) ?> Clearance</title>
    <link rel="icon" href="<?= base_url('assets/img/favicon.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<a href="#main" class="visually-hidden-focusable fixed left-3 top-3 z-[2000] rounded-lg bg-paper px-3 py-2 font-semibold text-brand-800 shadow-lift">Skip to content</a>

<!-- Sidebar -->
<aside class="app-sidebar offcanvas-lg offcanvas-start no-print lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:w-[246px]" tabindex="-1" id="appSidebar" aria-label="Main navigation" style="--bs-offcanvas-width: 276px;">
    <div class="flex h-full w-full flex-col overflow-y-auto bg-brand-900 px-4 pb-4 pt-5 text-white">
        <div class="flex items-center justify-between">
            <a href="<?= site_url(\App\Libraries\Auth::homeFor($authRole)) ?>" class="flex items-center gap-3 text-white">
                <img src="<?= base_url('assets/img/logo-192.png') ?>" alt="" class="h-11 w-11 shrink-0 rounded-full ring-2 ring-white/15">
                <span class="side-text leading-tight">
                    <span class="block text-[13px] font-bold uppercase tracking-[.14em]"><?= esc($schoolShort) ?></span>
                    <span class="block text-[11px] font-medium uppercase tracking-[.08em] text-white/70"><?= esc($office) ?></span>
                </span>
            </a>
            <button type="button" class="btn-close btn-close-white lg:hidden" data-bs-dismiss="offcanvas" data-bs-target="#appSidebar" aria-label="Close menu"></button>
        </div>

        <div class="side-text mt-8 px-3">
            <div class="text-[10.5px] font-bold uppercase tracking-[.16em] text-white/60"><?= esc($workspace[0]) ?></div>
            <div class="mt-1 text-[13px] text-white/85"><?= esc($workspace[1]) ?></div>
        </div>

        <nav class="mt-5 flex flex-col gap-1">
            <?php foreach (nav_items($authRole) as $item):
                [$label, $icon, $url, $patterns] = $item;
                $children = $item[4] ?? [];
                $active   = nav_is(...$patterns);
                $isNotif  = $url === 'notifications';
            ?>
                <a href="<?= site_url($url) ?>" class="side-link <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> title="<?= esc($label, 'attr') ?>">
                    <i class="bi <?= $icon ?>" aria-hidden="true"></i>
                    <span class="side-text flex-1"><?= esc($label) ?></span>
                    <?php if ($isNotif && $notif['unread'] > 0): ?>
                        <span class="side-text rounded-full bg-gold-400 px-2 py-0.5 text-[11px] font-bold text-brand-950"><?= $notif['unread'] ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($children !== []): ?>
                    <div class="side-text flex flex-col">
                        <?php foreach ($children as [$cLabel, $cUrl, $cPatterns]): ?>
                            <a href="<?= site_url($cUrl) ?>" class="side-sublink <?= nav_is(...$cPatterns) ? 'active' : '' ?>"><?= esc($cLabel) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="mt-auto pt-6">
            <div class="mb-3 border-t border-white/10"></div>
            <form action="<?= site_url('logout') ?>" method="post" class="mb-3">
                <?= csrf_field() ?>
                <button type="submit" class="side-link w-full bg-transparent text-left" title="Sign out">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="side-text">Sign out</span>
                </button>
            </form>
            <a href="<?= site_url('profile') ?>" class="flex items-center gap-3 rounded-xl bg-white/[.06] p-3 text-white hover:bg-white/10">
                <span class="avatar h-9 w-9 bg-gold-400 text-[13px] text-brand-950"><?= esc(initials($userName)) ?></span>
                <span class="side-text min-w-0 flex-1 leading-tight">
                    <span class="block truncate text-sm font-semibold"><?= esc($userName) ?></span>
                    <span class="block text-xs text-white/60"><?= esc($roleLabel) ?></span>
                </span>
                <i class="side-text bi bi-chevron-right text-xs text-white/50" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</aside>

<div class="app-main flex min-h-screen flex-col lg:pl-[246px]">
    <!-- Top bar -->
    <header class="no-print sticky top-0 z-20 border-b border-line bg-[#f4f7f3]/95 backdrop-blur">
        <div class="flex items-start gap-3 px-4 py-4 md:px-8">
            <button type="button" class="btn btn-light mt-1 h-10 w-10 shrink-0 p-0" data-sidebar-toggle aria-label="Toggle navigation">
                <i class="bi bi-list text-xl" aria-hidden="true"></i>
            </button>
            <div class="min-w-0 flex-1">
                <div class="eyebrow"><?= esc($eyebrow ?? $defaultEyebrow) ?></div>
                <h1 class="page-title truncate"><?= esc($title ?? 'Dashboard') ?></h1>
                <?php if (! empty($subtitle)): ?>
                    <p class="mt-0.5 hidden text-[15px] text-ink-muted sm:block"><?= esc($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="flex shrink-0 items-center gap-2 pt-1">
                <div class="hidden md:flex md:items-center md:gap-2"><?= $this->renderSection('headerActions') ?></div>

                <div class="dropdown">
                    <button type="button" class="btn btn-light relative h-10 w-10 p-0" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications<?= $notif['unread'] ? ', ' . $notif['unread'] . ' unread' : '' ?>">
                        <i class="bi bi-bell text-lg" aria-hidden="true"></i>
                        <?php if ($notif['unread'] > 0): ?>
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-[#c2412f] px-1 text-[10px] font-bold text-white"><?= $notif['unread'] > 9 ? '9+' : $notif['unread'] ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end w-[340px] max-w-[92vw]">
                        <div class="flex items-center justify-between px-3 py-2">
                            <span class="text-sm font-bold">Notifications</span>
                            <?php if ($notif['unread'] > 0): ?>
                                <form action="<?= site_url('notifications/read-all') ?>" method="post"><?= csrf_field() ?>
                                    <button class="bg-transparent p-0 text-xs font-semibold text-brand-700 hover:underline" type="submit">Mark all read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if ($notif['items'] === []): ?>
                            <div class="px-3 py-6 text-center text-sm text-ink-muted">You're all caught up.</div>
                        <?php endif; ?>
                        <?php foreach ($notif['items'] as $n): ?>
                            <a class="dropdown-item flex gap-3 whitespace-normal <?= $n['read_at'] ? '' : 'bg-brand-50/70' ?>" href="<?= site_url('notifications/' . $n['id'] . '/open') ?>">
                                <i class="bi <?= notification_icon($n['type']) ?> mt-0.5" aria-hidden="true"></i>
                                <span class="min-w-0">
                                    <span class="block text-[13px] font-semibold text-ink"><?= esc($n['title']) ?></span>
                                    <span class="line-clamp-2 block text-xs text-ink-muted"><?= esc($n['message']) ?></span>
                                    <span class="block text-[11px] text-ink-faint"><?= time_ago($n['created_at']) ?></span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                        <div class="mt-1 border-t border-line pt-1">
                            <a class="dropdown-item text-center font-semibold text-brand-700" href="<?= site_url('notifications') ?>">View all notifications</a>
                        </div>
                    </div>
                </div>

                <div class="dropdown">
                    <button type="button" class="avatar h-10 w-10 text-[13px]" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu"><?= esc(initials($userName)) ?></button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="px-3 py-2">
                            <div class="text-sm font-semibold"><?= esc($userName) ?></div>
                            <div class="text-xs text-ink-muted"><?= esc($roleLabel) ?> · <?= esc($authUser['username']) ?></div>
                        </div>
                        <a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person me-2"></i>Profile &amp; password</a>
                        <form action="<?= site_url('logout') ?>" method="post"><?= csrf_field() ?>
                            <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main id="main" class="flex-1 px-4 py-6 md:px-8 md:py-8" tabindex="-1">
        <?php if (session('errors')): ?>
            <div class="alert alert-danger mb-5 rounded-xl" role="alert">
                <div class="mb-1 font-semibold"><i class="bi bi-exclamation-octagon me-1"></i>Please fix the following:</div>
                <ul class="mb-0 ps-4 text-sm">
                    <?php foreach ((array) session('errors') as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </main>
</div>

<?= $this->include('partials/shared_modals') ?>

<script src="<?= base_url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
