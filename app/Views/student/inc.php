<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php if ($subjects === []): ?>
    <div class="surface empty-state"><i class="bi bi-emoji-smile"></i><p class="font-semibold text-ink">No INC subjects</p><p>You have no incomplete subjects. Nice work!</p></div>
<?php endif; ?>

<div class="flex flex-col gap-6">
<?php foreach ($subjects as $cs):
    $reqs = $requirements[(int) $cs['id']] ?? [];
    $open = $cs['status'] === 'INC' && $cs['clearance_status'] === 'in_progress';
?>
    <section class="surface overflow-hidden">
        <div class="flex flex-wrap items-start justify-between gap-3 p-5">
            <div>
                <div class="eyebrow"><?= esc(term_label($cs)) ?> · <?= esc($cs['subject_code']) ?></div>
                <h2 class="section-title mt-1"><?= esc($cs['subject_title']) ?></h2>
                <p class="mt-1 text-sm text-ink-muted">Teacher: <strong class="text-ink"><?= esc($cs['teacher_name']) ?></strong><?= $cs['teacher_email'] ? ' · ' . esc($cs['teacher_email']) : '' ?></p>
            </div>
            <?= $cs['status'] === 'INC' ? status_badge('INC') : status_badge('PASSED', 'INC → Passed') ?>
        </div>

        <?php if ($open): ?>
            <div class="mx-5 mb-4 flex items-start gap-3 rounded-xl border border-[#f3d2b5] bg-[#fdf3ea] p-4 text-sm text-[#7a3d12]">
                <i class="bi bi-person-check-fill mt-0.5" aria-hidden="true"></i>
                <span><strong>Instruction:</strong> Complete the missing requirement personally with your subject teacher. Only your teacher can verify it and change the subject to PASSED.</span>
            </div>
        <?php endif; ?>

        <ul class="m-0 list-none border-t border-line p-0">
            <?php foreach ($reqs as $r):
                $reqStatus = $r['status'] === 'completed' ? 'verified' : ($r['student_reported_at'] ? 'reported' : 'pending');
            ?>
                <li class="border-b border-line px-5 py-4 last:border-b-0">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-ink-muted">Missing requirement</div>
                            <div class="mt-0.5 font-semibold">“<?= esc($r['description']) ?>”</div>
                            <?php if ($r['instructions']): ?><div class="mt-1 text-sm text-ink-soft"><?= esc($r['instructions']) ?></div><?php endif; ?>
                            <div class="mt-1 text-xs text-ink-muted">
                                Added <?= fmt_date($r['created_at']) ?><?= $r['due_date'] ? ' · Due ' . fmt_date($r['due_date']) : '' ?>
                                <?= $r['verified_at'] ? ' · Verified ' . fmt_datetime($r['verified_at']) : '' ?>
                            </div>
                            <?php if ($r['verification_notes']): ?>
                                <div class="mt-2 rounded-lg bg-[#f6f8f4] px-3 py-2 text-sm text-ink-soft"><span class="font-semibold">Teacher note:</span> <?= esc($r['verification_notes']) ?></div>
                            <?php endif; ?>
                        </div>
                        <?= status_badge($reqStatus) ?>
                    </div>

                    <?php if ($open && $reqStatus === 'pending'): ?>
                        <form action="<?= site_url('student/inc/' . $r['id'] . '/report') ?>" method="post" class="mt-3 flex flex-col gap-2 sm:flex-row"
                              data-confirm="Let <?= esc($cs['teacher_name'], 'attr') ?> know you have completed this requirement? Your teacher still has to verify it." data-confirm-title="Notify your teacher" data-confirm-button="Notify teacher">
                            <?= csrf_field() ?>
                            <label class="visually-hidden" for="note<?= $r['id'] ?>">Note for your teacher (optional)</label>
                            <input type="text" id="note<?= $r['id'] ?>" name="note" maxlength="255" class="form-control form-control-sm" placeholder="Optional note, e.g. when you presented it">
                            <button type="submit" class="btn btn-soft btn-sm whitespace-nowrap"><i class="bi bi-send"></i>I've completed this — request verification</button>
                        </form>
                    <?php elseif ($reqStatus === 'reported'): ?>
                        <p class="mb-0 mt-2 text-sm text-[#285f94]"><i class="bi bi-send-check" aria-hidden="true"></i> Your teacher was notified <?= time_ago($r['student_reported_at']) ?> and will verify it with you.</p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
</div>
<?= $this->endSection() ?>
