<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('student/history') ?>" class="btn btn-soft h-10 px-3 text-sm"><i class="bi bi-clock-history"></i>History</a>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$clearance     = $bundle['clearance'];
$receipt       = $bundle['receipt'];
$stats         = $bundle['stats'];
$status        = $clearance['status'] ?? 'not_started';
$pct           = $stats['total'] ? (int) round($stats['resolved'] / $stats['total'] * 100) : 0;
$unresolved    = $stats['total'] - $stats['resolved'];
$cardIssued    = in_array($status, ['in_progress', 'completed'], true);
$receiptStatus = $receipt['status'] ?? 'not_submitted';
$eligible      = $eligibility['status'] === 'ELIGIBLE';
$subjectCount  = $stats['total'] ?: count($enrolled);
?>

<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="eyebrow">Academic year <?= $term ? esc(str_replace('-', '–', $term['school_year'])) : '—' ?></div>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-ink-soft">
            <span class="rounded-md border border-line bg-paper px-2 py-0.5 font-mono text-[12.5px] font-semibold text-ink"><?= esc($student['student_number']) ?></span>
            <span>Welcome, <strong class="text-ink"><?= esc(person_name($student)) ?></strong> · <?= esc($student['program_code']) ?> · <?= year_level_label($student['year_level']) ?> · <?= $term ? esc(semester_label($term['semester'])) : 'No active term' ?></span>
        </div>
    </div>
    <div class="flex items-center gap-2 text-sm text-ink-muted"><i class="bi bi-clock" aria-hidden="true"></i>Last updated <?= date('j M Y, g:i A') ?></div>
</div>

<?php if (! $term): ?>
    <div class="surface empty-state"><i class="bi bi-calendar-x"></i><p class="font-semibold text-ink">No active school term</p><p>The registrar has not opened a term yet. Please check back later.</p></div>
