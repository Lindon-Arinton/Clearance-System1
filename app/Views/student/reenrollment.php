<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php if ($subjects === []): ?>
    <div class="surface empty-state"><i class="bi bi-emoji-smile"></i><p class="font-semibold text-ink">No failed subjects</p><p>You have nothing to re-enroll.</p></div>
<?php endif; ?>

<div class="flex flex-col gap-6">
<?php foreach ($subjects as $cs):
    $key      = subject_status_key($cs);
    $history  = $receipts[(int) $cs['id']] ?? [];
    $latest   = $history[0] ?? null;
    $canUpload = $key === 'RE_ENROLLMENT_REQUIRED' && $cs['clearance_status'] === 'in_progress';
?>
    <section class="surface overflow-hidden">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            <div class="border-b border-line p-5 lg:border-b-0 lg:border-r">
                <div class="flex flex-wrap items-center gap-2"><?= status_badge('FAILED', 'Failed subject') ?><?= status_badge($key) ?></div>
                <dl class="mb-0 mt-4 flex flex-col gap-3">
                    <div><dt class="kv-label">Subject</dt><dd class="kv-value mb-0"><?= esc($cs['subject_title']) ?> <span class="text-sm font-normal text-ink-muted">(<?= esc($cs['subject_code']) ?>)</span></dd></div>
                    <div><dt class="kv-label">Teacher</dt><dd class="kv-value mb-0"><?= esc($cs['teacher_name']) ?></dd></div>
                    <div><dt class="kv-label">Term</dt><dd class="kv-value mb-0"><?= esc(term_label($cs)) ?> · <?= esc($cs['reference_no']) ?></dd></div>
                    <div><dt class="kv-label">Status</dt><dd class="kv-value mb-0">FAILED<?= $cs['final_grade'] ? ' · Grade ' . esc($cs['final_grade']) : '' ?></dd></div>
                </dl>
                <?php if ($cs['remarks']): ?><p class="mt-3 text-sm text-ink-soft">“<?= esc($cs['remarks']) ?>”</p><?php endif; ?>

                <div class="mt-4 rounded-xl border border-[#f1cfc7] bg-[#fdf5f3] p-4 text-sm">
                    <div class="font-bold text-[#8f2f24]">Action required</div>
                    <?php if ($key === 'RE_ENROLLMENT_APPROVED'): ?>
                        <p class="mb-0 mt-1 text-ink-soft">Re-enrollment confirmed on <?= fmt_datetime($cs['reenrollment_confirmed_at']) ?>. This subject is resolved on your clearance. The original FAILED record stays in your history.</p>
                    <?php else: ?>
                        <ol class="mb-0 mt-2 ps-4 text-ink-soft">
                            <li>Re-enroll in this subject according to school procedures.</li>
                            <li>Pay the re-enrollment fee at the cashier.</li>
                            <li>Upload the new official receipt here for registrar validation.</li>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>

            <div class="p-5">
                <?php if ($latest && $latest['status'] === 'reupload_required' && $canUpload): ?>
                    <div class="mb-4 rounded-xl border border-[#f3d2b5] bg-[#fdf3ea] p-4 text-sm" role="alert">
                        <div class="font-bold text-[#8a4414]"><i class="bi bi-arrow-counterclockwise"></i> Receipt re-upload required</div>
                        <p class="mb-0 mt-1 text-ink">“<?= esc($latest['reupload_reason']) ?>”</p>
                    </div>
                <?php endif; ?>

                <?php if ($canUpload): ?>
                    <h3 class="text-base font-bold">Upload re-enrollment receipt</h3>
                    <form action="<?= site_url('student/reenrollment/' . $cs['id'] . '/upload') ?>" method="post" enctype="multipart/form-data" class="mt-3 flex flex-col gap-3"
                          data-confirm="Submit this re-enrollment receipt for <?= esc($cs['subject_title'], 'attr') ?>?" data-confirm-title="Submit receipt" data-confirm-button="Submit receipt">
                        <?= csrf_field() ?>
                        <div>
                            <label class="form-label" for="file<?= $cs['id'] ?>">Receipt file (JPG, PNG or PDF, max <?= round($maxBytes / 1048576) ?> MB)</label>
                            <input class="form-control" type="file" id="file<?= $cs['id'] ?>" name="receipt" accept=".jpg,.jpeg,.png,.pdf" required data-file-preview="#preview<?= $cs['id'] ?>" data-max-bytes="<?= $maxBytes ?>">
                        </div>
                        <div id="preview<?= $cs['id'] ?>" class="flex min-h-[120px] items-center justify-center rounded-xl border border-dashed border-[#cfd8ce] bg-[#f7f9f5] p-2 text-sm text-ink-muted">No file selected yet.</div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div><label class="form-label" for="or<?= $cs['id'] ?>">OR number</label><input class="form-control" id="or<?= $cs['id'] ?>" name="or_number" maxlength="40" required></div>
                            <div><label class="form-label" for="amt<?= $cs['id'] ?>">Amount (₱)</label><input class="form-control" id="amt<?= $cs['id'] ?>" name="amount" type="number" step="0.01" min="1" required></div>
                            <div><label class="form-label" for="date<?= $cs['id'] ?>">Payment date</label><input class="form-control" id="date<?= $cs['id'] ?>" name="payment_date" type="date" max="<?= date('Y-m-d') ?>" required></div>
                        </div>
                        <button type="submit" class="btn btn-primary self-start"><i class="bi bi-cloud-arrow-up"></i>Upload re-enrollment receipt</button>
                    </form>
                <?php elseif ($key === 'RE_ENROLLMENT_RECEIPT_PENDING'): ?>
                    <div class="flex items-start gap-3 rounded-xl border border-[#cfe0f1] bg-[#eef5fb] p-4 text-sm text-[#285f94]"><i class="bi bi-hourglass-split mt-0.5"></i>Your re-enrollment receipt is waiting for registrar validation.</div>
                <?php endif; ?>

                <?php if ($history !== []): ?>
                    <h3 class="mt-5 text-sm font-bold uppercase tracking-wider text-ink-muted">Receipts on record</h3>
                    <ul class="mt-2 flex list-none flex-col gap-2 p-0">
                        <?php foreach ($history as $r): ?>
                            <li class="flex flex-wrap items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                                <span class="font-semibold"><?= esc($r['or_number']) ?></span><span class="text-ink-muted"><?= peso($r['amount']) ?> · <?= fmt_date($r['submitted_at']) ?></span>
                                <span class="ms-auto"><?= status_badge($r['status']) ?></span>
                                <button type="button" class="bg-transparent p-0 text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#previewModal"
                                        data-preview-url="<?= site_url('files/reenrollment-receipt/' . $r['id']) ?>" data-preview-type="<?= esc($r['mime_type'], 'attr') ?>" data-preview-title="Re-enrollment receipt <?= esc($r['or_number'], 'attr') ?>"><i class="bi bi-eye"></i><span class="visually-hidden">View</span></button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?= $this->endSection() ?>
