<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<section class="surface overflow-hidden">
    <form class="flex flex-wrap items-center gap-3 border-b border-line p-4" method="get">
        <label class="visually-hidden" for="q">Search students</label>
        <input class="form-control max-w-sm" id="q" name="q" value="<?= esc($q) ?>" placeholder="Search name, student number or subject code">
        <button class="btn btn-light" type="submit"><i class="bi bi-search"></i>Search</button>
        <span class="ms-auto text-sm text-ink-muted"><?= count($rows) ?> enrollment(s) · <?= $term ? esc(term_label($term)) : '' ?></span>
    </form>
    <div class="overflow-x-auto">
        <table class="table table-hover">
            <thead><tr><th class="ps-5">Student</th><th>Program</th><th>Subject</th><th>Clearance status</th><th class="pe-5"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="ps-5"><div class="font-semibold"><?= esc(person_name($r, true)) ?></div><div class="font-mono text-xs text-ink-muted"><?= esc($r['student_number']) ?></div></td>
                    <td><?= esc($r['program_code']) ?> · <?= year_level_label($r['year_level']) ?></td>
                    <td><?= esc($r['code']) ?><div class="text-xs text-ink-muted"><?= esc($r['title']) ?></div></td>
                    <td>
                        <?php if (! $r['cs_id']): ?>
                            <?= status_badge($r['clearance_status'] ?? 'not_started', $r['clearance_status'] ? 'Card not issued · ' . status_meta($r['clearance_status'])['label'] : 'Clearance not started') ?>
                        <?php elseif ($r['cs_status'] === 'PASSED' && ! $r['signed_at']): ?>
                            <?= status_badge('AWAITING_SIGNATURE') ?>
                        <?php else: ?>
                            <?= status_badge(subject_status_key(['status' => $r['cs_status'], 'reenrollment_status' => $r['reenrollment_status']])) ?>
                        <?php endif; ?>
                    </td>
                    <td class="pe-5 text-end">
                        <?php if ($r['cs_id']): ?><a class="btn btn-light btn-sm" href="<?= site_url('teacher/subjects/' . $r['offering_id']) ?>?cs=<?= $r['cs_id'] ?>">Open</a><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><tr><td colspan="5" class="py-10 text-center text-ink-muted">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?= $this->endSection() ?>
