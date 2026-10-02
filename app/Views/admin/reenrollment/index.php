<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$sel   = $selected;
$chips = ['review' => 'Needs review', 'reupload' => 'Re-upload', 'approved' => 'Approved', 'all' => 'All'];
?>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
    <div class="surface grid min-w-0 overflow-hidden lg:grid-cols-[minmax(0,320px)_minmax(0,1fr)]">
        <div class="border-b border-line lg:border-b-0 lg:border-r">
            <div class="flex flex-wrap gap-2 p-4">
                <?php foreach ($chips as $k => $label): ?>
                    <a href="<?= site_url('admin/reenrollment?filter=' . $k) ?>" class="rounded-full px-3 py-1 text-xs font-bold <?= $filter === $k ? 'bg-brand-800 text-white' : 'bg-[#eceeea] text-ink-soft hover:bg-brand-50' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
            <ul class="m-0 list-none border-t border-line p-0">
                <?php foreach ($rows as $r): $active = $sel && (int) $sel['id'] === (int) $r['id']; ?>
                    <li><a href="<?= site_url('admin/reenrollment?filter=' . $filter . '&id=' . $r['id']) ?>" class="block border-b border-line px-4 py-3.5 text-ink hover:bg-brand-50/60 <?= $active ? 'bg-brand-50 shadow-[inset_3px_0_0_#1d5a3f]' : '' ?>">
                        <div class="flex justify-between gap-2"><span class="font-semibold"><?= esc(person_name($r)) ?></span><span class="text-xs text-ink-muted"><?= time_ago($r['submitted_at']) ?></span></div>
                        <div class="text-xs text-ink-muted"><?= esc($r['subject_code']) ?> · <?= esc($r['student_number']) ?></div>
                        <div class="mt-2 flex items-center justify-between"><?= status_badge($r['status']) ?><span class="font-semibold"><?= peso($r['amount']) ?></span></div>
                    </a></li>
                <?php endforeach; ?>
                <?php if ($rows === []): ?><li class="empty-state"><i class="bi bi-inbox"></i>Nothing here.</li><?php endif; ?>
            </ul>
        </div>

        <div class="min-w-0 p-6">
            <?php if (! $sel): ?>
                <div class="empty-state"><i class="bi bi-arrow-repeat"></i>Select a re-enrollment receipt.</div>
            <?php else:
                $reviewable = in_array($sel['status'], ['submitted', 'under_review'], true);
                $fileUrl    = site_url('files/reenrollment-receipt/' . $sel['id']);
            ?>
                <div class="flex flex-wrap items-center gap-3"><h3 class="text-lg font-bold"><?= esc(person_name($sel)) ?></h3><?= status_badge($sel['status']) ?></div>
                <div class="text-sm text-ink-muted"><?= esc($sel['student_number']) ?> · <?= esc($sel['program_code']) ?> · <?= esc($sel['reference_no']) ?></div>

                <div class="mt-4 rounded-xl border border-[#f1cfc7] bg-[#fdf5f3] p-4 text-sm">
                    <div class="flex flex-wrap items-center gap-2"><?= status_badge('FAILED') ?><span class="font-semibold"><?= esc($sel['subject_code']) ?> · <?= esc($sel['subject_title']) ?></span></div>
                    <div class="mt-1 text-ink-soft">Teacher: <?= esc($sel['teacher_name']) ?><?= $sel['final_grade'] ? ' · Grade ' . esc($sel['final_grade']) : '' ?> · <?= esc(term_label($sel, true)) ?></div>
                </div>

                <button type="button" class="mt-4 flex h-[300px] w-full items-center justify-center overflow-hidden rounded-2xl border border-line bg-[#ebe8e1] p-3"
                        data-bs-toggle="modal" data-bs-target="#previewModal" data-preview-url="<?= $fileUrl ?>" data-preview-type="<?= esc($sel['mime_type'], 'attr') ?>" data-preview-title="Re-enrollment receipt <?= esc($sel['or_number'], 'attr') ?>">
                    <?php if (str_contains($sel['mime_type'], 'pdf')): ?>
                        <span class="flex flex-col items-center gap-2"><i class="bi bi-file-earmark-pdf text-5xl text-[#a3362a]"></i><?= esc($sel['original_name']) ?></span>
                    <?php else: ?>
                        <img src="<?= $fileUrl ?>" alt="Re-enrollment receipt" class="max-h-full max-w-full rounded bg-white object-contain shadow-lift">
                    <?php endif; ?>
                </button>

                <dl class="mb-0 mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="kv-label">OR number</dt><dd class="mb-0 font-mono font-semibold"><?= esc($sel['or_number']) ?></dd></div>
                    <div><dt class="kv-label">Amount</dt><dd class="mb-0 font-semibold"><?= peso($sel['amount']) ?></dd></div>
                    <div><dt class="kv-label">Payment date</dt><dd class="mb-0 font-semibold"><?= fmt_date($sel['payment_date']) ?></dd></div>
                </dl>

                <?php if ($reviewable): ?>
                    <div class="mt-5 grid gap-3 sm:grid-cols-[2fr_1fr]">
                        <form action="<?= site_url('admin/reenrollment/' . $sel['id'] . '/approve') ?>" method="post" class="grid"
                              data-confirm="Confirm re-enrollment payment for <?= esc($sel['subject_code'], 'attr') ?>? The FAILED subject becomes resolved on this clearance; the FAILED record stays in history." data-confirm-title="Approve re-enrollment" data-confirm-button="Approve">
                            <?= csrf_field() ?><button class="btn btn-primary py-2.5" type="submit"><i class="bi bi-check-lg"></i>Approve re-enrollment</button>
                        </form>
                        <button class="btn btn-outline-danger py-2.5" type="button" data-bs-toggle="modal" data-bs-target="#reuploadModal"><i class="bi bi-arrow-counterclockwise"></i>Request re-upload</button>
                    </div>
                    <?= view('partials/reupload_modal', ['action' => site_url('admin/reenrollment/' . $sel['id'] . '/reupload'), 'who' => person_name($sel)]) ?>
                <?php elseif ($sel['status'] === 'reupload_required'): ?>
                    <p class="mt-4 text-sm text-[#8a4414]">Re-upload requested: “<?= esc($sel['reupload_reason']) ?>”</p>
                <?php else: ?>
                    <p class="mt-4 text-sm text-[#1f6a3f]"><i class="bi bi-check-circle-fill"></i> Approved <?= fmt_datetime($sel['reviewed_at']) ?> — re-enrollment confirmed.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <section class="surface h-fit overflow-hidden">
        <div class="border-b border-line p-5"><div class="eyebrow">Waiting on students</div><h2 class="section-title mt-1">Re-enrollment required</h2><p class="mt-1 text-sm text-ink-muted">Failed subjects with no receipt uploaded yet.</p></div>
        <ul class="m-0 list-none p-0">
            <?php foreach ($awaiting as $a): ?>
                <li><a class="list-row" href="<?= site_url('admin/clearances/' . $a['clearance_id']) ?>">
                    <span class="min-w-0 flex-1"><span class="block font-semibold"><?= esc(person_name($a)) ?></span><span class="block text-xs text-ink-muted"><?= esc($a['subject_code']) ?> · failed <?= fmt_date($a['decided_at']) ?></span></span>
                    <?= status_badge('RE_ENROLLMENT_REQUIRED', 'Required') ?>
                </a></li>
            <?php endforeach; ?>
            <?php if ($awaiting === []): ?><li class="empty-state py-8"><i class="bi bi-check2-all"></i>None.</li><?php endif; ?>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