<?php elseif ($status === 'completed'): ?>
    <!-- Completed hero -->
    <section class="relative overflow-hidden rounded-3xl bg-brand-900 p-7 text-white shadow-lift md:p-9" aria-labelledby="doneTitle">
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-gold-400/10" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="max-w-2xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-[#e3f1e7] px-3 py-1 text-xs font-bold uppercase tracking-wider text-[#1f6a3f]"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>Clearance completed</span>
                <h2 id="doneTitle" class="mt-4 font-serif text-4xl leading-tight md:text-5xl">Congratulations!</h2>
                <p class="mt-3 text-[15px] leading-relaxed text-white/80">
                    Your clearance for <strong class="text-white"><?= esc(term_label($term)) ?></strong> was completed on <?= fmt_date($clearance['completed_at']) ?>.
                    You are qualified to enroll for the next semester.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="<?= site_url('student/card') ?>" class="btn border-0 bg-gold-400 px-4 font-bold text-brand-950 hover:bg-gold-300"><i class="bi bi-card-checklist"></i>View clearance</a>
                    <a href="<?= site_url('student/history') ?>" class="btn border border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">Clearance history</a>
                </div>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/[.06] p-5 md:w-[300px]">
                <div class="text-sm font-semibold text-white/75">Enrollment status</div>
                <div class="mt-2 flex items-center gap-2 font-serif text-3xl"><i class="bi bi-check-circle-fill text-[#8fd3a6]" aria-hidden="true"></i>Cleared</div>
                <p class="mt-2 text-sm text-white/70">You are eligible to enroll for the next semester.</p>
                <div class="mt-3 text-xs text-white/60">Reference <?= esc($clearance['reference_no']) ?></div>
            </div>
        </div>
    </section>
<?php else: ?>
    <!-- Clearance journey hero -->
    <section class="overflow-hidden rounded-3xl bg-brand-900 text-white shadow-lift" aria-labelledby="journeyTitle">
        <div class="grid gap-6 p-6 md:p-8 lg:grid-cols-[1fr_minmax(0,420px)]">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[.16em] text-white/85">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gold-400 text-brand-950"><i class="bi bi-check-lg" aria-hidden="true"></i></span>Clearance journey
                </div>
                <h2 id="journeyTitle" class="mt-5 max-w-xl font-serif text-[2.3rem] leading-[1.08] md:text-5xl">
                    <?= esc(match ($status) {
                        'not_started'       => 'Start your clearance for this semester.',
                        'awaiting_receipt'  => 'Upload your tuition receipt to begin.',
                        'receipt_review'    => 'Your receipt is with the registrar.',
                        'reupload_required' => 'Your receipt needs a clearer copy.',
                        default             => $unresolved === 1 ? 'You are 1 step away from being cleared.' : "You are {$unresolved} steps away from being cleared.",
                    }) ?>
                </h2>
                <p class="mt-4 max-w-xl text-[15px] leading-relaxed text-white/75">
                    <?= esc(match ($status) {
                        'not_started'       => $enrolled === [] ? 'You are not enrolled in any subjects for this term yet. Contact the registrar if this looks wrong.' : 'You are enrolled in ' . count($enrolled) . ' subject(s). Start your application, then upload your tuition receipt.',
                        'awaiting_receipt'  => 'The registrar validates your official receipt first. Once approved, your clearance card is created automatically.',
                        'receipt_review'    => 'We will notify you as soon as your receipt is validated and your clearance card is ready.',
                        'reupload_required' => 'Reason: ' . ($receipt['reupload_reason'] ?? 'Please upload a clearer copy of your receipt.'),
                        default             => 'We are waiting on subject outcomes before your clearance can be completed. You do not need to chase every office — we will show you exactly where to look next.',
                    }) ?>
                </p>

                <?php if ($status === 'not_started' && $enrolled !== []): ?>
                    <form action="<?= site_url('student/clearance/start') ?>" method="post" class="mt-6" data-confirm="Start your clearance application for <?= esc(term_label($term), 'attr') ?>?" data-confirm-title="Start clearance" data-confirm-button="Start clearance">
                        <?= csrf_field() ?>
                        <button class="btn border-0 bg-gold-400 px-5 font-bold text-brand-950 hover:bg-gold-300" type="submit"><i class="bi bi-play-circle-fill"></i>Start clearance</button>
                    </form>
                <?php elseif (in_array($status, ['awaiting_receipt', 'reupload_required'], true)): ?>
                    <a href="<?= site_url('student/receipt') ?>" class="btn mt-6 border-0 bg-gold-400 px-5 font-bold text-brand-950 hover:bg-gold-300"><i class="bi bi-cloud-arrow-up-fill"></i><?= $status === 'reupload_required' ? 'Upload new receipt' : 'Upload tuition receipt' ?></a>
                <?php elseif ($cardIssued): ?>
                    <div class="mt-6 flex max-w-lg items-center gap-4">
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-white/15" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Subjects resolved">
                            <div class="h-full rounded-full bg-gold-400" style="width: <?= $pct ?>%"></div>
                        </div>
                        <span class="whitespace-nowrap text-sm font-bold text-gold-200"><?= $stats['resolved'] ?> of <?= $stats['total'] ?> resolved</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[.05] p-5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-semibold text-white/80">Current eligibility</span>
                    <span class="rounded-full bg-[#f9e1dc] px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-[#a3362a]"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>Not yet eligible</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2 border-b border-white/10 pb-4">
                    <span class="font-serif text-5xl"><?= $pct ?>%</span><span class="text-sm text-white/65">subjects resolved</span>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-white/70">
                    All <?= $subjectCount ?> required subjects must be resolved before your clearance is completed and you become eligible to enroll.
                </p>
                <?php if ($cardIssued): ?>
                    <a href="<?= site_url('student/card') ?>" class="mt-4 flex items-center justify-between rounded-xl bg-gold-400 px-4 py-3 text-sm font-bold text-brand-950 hover:bg-gold-300">View clearance card <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <?php else: ?>
                    <div class="mt-4 flex items-center justify-between rounded-xl bg-gold-400/70 px-4 py-3 text-sm font-bold text-brand-950/80">Clearance card locked <i class="bi bi-lock-fill" aria-hidden="true"></i></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Step tracker -->
        <ol class="m-0 flex list-none flex-wrap items-center gap-x-4 gap-y-2 border-t border-white/10 px-6 py-4 text-sm md:px-8" aria-label="Clearance steps">
            <?php
            $steps = [
                ['Tuition receipt', $cardIssued ? 'done' : ($status === 'not_started' ? 'todo' : 'current')],
                ['Subject verification', $cardIssued ? 'current' : 'todo'],
                ['Clearance completed', 'todo'],
            ];
            foreach ($steps as $i => [$label, $state]): ?>
                <li class="flex items-center gap-2 <?= $state === 'todo' ? 'text-white/55' : 'font-semibold text-white' ?>">
                    <?php if ($state === 'done'): ?>
                        <span class="step-dot border-gold-400 bg-gold-400 text-brand-950"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                    <?php else: ?>
                        <span class="step-dot <?= $state === 'current' ? 'border-gold-400 text-gold-200' : 'border-white/30' ?>"><?= $i + 1 ?></span>
                    <?php endif; ?>
                    <?= esc($label) ?><span class="visually-hidden"> — <?= $state === 'done' ? 'done' : ($state === 'current' ? 'in progress' : 'not started') ?></span>
                    <?php if ($i < 2): ?><i class="bi bi-chevron-right ms-2 text-white/40" aria-hidden="true"></i><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
<?php endif; ?>

<?php if ($term): ?>
<ul class="nav mt-7 flex gap-7 border-b border-line" role="tablist">
    <li role="presentation"><button class="tab-link active bg-transparent" data-bs-toggle="tab" data-bs-target="#tabOverview" type="button" role="tab" aria-controls="tabOverview" aria-selected="true">Overview</button></li>
    <li role="presentation"><button class="tab-link bg-transparent" data-bs-toggle="tab" data-bs-target="#tabChecklist" type="button" role="tab" aria-controls="tabChecklist" aria-selected="false">Requirements checklist<?php if ($bundle['blockers']): ?> <span class="ms-1 rounded-full bg-[#fce8d6] px-2 py-0.5 text-[11px] text-[#a4521a]"><?= count($bundle['blockers']) ?></span><?php endif; ?></button></li>
</ul>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
    <div class="tab-content min-w-0">
        <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">
            <div class="flex flex-col gap-6">
                <section class="surface overflow-hidden">
                    <div class="flex items-start justify-between gap-3 p-5">
                        <div>
                            <div class="eyebrow">Step 1 · <?= $cardIssued ? 'Verified' : 'Tuition receipt' ?></div>
                            <h3 class="section-title mt-1">Tuition receipt</h3>
                            <p class="mt-1 text-sm text-ink-muted">
                                <?= esc(match ($receiptStatus) {
                                    'approved'          => 'Your receipt was reviewed and approved by the registrar.',
                                    'under_review'      => 'The registrar is reviewing your receipt now.',
                                    'submitted'         => 'Submitted and waiting in the registrar queue.',
                                    'reupload_required' => 'The registrar asked you to upload a new copy.',
                                    default             => $status === 'not_started' ? 'Start your clearance to upload your receipt.' : 'Upload your official tuition receipt.',
                                }) ?>
                            </p>
                        </div>
                        <?= status_badge($receiptStatus, $receiptStatus === 'approved' ? 'Verified' : null) ?>
                    </div>
                    <?php if ($receipt): ?>
                        <div class="flex flex-wrap items-center gap-3 border-t border-line bg-[#f9faf6] px-5 py-4">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><i class="bi <?= str_contains($receipt['mime_type'], 'pdf') ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' ?> text-lg" aria-hidden="true"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold"><?= esc($receipt['or_number']) ?> · <?= esc($receipt['original_name']) ?></div>
                                <div class="text-xs text-ink-muted">Uploaded <?= fmt_date($receipt['submitted_at']) ?> · <?= peso($receipt['amount']) ?></div>
                            </div>
                            <button type="button" class="bg-transparent text-sm font-semibold text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#previewModal"
                                    data-preview-url="<?= site_url('files/tuition-receipt/' . $receipt['id']) ?>" data-preview-type="<?= esc($receipt['mime_type'], 'attr') ?>" data-preview-title="Receipt <?= esc($receipt['or_number'], 'attr') ?>">
                                <i class="bi bi-eye"></i> View receipt
                            </button>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="surface overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="eyebrow">Step 2 · <?= $status === 'completed' ? 'Completed' : ($cardIssued ? 'In progress' : 'Locked') ?></div>
                                <h3 class="section-title mt-1">Subject progress</h3>
                                <p class="mt-1 text-sm text-ink-muted">Only teachers can mark a subject outcome.</p>
                            </div>
                            <span class="whitespace-nowrap pt-6 text-sm font-bold text-brand-800"><?= $stats['resolved'] ?> of <?= $subjectCount ?> cleared</span>
                        </div>
                        <div class="progress-track mt-4" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Subjects resolved"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div>
                    </div>
                    <ul class="m-0 list-none border-t border-line p-0">
                        <?php if ($cardIssued): ?>
                            <?php foreach ($bundle['subjects'] as $cs): ?>
                                <?= view('student/_subject_row', ['cs' => $cs]) ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($enrolled as $o): ?>
                                <li class="flex items-center gap-3 border-b border-line px-5 py-4 last:border-b-0">
                                    <span class="flex h-10 w-12 items-center justify-center rounded-lg border border-line bg-[#f6f8f4] text-[11px] font-bold text-ink-muted"><?= esc(preg_replace('/\d+$/', '', $o['code'])) ?></span>
                                    <div class="min-w-0 flex-1"><div class="font-semibold"><?= esc($o['title']) ?></div><div class="text-xs text-ink-muted"><?= esc($o['code']) ?> · <?= esc($o['teacher_name']) ?></div></div>
                                    <span class="badge-status badge-gray"><i class="bi bi-lock" aria-hidden="true"></i>Card not issued</span>
                                </li>
                            <?php endforeach; ?>
                            <?php if ($enrolled === []): ?><li class="empty-state"><i class="bi bi-journal-x"></i>No enrolled subjects for this term.</li><?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </section>
            </div>
        </div>

        <div class="tab-pane fade" id="tabChecklist" role="tabpanel">
            <section class="surface p-5">
                <div class="eyebrow">Enrollment status</div>
                <?php if ($eligible): ?>
                    <h3 class="section-title mt-1 flex items-center gap-2"><i class="bi bi-check-circle-fill text-[#1f6a3f]" aria-hidden="true"></i>Cleared</h3>
                    <p class="mt-2 text-ink-soft">You are eligible to enroll for the next semester.</p>
                <?php else: ?>
                    <h3 class="section-title mt-1 flex items-center gap-2"><i class="bi bi-exclamation-triangle-fill text-[#a4521a]" aria-hidden="true"></i>Not yet eligible</h3>
                    <p class="mt-2 text-ink-soft">Please complete the following:</p>
                    <ul class="mt-4 flex list-none flex-col gap-2 p-0">
                        <?php foreach ($bundle['blockers'] as $b): ?>
                            <li class="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-[#f9faf6] px-4 py-3">
                                <div class="min-w-0 flex-1"><div class="font-semibold"><?= esc($b['title']) ?></div><div class="text-sm text-ink-muted"><?= esc($b['detail']) ?></div></div>
                                <?= status_badge($b['status']) ?>
                                <a href="<?= site_url($b['link']) ?>" class="text-sm font-semibold text-brand-700 hover:underline">Open <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <aside class="flex flex-col gap-5">
        <?php
        $priority = null;
        foreach ($bundle['blockers'] as $b) {
            if (in_array($b['status'], ['FAILED', 'reupload_required', 'INC', 'not_submitted'], true)) {
                $priority = $b;
                break;
            }
        }
        $priority ??= $bundle['blockers'][0] ?? null;
        ?>
        <?php if ($priority && $status !== 'not_started'): ?>
            <section class="surface p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="eyebrow">Needs your attention</div>
                        <h3 class="section-title mt-1"><?= count($bundle['blockers']) === 1 ? 'One open blocker' : count($bundle['blockers']) . ' open blockers' ?></h3>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f9e1dc] text-[#a3362a]"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                </div>
                <div class="mt-4 rounded-xl border border-[#f1cfc7] bg-[#fdf5f3] p-4">
                    <div class="flex flex-wrap items-center gap-2"><?= status_badge($priority['status']) ?><span class="text-xs text-ink-muted"><?= esc($priority['title']) ?></span></div>
                    <div class="mt-2 font-semibold text-[#8f2f24]">
                        <?= esc(match ($priority['status']) {
                            'FAILED'            => 'Re-enrollment is required',
                            'INC'               => 'Missing requirement to complete',
                            'reupload_required' => 'Upload a new receipt',
                            'not_submitted'     => 'Upload your tuition receipt',
                            default             => 'Waiting on the next step',
                        }) ?>
                    </div>
                    <p class="mt-1 text-sm text-ink-soft"><?= esc($priority['detail']) ?></p>
                    <a href="<?= site_url($priority['link']) ?>" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-[#a3362a] hover:underline">
                        <?= $priority['status'] === 'FAILED' ? 'View re-enrollment' : 'Take action' ?> <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <section class="surface p-5">
            <div class="eyebrow">Student information</div>
            <dl class="mb-0 mt-3 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div class="col-span-2"><dt class="kv-label">Name</dt><dd class="kv-value mb-0"><?= esc(person_name($student)) ?></dd></div>
                <div><dt class="kv-label">Student no.</dt><dd class="mb-0 font-mono font-semibold"><?= esc($student['student_number']) ?></dd></div>
                <div><dt class="kv-label">Program</dt><dd class="kv-value mb-0"><?= esc($student['program_code']) ?></dd></div>
                <div><dt class="kv-label">Year level</dt><dd class="kv-value mb-0"><?= year_level_label($student['year_level']) ?></dd></div>
                <div><dt class="kv-label">Semester</dt><dd class="kv-value mb-0"><?= $term ? esc(term_label($term, true)) : '—' ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Clearance status</dt><dd class="mb-0 mt-1"><?= status_badge($status === 'in_progress' ? 'incomplete' : $status) ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Tuition receipt</dt><dd class="mb-0 mt-1"><?= status_badge($receiptStatus) ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Enrollment eligibility</dt><dd class="mb-0 mt-1"><?= status_badge($eligibility['status']) ?></dd></div>
            </dl>
        </section>

        <section class="surface p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gold-50 text-gold-600"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i></span>
                <div>
                    <h3 class="text-base font-bold">Receipt documents</h3>
                    <p class="text-sm text-ink-muted">Keep a clear copy of your official receipt.</p>
                </div>
            </div>
            <a href="<?= site_url('student/receipt') ?>" class="btn btn-soft mt-4 w-full"><i class="bi bi-cloud-arrow-up"></i><?= in_array($receiptStatus, ['reupload_required', 'not_submitted'], true) ? 'Upload a document' : 'View receipt history' ?></a>
            <?php if ($completedCount > 0): ?>
                <a href="<?= site_url('student/history') ?>" class="mt-3 block text-center text-sm font-semibold text-brand-700 hover:underline"><?= $completedCount ?> completed clearance<?= $completedCount > 1 ? 's' : '' ?> in your history</a>
            <?php endif; ?>
        </section>
    </aside>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
