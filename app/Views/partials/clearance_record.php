<?php
/**
 * Complete clearance record: card, tuition receipts, re-enrollment and INC history.
 *
 * @var array $bundle        ClearanceService::bundle()
 * @var array $reenrollments latest re-enrollment receipt per clearance subject id
 * @var array $requirements  INC requirements per clearance subject id
 */
$clearance = $bundle['clearance'];
$incRows   = array_filter($bundle['subjects'], static fn ($cs) => isset($requirements[(int) $cs['id']]));
$failed    = array_filter($bundle['subjects'], static fn ($cs) => $cs['status'] === 'FAILED');
?>
<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="stat-card"><span class="stat-label">Status</span><span class="mt-1"><?= status_badge($clearance['status'] === 'in_progress' ? 'incomplete' : $clearance['status']) ?></span></div>
    <div class="stat-card"><span class="stat-label">Started</span><span class="kv-value"><?= fmt_date($clearance['started_at']) ?></span></div>
    <div class="stat-card"><span class="stat-label">Card issued</span><span class="kv-value"><?= fmt_date($clearance['card_issued_at']) ?></span></div>
    <div class="stat-card"><span class="stat-label">Completed</span><span class="kv-value"><?= fmt_datetime($clearance['completed_at']) ?></span>
        <span class="stat-hint"><?= (int) $clearance['enrollment_eligible'] ? 'Eligible for next semester' : 'Not yet eligible' ?></span></div>
</div>

<?php if (in_array($clearance['status'], ['in_progress', 'completed'], true)): ?>
    <?= view('partials/clearance_card', ['clearance' => $clearance, 'subjects' => $bundle['subjects'], 'snapshot' => $bundle['snapshot'], 'reenrollments' => $reenrollments]) ?>
<?php endif; ?>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="surface p-5">
        <div class="eyebrow">Tuition receipt</div>
        <h2 class="section-title mt-1">Receipt record</h2>
        <ul class="mt-4 flex list-none flex-col gap-2 p-0">
            <?php foreach ($bundle['receipts'] as $r): ?>
                <li class="rounded-xl border border-line bg-[#f9faf6] p-3 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold"><?= esc($r['or_number']) ?></span><span class="text-ink-muted">Attempt #<?= (int) $r['attempt_no'] ?> · <?= peso($r['amount']) ?></span>
                        <span class="ms-auto"><?= status_badge($r['status']) ?></span>
                    </div>
                    <div class="mt-1 text-xs text-ink-muted">Submitted <?= fmt_datetime($r['submitted_at']) ?><?= $r['reviewed_at'] ? ' · Reviewed ' . fmt_datetime($r['reviewed_at']) : '' ?></div>
                    <?php if ($r['reupload_reason']): ?><div class="mt-1 text-xs text-[#8a4414]">“<?= esc($r['reupload_reason']) ?>”</div><?php endif; ?>
                    <button type="button" class="no-print mt-2 bg-transparent p-0 font-semibold text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#previewModal"
                            data-preview-url="<?= site_url('files/tuition-receipt/' . $r['id']) ?>" data-preview-type="<?= esc($r['mime_type'], 'attr') ?>" data-preview-title="Receipt <?= esc($r['or_number'], 'attr') ?>"><i class="bi bi-eye"></i> View file</button>
                </li>
            <?php endforeach; ?>
            <?php if ($bundle['receipts'] === []): ?><li class="text-sm text-ink-muted">No receipt uploaded.</li><?php endif; ?>
        </ul>
    </section>

    <section class="surface p-5">
        <div class="eyebrow">INC &amp; re-enrollment</div>
        <h2 class="section-title mt-1">Resolution record</h2>
        <?php if ($incRows === [] && $failed === []): ?>
            <p class="mt-3 text-sm text-ink-muted">No INC or failed subjects on this clearance.</p>
        <?php endif; ?>
        <ul class="mt-4 flex list-none flex-col gap-3 p-0">
            <?php foreach ($incRows as $cs): ?>
                <li class="rounded-xl border border-line p-3 text-sm">
                    <div class="flex items-center justify-between gap-2"><span class="font-semibold"><?= esc($cs['subject_title']) ?></span><?= status_badge($cs['status'] === 'INC' ? 'INC' : 'PASSED', $cs['status'] === 'INC' ? null : 'INC → Passed') ?></div>
                    <ul class="mb-0 mt-2 ps-4 text-ink-soft">
                        <?php foreach ($requirements[(int) $cs['id']] as $req): ?>
                            <li><?= esc($req['description']) ?> — <?= $req['status'] === 'completed' ? 'verified ' . fmt_date($req['verified_at']) : 'pending' ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
            <?php foreach ($failed as $cs): $re = $reenrollments[(int) $cs['id']] ?? null; ?>
                <li class="rounded-xl border border-line p-3 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2"><span class="font-semibold"><?= esc($cs['subject_title']) ?></span><span class="flex gap-1"><?= status_badge('FAILED') ?><?= status_badge(subject_status_key($cs)) ?></span></div>
                    <div class="mt-1 text-xs text-ink-muted">
                        Marked FAILED <?= fmt_date($cs['decided_at']) ?> by <?= esc($cs['teacher_name']) ?>
                        <?= $re ? ' · Receipt ' . esc($re['or_number']) . ' (' . esc(status_meta($re['status'])['label']) . ')' : '' ?>
                        <?= $cs['reenrollment_confirmed_at'] ? ' · Confirmed ' . fmt_date($cs['reenrollment_confirmed_at']) : '' ?>
                    </div>
                    <?php if ($re): ?>
                        <button type="button" class="no-print mt-2 bg-transparent p-0 font-semibold text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#previewModal"
                                data-preview-url="<?= site_url('files/reenrollment-receipt/' . $re['id']) ?>" data-preview-type="<?= esc($re['mime_type'], 'attr') ?>" data-preview-title="Re-enrollment receipt <?= esc($re['or_number'], 'attr') ?>"><i class="bi bi-eye"></i> View receipt</button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
