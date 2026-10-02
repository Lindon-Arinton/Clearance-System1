<?php
/**
 * Teacher e-signature upload.
 *
 * @var array $p            teacher profile row
 * @var bool  $hasSignature whether an e-signature is already on file
 */
?>
<section class="surface scroll-mt-40 p-5 lg:col-span-2 <?= $hasSignature ? '' : 'border-[#e8c98f] shadow-lift' ?>" id="signature">
    <?php if (! $hasSignature): ?>
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-[#f0dcc0] bg-[#fdf6ec] p-4 text-sm text-[#7a4a12]" role="alert">
            <i class="bi bi-pen-fill mt-0.5 text-lg" aria-hidden="true"></i>
            <div>
                <div class="font-bold">Upload your e-signature to activate your account</div>
                <div class="mt-0.5">Your e-signature is added automatically to a student's clearance every time you mark a subject <strong>PASSED</strong>. You can't review students until it is uploaded.</div>
            </div>
        </div>
    <?php endif; ?>

    <div class="eyebrow">E-signature</div>
    <h2 class="section-title mt-1"><?= $hasSignature ? 'Your e-signature' : 'Set up your e-signature' ?></h2>
    <p class="mt-1 text-sm text-ink-muted">
        Sign on white paper with a dark pen, then take a clear photo or scan and crop it close to the signature. A transparent PNG looks best.
        <?php if ($hasSignature): ?>Replacing it only affects new approvals — clearances you already signed keep the original.<?php endif; ?>
    </p>

    <div class="mt-5 grid gap-5 md:grid-cols-2">
        <div>
            <span class="form-label d-block"><?= $hasSignature ? 'Current signature' : 'Preview' ?></span>
            <div id="signaturePreview" class="flex h-36 items-center justify-center overflow-hidden rounded-xl [&_img]:max-h-full border border-dashed border-[#cfd8ce] bg-white p-3">
                <?php if ($hasSignature): ?>
                    <img src="<?= site_url('files/signature/' . $p['id']) ?>?v=<?= md5($p['signature_path']) ?>" alt="Your current e-signature" class="max-h-full max-w-full object-contain">
                <?php else: ?>
                    <span class="text-sm text-ink-faint">Your signature will appear here</span>
                <?php endif; ?>
            </div>
        </div>
        <form action="<?= site_url('profile/signature') ?>" method="post" enctype="multipart/form-data" class="flex flex-col gap-3"
              data-confirm="Use this image as your e-signature? It will be added to every clearance you mark PASSED." data-confirm-title="Save e-signature" data-confirm-button="Save e-signature">
            <?= csrf_field() ?>
            <div>
                <label class="form-label" for="signatureFile"><?= $hasSignature ? 'Replace with a new image' : 'Signature image' ?></label>
                <input class="form-control" type="file" id="signatureFile" name="signature" accept=".png,.jpg,.jpeg,image/png,image/jpeg" required
                       data-file-preview="#signaturePreview" data-max-bytes="<?= 2 * 1024 * 1024 ?>" aria-describedby="sigHelp">
                <div class="form-text" id="sigHelp">PNG or JPG, up to 2 MB.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="sigConsent" required>
                <label class="form-check-label text-sm text-ink-soft" for="sigConsent">This is my own signature, and I authorize its use on clearances I approve.</label>
            </div>
            <button class="btn btn-primary self-start" type="submit"><i class="bi bi-pen"></i><?= $hasSignature ? 'Replace e-signature' : 'Save e-signature' ?></button>
        </form>
    </div>
</section>
