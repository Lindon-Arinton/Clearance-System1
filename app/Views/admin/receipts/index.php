<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/reenrollment') ?>" class="btn btn-soft h-10 px-3 text-sm"><i class="bi bi-arrow-repeat"></i>Re-enrollment receipts</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$sel   = $selected;
$chips = ['all' => 'All', 'review' => 'Review', 'reupload' => 'Re-upload', 'approved' => 'Approved'];
$qs    = static fn (array $extra) => '?' . http_build_query(array_filter($extra + ['filter' => $filter, 'q' => $q, 'sort' => $sort === 'DESC' ? 'newest' : null]));
$enrolledUnits = array_sum(array_map(static fn ($o) => (float) $o['units'], $enrolled));
?>
<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="stat-card"><span class="stat-label">Awaiting review</span><span class="stat-value"><?= $counts['review'] ?></span><span class="stat-hint">Receipts in your queue</span></div>
    <div class="stat-card"><span class="stat-label">Re-upload requested</span><span class="stat-value text-[#a3362a]"><?= $counts['reupload'] ?></span><span class="stat-hint">Waiting on students</span></div>
    <div class="flex items-center gap-4 rounded-2xl border border-[#cde5d4] bg-[#e7f3ea] p-5">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-paper text-brand-700"><i class="bi bi-activity text-lg" aria-hidden="true"></i></span>
        <div><div class="font-bold text-brand-900">Queue health</div><div class="text-sm text-ink-soft"><?= $counts['review'] === 0 ? 'Queue is clear' : ($counts['review'] <= 10 ? 'On track for today\'s target' : 'Backlog building — prioritise oldest') ?></div></div>
    </div>
</div>

<div class="mb-4 flex flex-wrap items-end justify-between gap-3">
    <div><div class="eyebrow">Registrar desk · <?= date('j F Y') ?></div><h2 class="section-title mt-1 text-[1.75rem]">Receipt review queue</h2><p class="text-sm text-ink-muted">Review the next receipt, confirm its details, and send a clear outcome.</p></div>
    <a href="<?= site_url('admin/receipts') . $qs(['sort' => $sort === 'ASC' ? 'newest' : null]) ?>" class="btn btn-light btn-sm"><i class="bi bi-sort-down"></i><?= $sort === 'ASC' ? 'Oldest first' : 'Newest first' ?></a>
</div>

