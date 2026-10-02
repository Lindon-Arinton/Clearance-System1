<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php
$clearance = $bundle['clearance'];
$receipt   = $bundle['receipt'];
$status    = $clearance['status'] ?? 'not_started';
$canUpload = in_array($status, ['awaiting_receipt', 'reupload_required'], true);
?>

<?php if (! $term): ?>
    <div class="surface empty-state"><i class="bi bi-calendar-x"></i>No active school term.</div>
<?php elseif (! $clearance): ?>
    <div class="surface empty-state">
        <i class="bi bi-clipboard-plus"></i>
        <p class="font-semibold text-ink">You have not started a clearance for <?= esc(term_label($term)) ?>.</p>
        <p>Start your clearance application from the dashboard, then upload your tuition receipt here.</p>
        <a href="<?= site_url('student/dashboard') ?>" class="btn btn-primary mt-3">Go to dashboard</a>
    </div>
<?php else: ?>
<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
    <div class="flex min-w-0 flex-col gap-6">
        <!-- Current status -->
        <section class="surface p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="eyebrow"><?= esc(term_label($term)) ?> · <?= esc($clearance['reference_no']) ?></div>
                    <h2 class="section-title mt-1">Current status</h2>
                </div>
                <?= status_badge($receipt['status'] ?? 'not_submitted') ?>
            </div>
            <?php if ($receipt): ?>
                <dl class="mb-0 mt-4 grid gap-4 sm:grid-cols-4">
                    <div><dt class="kv-label">OR number</dt><dd class="kv-value mb-0"><?= esc($receipt['or_number']) ?></dd></div>
                    <div><dt class="kv-label">Amount</dt><dd class="kv-value mb-0"><?= peso($receipt['amount']) ?></dd></div>
                    <div><dt class="kv-label">Upload date</dt><dd class="kv-value mb-0"><?= fmt_datetime($receipt['submitted_at']) ?></dd></div>
                    <div><dt class="kv-label">Attempt</dt><dd class="kv-value mb-0">#<?= (int) $receipt['attempt_no'] ?></dd></div>
                </dl>
            <?php else: ?>
                <p class="mt-3 text-ink-soft">No receipt uploaded yet. Your clearance card is created as soon as the registrar approves your receipt.</p>
            <?php endif; ?>

            <?php if ($status === 'reupload_required' && $receipt): ?>
                <div class="mt-5 rounded-xl border border-[#f3d2b5] bg-[#fdf3ea] p-4" role="alert">
                    <div class="flex items-center gap-2 font-bold text-[#8a4414]"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Receipt re-upload required</div>
                    <div class="mt-2 text-sm text-ink-soft"><span class="font-semibold">Reason:</span> <?= esc(reupload_reasons()[$receipt['reupload_reason_code']] ?? 'Other') ?></div>
                    <p class="mt-1 text-[15px] text-ink">“<?= esc($receipt['reupload_reason']) ?>”</p>
                    <a href="#uploadForm" class="btn btn-primary mt-3"><i class="bi bi-cloud-arrow-up"></i>Upload new receipt</a>
                </div>
            <?php elseif (in_array($receipt['status'] ?? '', ['submitted', 'under_review'], true)): ?>
                <div class="mt-5 flex items-start gap-3 rounded-xl border border-[#cfe0f1] bg-[#eef5fb] p-4 text-sm text-[#285f94]">
                    <i class="bi bi-hourglass-split mt-0.5" aria-hidden="true"></i>
                    <span><?= $receipt['status'] === 'under_review' ? 'The registrar is reviewing your receipt now.' : 'Your receipt is in the registrar queue.' ?> You will be notified once it is approved or if a clearer copy is needed.</span>
                </div>
            <?php elseif (($receipt['status'] ?? '') === 'approved'): ?>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#cde5d4] bg-[#eef7f1] p-4 text-sm text-[#1f6a3f]">
                    <span><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Approved on <?= fmt_datetime($receipt['reviewed_at']) ?>. Your clearance card is active.</span>
                    <a href="<?= site_url('student/card') ?>" class="font-semibold text-brand-800 hover:underline">View clearance card <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($canUpload): ?>
            <!-- Upload form -->
            <section class="surface p-5" id="uploadForm">
                <div class="eyebrow">Step 1</div>
                <h2 class="section-title mt-1"><?= $status === 'reupload_required' ? 'Upload a new receipt' : 'Upload tuition receipt' ?></h2>
                <p class="mt-1 text-sm text-ink-muted">Accepted: JPG, JPEG, PNG or PDF up to <?= round($maxBytes / 1048576) ?> MB. Make sure the OR number, your name, the date and the amount are readable.</p>

                <form action="<?= site_url('student/receipt') ?>" method="post" enctype="multipart/form-data" class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
                      data-confirm="Submit this receipt to the registrar for validation?" data-confirm-title="Submit receipt" data-confirm-button="Submit receipt">
                    <?= csrf_field() ?>
                    <div class="flex flex-col gap-4">
                        <div>
                            <label for="receipt" class="form-label">Receipt file</label>
                            <input class="form-control" type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required
                                   data-file-preview="#filePreview" data-file-meta="#fileMeta" data-max-bytes="<?= $maxBytes ?>" aria-describedby="fileMeta">
                            <div class="form-text" id="fileMeta"></div>
                        </div>
                        <div>
                            <label for="or_number" class="form-label">Official receipt (OR) number</label>
                            <input class="form-control" id="or_number" name="or_number" value="<?= esc(old('or_number')) ?>" maxlength="40" required placeholder="e.g. OR-2026-18492" pattern="[A-Za-z0-9][A-Za-z0-9\-\/]{2,39}">
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="amount" class="form-label">Amount paid (₱)</label>
                                <input class="form-control" id="amount" name="amount" type="number" step="0.01" min="1" value="<?= esc(old('amount')) ?>" required>
                            </div>
                            <div>
                                <label for="payment_date" class="form-label">Payment date</label>
                                <input class="form-control" id="payment_date" name="payment_date" type="date" max="<?= date('Y-m-d') ?>" value="<?= esc(old('payment_date')) ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary self-start px-5"><i class="bi bi-send"></i>Submit receipt</button>
                    </div>
                    <div>
                        <span class="form-label d-block">Preview</span>
                        <div id="filePreview" class="flex min-h-[260px] items-center justify-center rounded-xl border border-dashed border-[#cfd8ce] bg-[#f7f9f5] p-3">
                            <div class="empty-state"><i class="bi bi-image"></i><span>No file selected yet.</span></div>
                        </div>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </div>

    <!-- Upload history -->
    <aside class="surface h-fit p-5">
        <div class="eyebrow">Upload history</div>
        <h2 class="section-title mt-1">All attempts</h2>
        <p class="mt-1 text-sm text-ink-muted">Every upload is kept on record.</p>
        <ol class="mt-4 flex list-none flex-col gap-3 p-0">
            <?php foreach ($bundle['receipts'] as $r): ?>
                <li class="rounded-xl border border-line bg-[#f9faf6] p-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold">Attempt #<?= (int) $r['attempt_no'] ?></span><?= status_badge($r['status']) ?>
                    </div>
                    <div class="mt-1 text-xs text-ink-muted"><?= esc($r['or_number']) ?> · <?= peso($r['amount']) ?> · <?= fmt_datetime($r['submitted_at']) ?></div>
                    <?php if ($r['status'] === 'reupload_required'): ?>
                        <div class="mt-2 text-xs text-[#8a4414]">“<?= esc($r['reupload_reason']) ?>”</div>
                    <?php endif; ?>
                    <button type="button" class="mt-2 bg-transparent p-0 text-sm font-semibold text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#previewModal"
                            data-preview-url="<?= site_url('files/tuition-receipt/' . $r['id']) ?>" data-preview-type="<?= esc($r['mime_type'], 'attr') ?>" data-preview-title="Receipt <?= esc($r['or_number'], 'attr') ?> · attempt <?= (int) $r['attempt_no'] ?>">
                        <i class="bi bi-eye"></i> View file
                    </button>
                </li>
            <?php endforeach; ?>
            <?php if ($bundle['receipts'] === []): ?><li class="text-sm text-ink-muted">No uploads yet.</li><?php endif; ?>
        </ol>
    </aside>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
