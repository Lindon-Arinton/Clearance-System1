<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="flex min-h-screen items-center justify-center p-4">
<div class="surface max-w-md p-8 text-center">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f9e1dc] text-2xl text-[#a3362a]"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></span>
    <h1 class="mt-4 font-serif text-3xl text-brand-900">Access denied</h1>
    <p class="mt-2 text-ink-muted">Your account does not have permission to open this page. Access is limited to your assigned role and records.</p>
    <a href="<?= site_url('/') ?>" class="btn btn-primary mt-6">Go to my dashboard</a>
</div>
</body>
</html>