<div class="surface grid overflow-hidden lg:grid-cols-[minmax(0,390px)_minmax(0,1fr)]">
    <div class="border-b border-line lg:border-b-0 lg:border-r">
        <form method="get" class="p-4">
            <input type="hidden" name="filter" value="<?= esc($filter) ?>">
            <label class="visually-hidden" for="rq">Search student or OR number</label>
            <div class="relative"><i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                <input class="form-control pl-10" id="rq" name="q" value="<?= esc($q) ?>" placeholder="Search student or OR number"></div>
        </form>
        <div class="flex flex-wrap gap-2 px-4 pb-3">
            <?php foreach ($chips as $k => $label): ?>
                <a href="<?= site_url('admin/receipts') . '?' . http_build_query(array_filter(['filter' => $k, 'q' => $q])) ?>"
                   class="rounded-full px-3 py-1 text-xs font-bold <?= $filter === $k ? 'bg-brand-800 text-white' : 'bg-[#eceeea] text-ink-soft hover:bg-brand-50' ?>"><?= $label ?><?= $k === 'review' ? ' ' . $counts['review'] : '' ?></a>
            <?php endforeach; ?>
        </div>
        <ul class="m-0 max-h-[75vh] list-none overflow-y-auto border-t border-line p-0">
            <?php foreach ($rows as $r): $active = $sel && (int) $sel['id'] === (int) $r['id']; ?>
                <li>
                    <a href="<?= site_url('admin/receipts') . $qs(['id' => $r['id']]) ?>" class="block border-b border-line px-4 py-4 text-ink transition hover:bg-brand-50/60 <?= $active ? 'bg-brand-50 shadow-[inset_3px_0_0_#1d5a3f]' : '' ?>" <?= $active ? 'aria-current="true"' : '' ?>>
                        <div class="flex items-start justify-between gap-2"><span class="font-semibold"><?= esc(person_name($r)) ?></span><span class="text-xs text-ink-muted"><?= time_ago($r['submitted_at']) ?></span></div>
                        <div class="text-xs text-ink-muted"><?= esc($r['student_number']) ?> · <?= esc($r['program_code']) ?> · <?= year_level_label($r['year_level']) ?></div>
                        <div class="mt-2 flex items-center justify-between"><?= status_badge($r['status']) ?><span class="font-semibold"><?= peso($r['amount']) ?></span></div>
                    </a>
                </li>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><li class="empty-state"><i class="bi bi-inbox"></i>No receipts in this view.</li><?php endif; ?>
        </ul>
    </div>

    <div class="min-w-0">
        <?php if (! $sel): ?>
            <div class="empty-state h-full"><i class="bi bi-receipt"></i><p class="font-semibold text-ink">Select a receipt</p></div>
        <?php else:
            $reviewable = in_array($sel['status'], ['submitted', 'under_review'], true) && (int) $attempts[0]['id'] === (int) $sel['id'];
            $isPdf      = str_contains($sel['mime_type'], 'pdf');
            $fileUrl    = site_url('files/tuition-receipt/' . $sel['id']);
        ?>
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-6 py-5">
                <div>
                    <div class="flex flex-wrap items-center gap-3"><h3 class="text-lg font-bold"><?= esc(person_name($sel)) ?></h3><?= status_badge($sel['status']) ?></div>
                    <div class="text-sm text-ink-muted"><?= esc($sel['student_number']) ?> · Submitted <?= fmt_datetime($sel['submitted_at']) ?> · Attempt #<?= (int) $sel['attempt_no'] ?></div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light h-10 w-10 p-0" data-bs-toggle="dropdown" aria-label="More actions"><i class="bi bi-three-dots"></i></button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="<?= site_url('admin/clearances/' . $sel['clearance_id']) ?>"><i class="bi bi-clipboard-check me-2"></i>Open clearance</a>
                        <a class="dropdown-item" href="<?= $fileUrl ?>?download=1"><i class="bi bi-download me-2"></i>Download file</a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-5 p-6">
                <button type="button" class="group relative flex h-[340px] w-full items-center justify-center overflow-hidden rounded-2xl border border-line bg-[#ebe8e1] p-4"
                        data-bs-toggle="modal" data-bs-target="#previewModal" data-preview-url="<?= $fileUrl ?>" data-preview-type="<?= esc($sel['mime_type'], 'attr') ?>" data-preview-title="Receipt <?= esc($sel['or_number'], 'attr') ?> · <?= esc(person_name($sel), 'attr') ?>">
                    <?php if ($isPdf): ?>
                        <span class="flex flex-col items-center gap-2 text-ink-soft"><i class="bi bi-file-earmark-pdf text-5xl text-[#a3362a]" aria-hidden="true"></i><span class="font-semibold"><?= esc($sel['original_name']) ?></span><span class="text-sm">PDF · <?= file_size_label($sel['file_size']) ?> · click to open</span></span>
                    <?php else: ?>
                        <img src="<?= $fileUrl ?>" alt="Uploaded receipt <?= esc($sel['or_number'], 'attr') ?>" class="max-h-full max-w-full rotate-[-2deg] rounded bg-white object-contain shadow-lift transition group-hover:rotate-0">
                    <?php endif; ?>
                    <span class="absolute bottom-3 right-3 rounded-lg bg-paper/90 px-2.5 py-1 text-xs font-semibold text-ink"><i class="bi bi-arrows-fullscreen"></i> Enlarge</span>
                </button>
                <div class="text-xs text-ink-muted">File: <?= esc($sel['original_name']) ?> · <?= esc($sel['mime_type']) ?> · <?= file_size_label($sel['file_size']) ?> · SHA-256 <?= esc(substr($sel['file_hash'], 0, 12)) ?>…</div>

                <?php if ($reviewable): ?>
                    <form action="<?= site_url('admin/receipts/' . $sel['id'] . '/approve') ?>" method="post" id="approveForm"
                          data-confirm="Approve receipt <?= esc($sel['or_number'], 'attr') ?>? The student's clearance card will be created and teachers notified." data-confirm-title="Approve receipt" data-confirm-button="Approve receipt">
                        <?= csrf_field() ?>
                        <div class="flex items-end justify-between">
                            <div><div class="eyebrow">Review checklist</div><p class="text-sm text-ink-muted">Confirm every field before approving.</p></div>
                            <span class="text-sm font-semibold text-ink-muted" id="checkCount">0 of 4 checked</span>
                        </div>
                        <div class="mt-3 flex flex-col gap-2" data-checklist="#approveBtn" data-checklist-count="#checkCount">
                            <?php foreach (receipt_checklist_items() as $key => $label): ?>
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-paper px-4 py-3 hover:border-brand-300">
                                    <input class="form-check-input m-0" type="checkbox" name="checklist[<?= $key ?>]" value="1"> <span class="font-semibold"><?= esc($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php endif; ?>

                <dl class="mb-0 grid gap-x-6 gap-y-3 rounded-xl border border-line bg-paper p-4 text-sm sm:grid-cols-2">
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Student record</dt><dd class="mb-0 font-semibold"><?= esc($sel['program_code']) ?> · <?= year_level_label($sel['year_level']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Semester</dt><dd class="mb-0 font-semibold"><?= esc(term_label($sel, true)) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">OR number</dt><dd class="mb-0 font-mono font-semibold"><?= esc($sel['or_number']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Payment date</dt><dd class="mb-0 font-semibold"><?= fmt_date($sel['payment_date']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Payment amount</dt><dd class="mb-0 font-semibold"><?= peso($sel['amount']) ?></dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-ink-muted">Enrolled subjects</dt><dd class="mb-0 font-semibold"><?= count($enrolled) ?> · <?= rtrim(rtrim(number_format($enrolledUnits, 1), '0'), '.') ?> units</dd></div>
                </dl>

                <?php if ($reviewable): ?>
                    <div class="grid gap-3 sm:grid-cols-[2fr_1fr]">
                        <button class="btn btn-primary py-2.5" type="submit" form="approveForm" id="approveBtn" disabled><i class="bi bi-check-lg"></i>Approve receipt</button>
                        <button class="btn btn-outline-danger py-2.5" type="button" data-bs-toggle="modal" data-bs-target="#reuploadModal"><i class="bi bi-arrow-counterclockwise"></i>Request re-upload</button>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-line bg-[#f7f9f5] p-4 text-sm text-ink-soft">
                        <?php if ($sel['status'] === 'approved'): ?>
                            <i class="bi bi-check-circle-fill text-[#1f6a3f]"></i> Approved <?= fmt_datetime($sel['reviewed_at']) ?> by <?= esc(trim(($sel['reviewer_first'] ?? '') . ' ' . ($sel['reviewer_last'] ?? ''))) ?>.
                        <?php elseif ($sel['status'] === 'reupload_required'): ?>
                            <i class="bi bi-arrow-counterclockwise text-[#a4521a]"></i> Re-upload requested <?= fmt_datetime($sel['reviewed_at']) ?>: “<?= esc($sel['reupload_reason']) ?>”
                        <?php else: ?>
                            A newer upload exists for this clearance.
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (count($attempts) > 1): ?>
                    <div>
                        <div class="eyebrow mb-2">Upload history</div>
                        <ul class="m-0 flex list-none flex-col gap-2 p-0 text-sm">
                            <?php foreach ($attempts as $a): ?>
                                <li class="flex flex-wrap items-center gap-2 rounded-lg border border-line px-3 py-2">
                                    <span class="font-semibold">#<?= (int) $a['attempt_no'] ?></span><span class="text-ink-muted"><?= esc($a['or_number']) ?> · <?= fmt_datetime($a['submitted_at']) ?></span>
                                    <span class="ms-auto"><?= status_badge($a['status']) ?></span>
                                    <button type="button" class="bg-transparent p-0 text-brand-700" data-bs-toggle="modal" data-bs-target="#previewModal" data-preview-url="<?= site_url('files/tuition-receipt/' . $a['id']) ?>" data-preview-type="<?= esc($a['mime_type'], 'attr') ?>" data-preview-title="Attempt #<?= (int) $a['attempt_no'] ?>"><i class="bi bi-eye"></i><span class="visually-hidden">View attempt</span></button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($reviewable): ?>
                <?= view('partials/reupload_modal', ['action' => site_url('admin/receipts/' . $sel['id'] . '/reupload'), 'who' => person_name($sel)]) ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
