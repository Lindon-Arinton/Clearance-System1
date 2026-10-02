<?php
/** @var array $cs clearance subject row */
$key    = subject_status_key($cs);
$prefix = preg_replace('/\d+$/', '', $cs['subject_code']);
$note   = match (true) {
    $cs['status'] === 'PASSED' && ! empty($cs['signed_at']) => 'Verified by ' . $cs['teacher_name'] . ' · ' . fmt_date($cs['signed_at']),
    $cs['status'] === 'PASSED'                                => 'Passed · awaiting ' . $cs['teacher_name'] . "'s signature",
    $cs['status'] === 'INC'                                   => 'Teacher verification needed · ' . (int) $cs['inc_pending'] . ' missing requirement' . ((int) $cs['inc_pending'] === 1 ? '' : 's'),
    $key === 'RE_ENROLLMENT_APPROVED'                         => 'Failed · re-enrollment confirmed ' . fmt_date($cs['reenrollment_confirmed_at']),
    $key === 'RE_ENROLLMENT_RECEIPT_PENDING'                  => 'Failed · re-enrollment receipt with the registrar',
    $cs['status'] === 'FAILED'                                => 'Re-enrollment required before clearance',
    default                                                   => 'Awaiting decision from ' . $cs['teacher_name'],
};
?>
<li class="flex items-center gap-3 border-b border-line px-5 py-4 last:border-b-0">
    <span class="flex h-10 w-12 shrink-0 items-center justify-center rounded-lg border border-line bg-[#f6f8f4] text-[11px] font-bold text-ink-muted"><?= esc($prefix) ?></span>
    <div class="min-w-0 flex-1">
        <div class="font-semibold"><?= esc($cs['subject_title']) ?></div>
        <div class="text-xs text-ink-muted"><?= esc($cs['subject_code']) ?> · <?= rtrim(rtrim($cs['units'], '0'), '.') ?> units</div>
    </div>
    <div class="flex flex-col items-end gap-1 text-right">
        <?php if ($cs['status'] === 'PASSED' && empty($cs['signed_at'])): ?>
            <?= status_badge('AWAITING_SIGNATURE', 'Passed · unsigned') ?>
        <?php else: ?>
            <?= status_badge($key) ?>
        <?php endif; ?>
        <span class="hidden text-[11.5px] text-ink-muted sm:block"><?= esc($note) ?></span>
    </div>
</li>
