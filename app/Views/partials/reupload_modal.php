<?php
/**
 * @var string $action form URL
 * @var string $who    student name for the heading
 */
$templates = [
    'blurry'     => 'Please upload a clearer image where the receipt number, student name, date, and amount are readable.',
    'unreadable' => 'The receipt details cannot be read. Please upload a sharper photo or a scanned copy.',
    'wrong'      => 'The uploaded file is not the official tuition receipt for this semester. Please upload the correct receipt.',
    'incomplete' => 'Part of the receipt is cut off. Please upload a photo showing the whole receipt.',
    'mismatch'   => 'The name or amount on the receipt does not match your student record. Please check and upload the correct receipt.',
    'other'      => '',
];
?>
<div class="modal fade" id="reuploadModal" tabindex="-1" aria-labelledby="reuploadTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="<?= esc($action, 'attr') ?>" method="post"
              data-confirm="Send this re-upload request to <?= esc($who, 'attr') ?>?" data-confirm-title="Request re-upload" data-confirm-button="Send request" data-confirm-variant="danger">
            <?= csrf_field() ?>
            <div class="modal-header"><h2 class="modal-title" id="reuploadTitle">Request re-upload</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body flex flex-col gap-3">
                <p class="text-sm text-ink-muted">The student sees this reason and is asked to upload a new receipt.</p>
                <div>
                    <label class="form-label" for="reasonCode">Reason</label>
                    <select class="form-select" id="reasonCode" name="reason_code" required data-reason-select="#reasonText">
                        <option value="">Choose a reason…</option>
                        <?php foreach (reupload_reasons() as $code => $label): ?>
                            <option value="<?= $code ?>" data-template="<?= esc($templates[$code] ?? '', 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="reasonText">Message to the student</label>
                    <textarea class="form-control" id="reasonText" name="reason" rows="4" minlength="10" maxlength="1000" required></textarea>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-outline-danger" type="submit"><i class="bi bi-arrow-counterclockwise"></i>Request re-upload</button></div>
        </form>
    </div>
</div>
