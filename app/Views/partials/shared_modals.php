<!-- Toasts -->
<div id="toastStack" class="toast-container no-print fixed bottom-0 right-0 z-[1090] p-3" aria-live="polite"></div>
<?php foreach (['success', 'error', 'info'] as $tone): ?>
    <?php if (session()->getFlashdata('toast_' . $tone)): ?>
        <span hidden data-flash-toast="<?= $tone ?>" data-message="<?= esc(session()->getFlashdata('toast_' . $tone), 'attr') ?>"></span>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Confirmation dialog for important actions -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="confirmModalTitle" data-confirm-title>Please confirm</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-[15px] text-ink-soft" data-confirm-body></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-confirm-ok>Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt / document preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="previewModalTitle">Document preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-[#ebe8e1]" data-preview-body></div>
            <div class="modal-footer">
                <a class="btn btn-light" data-preview-download href="#"><i class="bi bi-download"></i>Download</a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
