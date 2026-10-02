<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
    <section class="surface overflow-hidden">
        <div class="border-b border-line p-5"><div class="eyebrow">Semesters</div><h2 class="section-title mt-1">School terms</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th class="ps-5">Term</th><th>Dates · Status</th><th class="text-center">Clearances</th><th class="pe-5"></th></tr></thead>
                <tbody>
                <?php foreach ($terms as $t): ?>
                    <tr class="<?= (int) $t['is_current'] ? 'bg-brand-50/60' : '' ?>">
                        <td class="ps-5">
                            <div class="font-semibold"><?= esc(term_label($t)) ?></div>
                            <?php if ((int) $t['is_current']): ?><span class="badge-status badge-green mt-1"><i class="bi bi-star-fill" aria-hidden="true"></i>Current term</span><?php endif; ?>
                            <div class="text-xs text-ink-muted"><?= (int) $t['offering_count'] ?> offerings</div>
                        </td>
                        <td>
                            <form action="<?= site_url('admin/terms/' . $t['id']) ?>" method="post" class="flex flex-wrap items-center gap-2">
                                <?= csrf_field() ?>
                                <input class="form-control form-control-sm w-auto" type="date" name="start_date" value="<?= esc($t['start_date']) ?>" aria-label="Start date" required>
                                <input class="form-control form-control-sm w-auto" type="date" name="end_date" value="<?= esc($t['end_date']) ?>" aria-label="End date" required>
                                <select class="form-select form-select-sm w-auto" name="status" aria-label="Status">
                                    <?php foreach (['upcoming', 'open', 'closed'] as $s): ?><option value="<?= $s ?>" <?= $t['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
                                </select>
                                <button class="btn btn-light btn-sm" type="submit">Save</button>
                            </form>
                        </td>
                        <td class="text-center"><?= (int) $t['completed_count'] ?>/<?= (int) $t['clearance_count'] ?><div class="text-xs text-ink-muted">completed</div></td>
                        <td class="pe-5 text-end">
                            <?php if (! (int) $t['is_current'] && $t['status'] !== 'closed'): ?>
                                <form action="<?= site_url('admin/terms/' . $t['id'] . '/current') ?>" method="post" data-confirm="Make <?= esc(term_label($t), 'attr') ?> the current term? Students will start new clearances for this term; existing clearances stay in history." data-confirm-button="Make current">
                                    <?= csrf_field() ?><button class="btn btn-soft btn-sm" type="submit">Make current</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-col gap-6">
        <section class="surface p-5">
            <div class="eyebrow">Add semester</div>
            <form action="<?= site_url('admin/terms') ?>" method="post" class="mt-3 flex flex-col gap-3">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="form-label" for="school_year_id">School year</label>
                        <select class="form-select" id="school_year_id" name="school_year_id" required><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>"><?= esc($y['name']) ?></option><?php endforeach; ?></select></div>
                    <div><label class="form-label" for="semester">Semester</label>
                        <select class="form-select" id="semester" name="semester"><option value="1st">1st Semester</option><option value="2nd">2nd Semester</option><option value="summer">Summer</option></select></div>
                    <div><label class="form-label" for="tStart">Start</label><input class="form-control" type="date" id="tStart" name="start_date" required></div>
                    <div><label class="form-label" for="tEnd">End</label><input class="form-control" type="date" id="tEnd" name="end_date" required></div>
                </div>
                <div><label class="form-label" for="tStatus">Status</label><select class="form-select" id="tStatus" name="status"><option value="upcoming">Upcoming</option><option value="open">Open</option></select></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i>Add semester</button>
            </form>
        </section>

        <section class="surface p-5">
            <div class="eyebrow">Add school year</div>
            <form action="<?= site_url('admin/terms/years') ?>" method="post" class="mt-3 flex flex-col gap-3">
                <?= csrf_field() ?>
                <div><label class="form-label" for="yName">School year</label><input class="form-control" id="yName" name="name" placeholder="2027-2028" pattern="\d{4}-\d{4}" required></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="form-label" for="yStart">Start</label><input class="form-control" type="date" id="yStart" name="start_date" required></div>
                    <div><label class="form-label" for="yEnd">End</label><input class="form-control" type="date" id="yEnd" name="end_date" required></div>
                </div>
                <button class="btn btn-soft" type="submit"><i class="bi bi-plus-lg"></i>Add school year</button>
            </form>
            <ul class="mb-0 mt-4 flex list-none flex-wrap gap-2 p-0">
                <?php foreach ($years as $y): ?><li class="rounded-lg border border-line px-2.5 py-1 text-sm"><?= esc($y['name']) ?></li><?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
