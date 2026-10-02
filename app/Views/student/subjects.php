<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php $clearance = $bundle['clearance']; $stats = $bundle['stats']; ?>
<?php if (! $clearance || ! in_array($clearance['status'], ['in_progress', 'completed'], true)): ?>
    <div class="surface empty-state"><i class="bi bi-lock"></i><p class="font-semibold text-ink">Subject clearance has not started</p><p>Subjects appear here once your tuition receipt is approved.</p></div>
<?php else: ?>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card"><span class="stat-label">Resolved</span><span class="stat-value"><?= $stats['resolved'] ?>/<?= $stats['total'] ?></span><span class="stat-hint">Subjects cleared</span></div>
        <div class="stat-card"><span class="stat-label">Pending decision</span><span class="stat-value"><?= $stats['pending'] + $stats['unsigned'] ?></span><span class="stat-hint">Waiting on teachers</span></div>
        <div class="stat-card"><span class="stat-label">INC</span><span class="stat-value text-[#a4521a]"><?= $stats['inc'] ?></span><span class="stat-hint">Missing requirements</span></div>
        <div class="stat-card"><span class="stat-label">Failed</span><span class="stat-value text-[#a3362a]"><?= $stats['failed'] ?></span><span class="stat-hint">Re-enrollment track</span></div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <?php foreach ($bundle['subjects'] as $cs):
            $key  = subject_status_key($cs);
            $reqs = $requirements[(int) $cs['id']] ?? [];
        ?>
            <section class="surface flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="eyebrow"><?= esc($cs['subject_code']) ?> · <?= rtrim(rtrim($cs['units'], '0'), '.') ?> units</div>
                        <h2 class="section-title mt-1"><?= esc($cs['subject_title']) ?></h2>
                    </div>
                    <?= $cs['status'] === 'PASSED' && empty($cs['signed_at']) ? status_badge('AWAITING_SIGNATURE', 'Passed · unsigned') : status_badge($key) ?>
                </div>

                <div class="mt-4 flex items-center gap-3 rounded-xl bg-[#f6f8f4] p-3">
                    <span class="avatar h-10 w-10 text-sm"><?= esc(initials($cs['teacher_name'])) ?></span>
                    <div class="min-w-0 text-sm">
                        <div class="font-semibold">Teacher: <?= esc($cs['teacher_name']) ?></div>
                        <div class="truncate text-ink-muted"><?= esc($cs['teacher_department'] ?? '') ?><?= $cs['teacher_email'] ? ' · ' . esc($cs['teacher_email']) : '' ?></div>
                        <?php if ($cs['offering_schedule']): ?><div class="text-xs text-ink-muted">Section <?= esc($cs['offering_section']) ?> · <?= esc($cs['offering_schedule']) ?></div><?php endif; ?>
                    </div>
                </div>

                <ul class="mt-4 flex list-none flex-col gap-1.5 p-0 text-sm">
                    <?php if ($cs['status'] === 'PASSED'): ?>
                        <li class="text-[#1f6a3f]"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Passed<?= $cs['final_grade'] ? ' · Grade ' . esc($cs['final_grade']) : '' ?><?= (int) $cs['was_incomplete'] ? ' (INC completed)' : '' ?></li>
                        <li class="<?= $cs['signed_at'] ? 'text-[#1f6a3f]' : 'text-[#8a6310]' ?>"><i class="bi <?= $cs['signed_at'] ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?> me-1" aria-hidden="true"></i><?= $cs['signed_at'] ? 'Teacher approved · signed ' . fmt_date($cs['signed_at']) : 'Waiting for teacher signature' ?></li>
                    <?php elseif ($cs['status'] === 'INC'): ?>
                        <li class="text-[#a4521a]"><i class="bi bi-exclamation-triangle-fill me-1" aria-hidden="true"></i>Incomplete — <?= count(array_filter($reqs, static fn ($r) => $r['status'] === 'pending')) ?> requirement(s) pending</li>
                        <?php foreach ($reqs as $r): ?>
                            <li class="ms-5 text-ink-soft"><i class="bi <?= $r['status'] === 'completed' ? 'bi-check2-square text-[#1f6a3f]' : 'bi-square' ?> me-1" aria-hidden="true"></i><?= esc($r['description']) ?></li>
                        <?php endforeach; ?>
                    <?php elseif ($cs['status'] === 'FAILED'): ?>
                        <li class="text-[#a3362a]"><i class="bi bi-x-circle-fill me-1" aria-hidden="true"></i>Failed<?= $cs['final_grade'] ? ' · Grade ' . esc($cs['final_grade']) : '' ?></li>
                        <li class="text-ink-soft"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i><?= esc(status_meta($key)['label']) ?></li>
                    <?php else: ?>
                        <li class="text-[#8a6310]"><i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>Awaiting your teacher's decision</li>
                    <?php endif; ?>
                </ul>

                <?php if ($cs['remarks']): ?>
                    <blockquote class="mb-0 mt-3 rounded-lg border-l-4 border-brand-200 bg-brand-50/50 px-3 py-2 text-sm text-ink-soft">“<?= esc($cs['remarks']) ?>”</blockquote>
                <?php endif; ?>

                <div class="mt-auto pt-4">
                    <?php if ($cs['status'] === 'INC'): ?>
                        <a href="<?= site_url('student/inc') ?>" class="btn btn-soft btn-sm">View INC requirements <i class="bi bi-arrow-right"></i></a>
                    <?php elseif ($cs['status'] === 'FAILED' && $key !== 'RE_ENROLLMENT_APPROVED'): ?>
                        <a href="<?= site_url('student/reenrollment') ?>" class="btn btn-outline-danger btn-sm">View re-enrollment <i class="bi bi-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
