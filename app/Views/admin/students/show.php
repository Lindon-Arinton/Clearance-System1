<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<a href="<?= site_url('admin/students/' . $student['id'] . '/edit') ?>" class="btn btn-light h-10 px-3 text-sm"><i class="bi bi-pencil"></i>Edit</a>
<form action="<?= site_url('admin/students/' . $student['id'] . '/reset-password') ?>" method="post" data-confirm="Reset this student's password? A new temporary password will be shown once." data-confirm-button="Reset password">
    <?= csrf_field() ?><button class="btn btn-light h-10 px-3 text-sm" type="submit"><i class="bi bi-key"></i>Reset password</button>
</form>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partials/temp_password') ?>

<?php if ($student['approval_status'] === 'pending'): ?>
    <section class="mb-6 rounded-2xl border border-[#f0dcc0] bg-[#fdf6ec] p-5">
        <div class="flex flex-wrap items-start gap-4">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-paper text-[#a4521a]"><i class="bi bi-person-exclamation text-xl" aria-hidden="true"></i></span>
            <div class="min-w-0 flex-1">
                <div class="font-bold text-[#7a4a12]">Self-registered account waiting for approval</div>
                <p class="mb-0 mt-1 text-sm text-ink-soft">Signed up <?= fmt_datetime($student['created_at']) ?> with <strong><?= esc($student['email']) ?></strong>. Check the student ID number, name and program against school records. You can correct the ID number before approving. Rejecting deletes the account so the ID number and email can be registered again correctly.</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-[#f0dcc0] pt-4 sm:ps-[60px]">
            <form action="<?= site_url('admin/students/' . $student['id'] . '/approve') ?>" method="post" class="flex flex-wrap items-end gap-2"
                  data-confirm="Approve <?= esc(person_name($student), 'attr') ?> with this student ID number? They will be able to sign in." data-confirm-title="Approve sign-up" data-confirm-button="Approve">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="assignNumber">Student ID number</label>
                    <input class="form-control w-44 font-mono" id="assignNumber" name="student_number" value="<?= esc(old('student_number', $student['student_number'])) ?>" placeholder="9785" inputmode="numeric" pattern="\d{3,10}" required title="Digits only, e.g. 9785">
                </div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i>Approve</button>
            </form>
            <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="bi bi-x-lg"></i>Reject</button>
        </div>
    </section>

    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" action="<?= site_url('admin/students/' . $student['id'] . '/reject') ?>" method="post"
                  data-confirm="Reject and delete this sign-up? This cannot be undone." data-confirm-variant="danger" data-confirm-button="Reject sign-up">
                <?= csrf_field() ?>
                <div class="modal-header"><h2 class="modal-title" id="rejectTitle">Reject sign-up</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <label class="form-label" for="rejectReason">Reason (kept in the audit log)</label>
                    <textarea class="form-control" id="rejectReason" name="reason" rows="3" required minlength="5" maxlength="500" placeholder="e.g. Student number does not match school records"></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Reject sign-up</button></div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
    <aside class="flex flex-col gap-6">
        <section class="surface p-5">
            <div class="flex items-center gap-3">
                <span class="avatar h-12 w-12"><?= esc(initials(person_name($student))) ?></span>
                <div><div class="text-lg font-bold"><?= esc(person_name($student)) ?></div><div class="font-mono text-sm text-ink-muted"><?= esc($student['student_number'] ?? 'No student number yet') ?></div></div>
            </div>
            <dl class="mb-0 mt-5 grid grid-cols-2 gap-3 text-sm">
                <div class="col-span-2"><dt class="kv-label">Program</dt><dd class="mb-0 font-semibold"><?= esc($student['program_code'] . ' — ' . $student['program_name']) ?></dd></div>
                <div><dt class="kv-label">Year level</dt><dd class="mb-0 font-semibold"><?= year_level_label($student['year_level']) ?></dd></div>
                <div><dt class="kv-label">Section</dt><dd class="mb-0 font-semibold"><?= esc($student['section'] ?: '—') ?></dd></div>
                <div><dt class="kv-label">Status</dt><dd class="mb-0 mt-1"><?= $student['approval_status'] === 'pending' ? status_badge('pending_approval') : status_badge($student['status']) ?></dd></div>
                <div><dt class="kv-label">Last sign-in</dt><dd class="mb-0 font-semibold"><?= fmt_date($student['last_login_at']) ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Email</dt><dd class="mb-0 break-all"><?= esc($student['email'] ?: '—') ?></dd></div>
                <div class="col-span-2"><dt class="kv-label">Next-semester eligibility</dt><dd class="mb-0 mt-1"><?= status_badge($currentEligibility['status']) ?></dd></div>
            </dl>
        </section>

        <section class="surface overflow-hidden">
            <div class="border-b border-line p-5"><div class="eyebrow">Clearance history</div><h2 class="section-title mt-1">All terms</h2></div>
            <ul class="m-0 list-none p-0">
                <?php foreach ($clearances as $c): ?>
                    <li><a class="list-row" href="<?= site_url('admin/clearances/' . $c['id']) ?>">
                        <span class="min-w-0 flex-1"><span class="block font-semibold"><?= esc(term_label($c, true)) ?></span><span class="block text-xs text-ink-muted"><?= esc($c['reference_no']) ?></span></span>
                        <?= status_badge($c['status']) ?>
                    </a></li>
                <?php endforeach; ?>
                <?php if ($clearances === []): ?><li class="empty-state py-8"><i class="bi bi-clipboard"></i>No clearances yet.</li><?php endif; ?>
            </ul>
        </section>
    </aside>

    <section class="surface overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line p-5">
            <div><div class="eyebrow">Enrollment records</div><h2 class="section-title mt-1"><?= $term ? esc(term_label($term)) : 'No term' ?></h2></div>
            <form method="get" class="flex items-center gap-2">
                <label class="visually-hidden" for="termSel">Term</label>
                <select class="form-select form-select-sm" id="termSel" name="term" onchange="this.form.submit()">
                    <?php foreach ($terms as $t): ?><option value="<?= $t['id'] ?>" <?= $term && (int) $term['id'] === (int) $t['id'] ? 'selected' : '' ?>><?= esc(term_label($t)) ?><?= (int) $t['is_current'] ? ' (current)' : '' ?></option><?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($eligibility): ?>
            <div class="mx-5 mt-5 flex items-start gap-3 rounded-xl border p-3 text-sm <?= $eligibility['ok'] ? 'border-[#cde5d4] bg-[#eef7f1] text-[#1f6a3f]' : 'border-[#f1cfc7] bg-[#fdf5f3] text-[#8f2f24]' ?>">
                <i class="bi <?= $eligibility['ok'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> mt-0.5" aria-hidden="true"></i>
                <span><strong><?= $eligibility['ok'] ? 'Eligible to enroll in this term.' : 'Not eligible to enroll in this term.' ?></strong> <?= esc($eligibility['reason']) ?></span>
            </div>
        <?php endif; ?>

        <div class="mt-3 overflow-x-auto">
            <table class="table">
                <thead><tr><th class="ps-5">Subject</th><th>Teacher</th><th>Type</th><th>Enrollment</th><th>Clearance</th><th class="pe-5"></th></tr></thead>
                <tbody>
                <?php foreach ($enrollments as $e): ?>
                    <tr>
                        <td class="ps-5"><div class="font-semibold"><?= esc($e['code']) ?> · <?= esc($e['title']) ?></div><div class="text-xs text-ink-muted">Section <?= esc($e['section']) ?></div></td>
                        <td><?= esc($e['teacher_name']) ?></td>
                        <td><?= $e['enrollment_type'] === 're_enrollment' ? '<span class="badge-status badge-orange"><i class="bi bi-arrow-repeat"></i>Re-enrollment</span>' : 'Regular' ?></td>
                        <td><?= status_badge($e['status']) ?></td>
                        <td><?= $e['cs_status'] ? ($e['cs_status'] === 'PASSED' && ! $e['signed_at'] ? status_badge('AWAITING_SIGNATURE') : status_badge(subject_status_key(['status' => $e['cs_status'], 'reenrollment_status' => $e['reenrollment_status']]))) : '<span class="text-xs text-ink-muted">Not on a card</span>' ?></td>
                        <td class="pe-5 text-end">
                            <?php if ($e['status'] === 'enrolled' && in_array($e['cs_status'], [null, 'PENDING'], true)): ?>
                                <form action="<?= site_url('admin/enrollments/' . $e['id'] . '/drop') ?>" method="post" data-confirm="Drop <?= esc($e['code'], 'attr') ?> for this student? Any pending clearance row for it is removed." data-confirm-variant="danger" data-confirm-button="Drop subject">
                                    <?= csrf_field() ?><button class="btn btn-light btn-sm text-[#a3362a]" type="submit">Drop</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($enrollments === []): ?><tr><td colspan="6" class="py-8 text-center text-ink-muted">No enrollment records for this term.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($term && $term['status'] !== 'closed'): ?>
            <form action="<?= site_url('admin/students/' . $student['id'] . '/enroll') ?>" method="post" class="flex flex-wrap items-end gap-3 border-t border-line bg-[#f9faf6] p-5">
                <?= csrf_field() ?>
                <div class="min-w-[260px] flex-1"><label class="form-label" for="offering">Enroll in subject</label>
                    <select class="form-select" id="offering" name="subject_offering_id" required><option value="">Choose a subject offering…</option>
                        <?php foreach ($offerings as $o): ?><option value="<?= $o['id'] ?>"><?= esc($o['code'] . ' · ' . $o['title'] . ' — Sec ' . $o['section'] . ' · ' . $o['teacher_name']) ?></option><?php endforeach; ?>
                    </select></div>
                <div><label class="form-label" for="etype">Type</label>
                    <select class="form-select" id="etype" name="enrollment_type"><option value="regular">Regular</option><option value="re_enrollment">Re-enrollment</option></select></div>
                <button class="btn btn-primary" type="submit" <?= $eligibility && ! $eligibility['ok'] ? 'disabled' : '' ?>><i class="bi bi-plus-lg"></i>Enroll</button>
            </form>
        <?php endif; ?>
    </section>
</div>
<?= $this->endSection() ?>
