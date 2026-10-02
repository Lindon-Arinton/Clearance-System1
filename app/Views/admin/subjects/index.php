<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/offerings') ?>" class="btn btn-soft h-10 px-3 text-sm"><i class="bi bi-diagram-3"></i>Offerings</a>
<button class="btn btn-primary h-10 px-3 text-sm" data-bs-toggle="modal" data-bs-target="#subjectModal" data-mode="create"><i class="bi bi-plus-lg"></i>New subject</button>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4 flex gap-6 border-b border-line">
    <a href="<?= site_url('admin/subjects') ?>" class="tab-link active">Catalogue</a>
    <a href="<?= site_url('admin/offerings') ?>" class="tab-link">Offerings &amp; teacher assignment</a>
</div>
<section class="surface overflow-hidden">
    <form method="get" class="flex gap-3 border-b border-line p-4">
        <label class="visually-hidden" for="q">Search subjects</label>
        <input class="form-control max-w-sm" id="q" name="q" value="<?= esc($q) ?>" placeholder="Search code or title">
        <button class="btn btn-light" type="submit"><i class="bi bi-search"></i>Search</button>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Code</th><th>Title</th><th>Units</th><th>Program</th><th class="text-center">Offerings</th><th>Status</th><th class="pe-5"></th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $s): ?>
                <tr>
                    <td class="ps-5 font-mono font-semibold"><?= esc($s['code']) ?></td>
                    <td><?= esc($s['title']) ?></td>
                    <td><?= rtrim(rtrim($s['units'], '0'), '.') ?></td>
                    <td><?= esc($s['program_code'] ?? 'General') ?></td>
                    <td class="text-center"><?= (int) $s['offering_count'] ?></td>
                    <td><?= status_badge((int) $s['is_active'] ? 'active' : 'inactive') ?></td>
                    <td class="pe-5 text-end">
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#subjectModal" data-mode="edit"
                                data-subject='<?= esc(json_encode(['id' => $s['id'], 'code' => $s['code'], 'title' => $s['title'], 'units' => $s['units'], 'program_id' => $s['program_id'], 'description' => $s['description'], 'is_active' => (int) $s['is_active']]), 'attr') ?>'>Edit</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>

<div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= site_url('admin/subjects') ?>" id="subjectForm">
            <?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title" id="subjectModalTitle">New subject</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body grid gap-3 sm:grid-cols-2">
                <div><label class="form-label" for="sCode">Code</label><input class="form-control font-mono" id="sCode" name="code" required maxlength="20" placeholder="IT301"></div>
                <div><label class="form-label" for="sUnits">Units</label><input class="form-control" id="sUnits" name="units" type="number" step="0.5" min="0.5" max="10" value="3" required></div>
                <div class="sm:col-span-2"><label class="form-label" for="sTitle">Title</label><input class="form-control" id="sTitle" name="title" required maxlength="150"></div>
                <div class="sm:col-span-2"><label class="form-label" for="sProgram">Program</label>
                    <select class="form-select" id="sProgram" name="program_id"><option value="">General education (all programs)</option>
                        <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['code']) ?></option><?php endforeach; ?></select></div>
                <div class="sm:col-span-2"><label class="form-label" for="sDesc">Description</label><textarea class="form-control" id="sDesc" name="description" rows="2" maxlength="1000"></textarea></div>
                <div class="form-check sm:col-span-2 ms-1"><input class="form-check-input" type="checkbox" id="sActive" name="is_active" value="1" checked><label class="form-check-label" for="sActive">Active</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save subject</button></div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.getElementById('subjectModal').addEventListener('show.bs.modal', (e) => {
    const f = document.getElementById('subjectForm');
    const edit = e.relatedTarget && e.relatedTarget.dataset.mode === 'edit';
    const s = edit ? JSON.parse(e.relatedTarget.dataset.subject) : { code: '', title: '', units: 3, program_id: '', description: '', is_active: 1 };
    f.action = '<?= site_url('admin/subjects') ?>' + (edit ? '/' + s.id : '');
    document.getElementById('subjectModalTitle').textContent = edit ? 'Edit ' + s.code : 'New subject';
    f.code.value = s.code; f.title.value = s.title; f.units.value = s.units; f.program_id.value = s.program_id || '';
    f.description.value = s.description || ''; f.is_active.checked = !!s.is_active;
  });
</script>
<?= $this->endSection() ?>
