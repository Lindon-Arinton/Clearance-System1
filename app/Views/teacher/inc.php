<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<div class="mb-4 flex gap-6 border-b border-line">
    <a href="<?= site_url('teacher/inc') ?>" class="tab-link <?= $show === 'open' ? 'active' : '' ?>">Open requirements</a>
    <a href="<?= site_url('teacher/inc?show=all') ?>" class="tab-link <?= $show === 'all' ? 'active' : '' ?>">All, including verified</a>
</div>

<section class="surface overflow-hidden">
    <ul class="m-0 list-none p-0">
        <?php foreach ($rows as $r):
            $state = $r['status'] === 'completed' ? 'verified' : ($r['student_reported_at'] ? 'reported' : 'pending');
            $open  = $r['status'] === 'pending' && $r['cs_status'] === 'INC' && $r['clearance_status'] === 'in_progress';
        ?>
            <li class="border-b border-line px-5 py-4 last:border-b-0">
                <div class="flex flex-wrap items-start gap-3">
                    <span class="avatar h-10 w-10 text-xs"><?= esc(initials(person_name($r))) ?></span>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold"><?= esc(person_name($r)) ?> <span class="font-mono text-xs font-normal text-ink-muted"><?= esc($r['student_number']) ?></span></div>
                        <div class="text-sm text-ink-muted"><?= esc($r['subject_code']) ?> · <?= esc($r['subject_title']) ?> · <?= esc(term_label($r, true)) ?></div>
                        <div class="mt-1.5 font-semibold">“<?= esc($r['description']) ?>”</div>
                        <?php if ($r['student_reported_at'] && $r['status'] === 'pending'): ?>
                            <div class="mt-1 text-sm text-[#285f94]"><i class="bi bi-send-check" aria-hidden="true"></i> Reported complete <?= time_ago($r['student_reported_at']) ?><?= $r['student_note'] ? ': “' . esc($r['student_note']) . '”' : '' ?></div>
                        <?php endif; ?>
                        <?php if ($r['verified_at']): ?><div class="mt-1 text-xs text-ink-muted">Verified <?= fmt_datetime($r['verified_at']) ?><?= $r['verification_notes'] ? ' · ' . esc($r['verification_notes']) : '' ?></div><?php endif; ?>
                    </div>
                    <?= status_badge($state) ?>
                </div>
                <?php if ($open): ?>
                    <div class="ms-[52px] mt-3 flex flex-wrap items-center gap-2">
                        <form action="<?= site_url('teacher/inc/' . $r['id'] . '/verify') ?>" method="post" class="flex gap-2" data-confirm="Confirm that <?= esc(person_name($r), 'attr') ?> completed this requirement with you?" data-confirm-title="Verify requirement" data-confirm-button="Verify">
                            <?= csrf_field() ?>
                            <input class="form-control form-control-sm" name="notes" maxlength="255" placeholder="Verification note (optional)" aria-label="Verification note">
                            <button class="btn btn-primary btn-sm whitespace-nowrap" type="submit"><i class="bi bi-check2-circle"></i>Verify</button>
                        </form>
                        <a class="btn btn-light btn-sm" href="<?= site_url('teacher/subjects/' . $r['subject_offering_id']) ?>?cs=<?= $r['cs_id'] ?>">Open review</a>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($rows === []): ?><li class="empty-state"><i class="bi bi-check2-all"></i>No INC requirements to show.</li><?php endif; ?>
    </ul>
</section>
<?= $this->endSection() ?>
