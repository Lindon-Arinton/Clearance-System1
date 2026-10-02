<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<section class="surface overflow-hidden">
    <form method="get" class="flex flex-wrap items-end gap-3 border-b border-line p-4">
        <div class="min-w-[200px] flex-1"><label class="form-label" for="q">Search</label><input class="form-control" id="q" name="q" value="<?= esc($q) ?>" placeholder="Name, student number or reference"></div>
        <div><label class="form-label" for="term">Term</label><select class="form-select" id="term" name="term">
            <?php foreach ($terms as $t): ?><option value="<?= $t['id'] ?>" <?= $termId === (int) $t['id'] ? 'selected' : '' ?>><?= esc(term_label($t, true)) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">Any</option>
            <?php foreach (['awaiting_receipt', 'receipt_review', 'reupload_required', 'in_progress', 'completed'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc(status_meta($s)['label']) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="program">Program</label><select class="form-select" id="program" name="program"><option value="">All</option>
            <?php foreach ($programs as $p): ?><option value="<?= $p['id'] ?>" <?= $program === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['code']) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-light" type="submit"><i class="bi bi-funnel"></i>Filter</button>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Student</th><th>Reference</th><th>Status</th><th>Progress</th><th>Open items</th><th>Updated</th><th class="pe-5"></th></tr></thead>
            <tbody>
            <?php foreach ($clearances as $c): $pct = $c['subjects'] ? round($c['resolved'] / $c['subjects'] * 100) : 0; ?>
                <tr>
                    <td class="ps-5"><div class="font-semibold"><?= esc(person_name($c, true)) ?></div><div class="text-xs text-ink-muted"><?= esc($c['student_number']) ?> · <?= esc($c['program_code']) ?></div></td>
                    <td class="font-mono text-xs"><?= esc($c['reference_no']) ?></td>
                    <td><?= status_badge($c['status']) ?></td>
                    <td class="min-w-[140px]"><div class="flex items-center gap-2"><div class="progress-track flex-1"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div><span class="text-xs text-ink-muted"><?= (int) $c['resolved'] ?>/<?= (int) $c['subjects'] ?></span></div></td>
                    <td class="text-xs"><?= (int) $c['inc'] ? '<span class="badge-status badge-orange">' . (int) $c['inc'] . ' INC</span> ' : '' ?><?= (int) $c['failed_open'] ? '<span class="badge-status badge-red">' . (int) $c['failed_open'] . ' failed</span>' : '' ?></td>
                    <td class="text-xs text-ink-muted"><?= time_ago($c['updated_at']) ?></td>
                    <td class="pe-5 text-end"><a class="btn btn-light btn-sm" href="<?= site_url('admin/clearances/' . $c['id']) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($clearances === []): ?><tr><td colspan="7" class="py-10 text-center text-ink-muted">No clearances match these filters.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>
<?= $this->endSection() ?>
