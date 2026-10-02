<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?= view('partials/temp_password') ?>
<div class="grid gap-6 xl:grid-cols-2">
    <section class="surface p-5">
        <div class="eyebrow">School profile</div>
        <h2 class="section-title mt-1">Branding &amp; limits</h2>
        <form action="<?= site_url('admin/settings') ?>" method="post" class="mt-4 grid gap-4 sm:grid-cols-2">
            <?= csrf_field() ?>
            <div class="sm:col-span-2"><label class="form-label" for="school_name">School name</label><input class="form-control" id="school_name" name="school_name" value="<?= esc(old('school_name', $settings['school_name'])) ?>" required maxlength="120"></div>
            <div><label class="form-label" for="school_short_name">Short name (sidebar)</label><input class="form-control" id="school_short_name" name="school_short_name" value="<?= esc(old('school_short_name', $settings['school_short_name'])) ?>" required maxlength="40"></div>
            <div><label class="form-label" for="office_name">Office name</label><input class="form-control" id="office_name" name="office_name" value="<?= esc(old('office_name', $settings['office_name'])) ?>" required maxlength="80"></div>
            <div class="sm:col-span-2"><label class="form-label" for="school_address">Address</label><input class="form-control" id="school_address" name="school_address" value="<?= esc(old('school_address', $settings['school_address'])) ?>" maxlength="200"></div>
            <div><label class="form-label" for="registrar_name">Registrar</label><input class="form-control" id="registrar_name" name="registrar_name" value="<?= esc(old('registrar_name', $settings['registrar_name'])) ?>" maxlength="120"></div>
            <div><label class="form-label" for="max_upload_mb">Max upload size (MB)</label><input class="form-control" type="number" min="1" max="20" id="max_upload_mb" name="max_upload_mb" value="<?= esc(old('max_upload_mb', $settings['max_upload_mb'])) ?>" required>
                <div class="form-text">Also limited by PHP's upload_max_filesize (<?= esc(ini_get('upload_max_filesize')) ?>).</div></div>
            <div class="sm:col-span-2"><button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i>Save settings</button></div>
        </form>
    </section>

    <section class="surface overflow-hidden">
        <div class="border-b border-line p-5"><div class="eyebrow">Academic programs</div><h2 class="section-title mt-1">Programs</h2></div>
        <ul class="m-0 list-none p-0">
            <?php foreach ($programs as $p): ?>
                <li class="border-b border-line px-5 py-3">
                    <form action="<?= site_url('admin/settings/programs/' . $p['id']) ?>" method="post" class="flex flex-wrap items-center gap-2">
                        <?= csrf_field() ?>
                        <input class="form-control form-control-sm w-24 font-mono" name="code" value="<?= esc($p['code']) ?>" aria-label="Code" required>
                        <input class="form-control form-control-sm min-w-[180px] flex-1" name="name" value="<?= esc($p['name']) ?>" aria-label="Name" required>
                        <input class="form-control form-control-sm w-44" name="department" value="<?= esc($p['department'] ?? '') ?>" aria-label="Department" placeholder="Department">
                        <div class="form-check mb-0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="pa<?= $p['id'] ?>" <?= (int) $p['is_active'] ? 'checked' : '' ?>><label class="form-check-label text-sm" for="pa<?= $p['id'] ?>">Active</label></div>
                        <span class="text-xs text-ink-muted"><?= (int) $p['student_count'] ?> students</span>
                        <button class="btn btn-light btn-sm" type="submit">Save</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <form action="<?= site_url('admin/settings/programs') ?>" method="post" class="flex flex-wrap items-end gap-2 bg-[#f9faf6] p-5">
            <?= csrf_field() ?>
            <div><label class="form-label" for="pCode">Code</label><input class="form-control w-28" id="pCode" name="code" required maxlength="20"></div>
            <div class="min-w-[200px] flex-1"><label class="form-label" for="pName">Program name</label><input class="form-control" id="pName" name="name" required maxlength="150"></div>
            <div><label class="form-label" for="pDept">Department</label><input class="form-control" id="pDept" name="department" maxlength="120"></div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i>Add</button>
        </form>
    </section>

    <section class="surface overflow-hidden xl:col-span-2">
        <div class="border-b border-line p-5"><div class="eyebrow">Access</div><h2 class="section-title mt-1">Administrator accounts</h2></div>
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_380px]">
            <table class="table">
                <thead><tr><th class="ps-5">Name</th><th>Username</th><th>Last sign-in</th><th class="pe-5">Status</th></tr></thead>
                <tbody>
                <?php foreach ($admins as $a): ?>
                    <tr><td class="ps-5 font-semibold"><?= esc(person_name($a)) ?></td><td class="font-mono"><?= esc($a['username']) ?></td><td><?= fmt_datetime($a['last_login_at']) ?></td><td class="pe-5"><?= status_badge((int) $a['is_active'] ? 'active' : 'inactive') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <form action="<?= site_url('admin/settings/admins') ?>" method="post" class="flex flex-col gap-3 border-t border-line bg-[#f9faf6] p-5 lg:border-l lg:border-t-0">
                <?= csrf_field() ?>
                <div class="font-bold">Add administrator</div>
                <div><label class="form-label" for="aUser">Username</label><input class="form-control" id="aUser" name="username" required minlength="4" maxlength="50"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="form-label" for="aFirst">First name</label><input class="form-control" id="aFirst" name="first_name" required></div>
                    <div><label class="form-label" for="aLast">Last name</label><input class="form-control" id="aLast" name="last_name" required></div>
                </div>
                <div><label class="form-label" for="aEmail">Personal email (used to sign in)</label><input class="form-control" type="email" id="aEmail" name="email" required></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i>Create administrator</button>
            </form>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
