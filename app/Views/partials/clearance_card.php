<?php
/**
 * Printable clearance card.
 *
 * @var array      $clearance      detailed clearance row
 * @var array      $subjects       clearance subjects (withDetails)
 * @var array|null $snapshot       frozen student details
 * @var array      $reenrollments  latest re-enrollment receipt per clearance subject id
 */
use App\Services\ClearanceService;
use App\Services\SubjectClearanceService;

$snap      = $snapshot ?? [];
$name      = $snap['name'] ?? person_name($clearance);
$number    = $snap['student_number'] ?? $clearance['student_number'];
$program   = $snap['program_code'] ?? $clearance['program_code'];
$year      = $snap['year_level'] ?? $clearance['year_level'];
$completed = $clearance['status'] === 'completed';
$reenrollments ??= [];
?>
<article class="surface print-plain overflow-hidden" aria-label="Clearance card">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-line bg-brand-900 px-6 py-5 text-white">
        <div class="flex items-center gap-4">
            <img src="<?= base_url('assets/img/logo-192.png') ?>" alt="<?= esc(setting('school_name'), 'attr') ?> seal" class="h-16 w-16 rounded-full ring-2 ring-white/20">
            <div>
                <div class="max-w-[560px] text-[15px] font-semibold leading-snug tracking-[.01em] text-gold-100"><?= esc(setting('school_name')) ?></div>
                <div class="font-serif text-2xl leading-tight">School Clearance</div>
                <div class="text-xs text-white/70"><?= esc(setting('office_name')) ?><?= setting('school_address') ? ' · ' . esc(setting('school_address')) : '' ?></div>
            </div>
        </div>
        <div class="text-sm sm:text-right">
            <div class="text-white/70">Reference</div>
            <div class="font-mono font-semibold"><?= esc($clearance['reference_no']) ?></div>
        </div>
    </header>

    <dl class="m-0 grid gap-4 border-b border-line px-6 py-5 sm:grid-cols-3 lg:grid-cols-6">
        <div class="sm:col-span-2"><dt class="kv-label">Student</dt><dd class="kv-value mb-0"><?= esc($name) ?></dd></div>
        <div><dt class="kv-label">Student ID</dt><dd class="mb-0 font-mono font-semibold"><?= esc($number) ?></dd></div>
        <div><dt class="kv-label">Program</dt><dd class="kv-value mb-0"><?= esc($program) ?> · <?= year_level_label($year) ?></dd></div>
        <div><dt class="kv-label">Semester</dt><dd class="kv-value mb-0"><?= esc(semester_label($clearance['semester'])) ?></dd></div>
        <div><dt class="kv-label">School year</dt><dd class="kv-value mb-0"><?= esc(str_replace('-', '–', $clearance['school_year'])) ?></dd></div>
    </dl>

    <div class="overflow-x-auto">
        <table class="table">
            <thead>
            <tr><th scope="col" class="ps-6">Subject</th><th scope="col">Teacher</th><th scope="col">Status</th><th scope="col" class="pe-6">Teacher signature / approval</th></tr>
            </thead>
            <tbody>
            <?php foreach ($subjects as $cs):
                $key      = subject_status_key($cs);
                $signed   = $cs['status'] === 'PASSED' && ! empty($cs['signed_at']);
                $re       = $reenrollments[(int) $cs['id']] ?? null;
            ?>
                <tr>
                    <td class="ps-6">
                        <div class="font-semibold"><?= esc($cs['subject_title']) ?></div>
                        <div class="text-xs text-ink-muted"><?= esc($cs['subject_code']) ?> · <?= rtrim(rtrim($cs['units'], '0'), '.') ?> units<?= $cs['final_grade'] ? ' · Grade ' . esc($cs['final_grade']) : '' ?></div>
                    </td>
                    <td class="whitespace-nowrap"><?= esc($cs['teacher_name']) ?></td>
                    <td>
                        <div class="flex flex-col items-start gap-1">
                            <?php if ($cs['status'] === 'FAILED'): ?>
                                <?= status_badge('FAILED') ?>
                                <?= status_badge($key) ?>
                            <?php elseif ($cs['status'] === 'PASSED' && ! $signed): ?>
                                <?= status_badge('PASSED') ?><?= status_badge('AWAITING_SIGNATURE') ?>
                            <?php else: ?>
                                <?= status_badge($key) ?>
                            <?php endif; ?>
                            <?php if ((int) $cs['was_incomplete'] === 1): ?><span class="text-[11px] text-ink-muted">INC completed → PASSED</span><?php endif; ?>
                        </div>
                    </td>
                    <td class="pe-6">
                        <?php if ($signed): ?>
                            <div class="flex items-center gap-3">
                                <?php if (! empty($cs['signature_image'])): ?>
                                    <img src="<?= site_url('files/approval-signature/' . $cs['id']) ?>" alt="E-signature of <?= esc($cs['teacher_name'], 'attr') ?>" class="h-10 max-w-[150px] object-contain">
                                <?php else: ?>
                                    <span class="signature-script text-lg text-[#17346e]"><?= esc(preg_replace('/^(Prof\.|Dr\.)\s*/', '', $cs['teacher_name'])) ?></span>
                                <?php endif; ?>
                                <div class="text-[11px] leading-tight text-ink-muted">
                                    <?php if (SubjectClearanceService::signatureValid($cs)): ?>
                                        <span class="font-semibold text-[#1f6a3f]"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Teacher approved</span>
                                    <?php else: ?>
                                        <span class="font-semibold text-[#a3362a]"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Signature mismatch</span>
                                    <?php endif; ?>
                                    <br><?= fmt_datetime($cs['signed_at']) ?><br>ID <?= esc(strtoupper(substr($cs['signature_hash'], 0, 10))) ?>
                                </div>
                            </div>
                        <?php elseif ($key === 'RE_ENROLLMENT_APPROVED'): ?>
                            <div class="text-xs text-ink-soft"><i class="bi bi-receipt text-[#1f6a3f]" aria-hidden="true"></i> Re-enrollment confirmed by registrar<br><?= fmt_datetime($cs['reenrollment_confirmed_at']) ?><?= $re ? ' · ' . esc($re['or_number']) : '' ?></div>
                        <?php else: ?>
                            <span class="text-sm text-ink-faint">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($subjects === []): ?>
                <tr><td colspan="4" class="py-8 text-center text-ink-muted">No subjects on this card yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-[#f7f9f5] px-6 py-4">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-ink-soft">Overall status:</span>
            <?= $completed ? status_badge('completed', 'Clearance completed') : status_badge('incomplete', 'In progress') ?>
        </div>
        <div class="text-xs text-ink-muted">
            <?php if ($completed): ?>Completed <?= fmt_datetime($clearance['completed_at']) ?> · Eligible for next semester enrollment
            <?php else: ?><?= count(array_filter($subjects, [ClearanceService::class, 'isResolved'])) ?> of <?= count($subjects) ?> subjects resolved · Card issued <?= fmt_date($clearance['card_issued_at']) ?><?php endif; ?>
        </div>
    </footer>
</article>
