<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<span class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand-50 px-3 text-sm font-semibold text-brand-800"><i class="bi bi-shield-check" aria-hidden="true"></i>Secure faculty session</span>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$o       = $offering;
$filters = ['all' => 'All students', 'action' => 'Needs action', 'passed' => 'Passed', 'inc' => 'INC', 'failed' => 'Failed'];
$base    = 'teacher/subjects/' . $o['id'];
$sel     = $selected;
?>
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <div class="eyebrow">Assigned subject</div>
        <div class="mt-1 flex flex-wrap items-center gap-3">
            <h2 class="font-serif text-3xl text-brand-900"><?= esc($o['title']) ?></h2>
            <span class="rounded-md bg-[#eceeea] px-2 py-0.5 font-mono text-xs font-semibold text-ink-soft"><?= esc($o['code']) ?></span>
        </div>
        <p class="mt-1 text-sm text-ink-muted"><?= esc(term_label($o)) ?> · Section <?= esc($o['section']) ?> · <?= rtrim(rtrim($o['units'], '0'), '.') ?> units · <?= (int) $o['students'] ?> assigned students</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-light" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-journal-bookmark"></i>Switch subject</button>
        <div class="dropdown-menu dropdown-menu-end">
            <?php foreach ($switchable as $other): ?>
                <a class="dropdown-item <?= (int) $other['id'] === (int) $o['id'] ? 'active' : '' ?>" href="<?= site_url('teacher/subjects/' . $other['id']) ?>"><?= esc($other['code'] . ' · ' . $other['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="stat-card">
        <div class="flex items-start justify-between"><span class="stat-label">Awaiting review</span><?php if ($o['pending'] + $o['awaiting_sign'] + $o['reported'] > 0): ?><span class="badge-status badge-blue"><i class="bi bi-circle-fill" aria-hidden="true"></i>Action</span><?php endif; ?></div>
        <span class="stat-value"><?= (int) $o['pending'] + (int) $o['awaiting_sign'] + (int) $o['reported'] ?></span><span class="stat-hint">Students need your decision or signature</span>
    </div>
    <div class="stat-card"><span class="stat-label">Passed this term</span><span class="stat-value"><?= (int) $o['passed'] ?></span><span class="stat-hint">Signed and visible to students</span></div>
    <div class="stat-card"><span class="stat-label">INC follow-ups</span><span class="stat-value text-[#a4521a]"><?= (int) $o['inc'] ?></span><span class="stat-hint">Need a missing requirement</span></div>
</div>

<div class="surface grid overflow-hidden lg:grid-cols-[minmax(0,340px)_minmax(0,1fr)]">
    <!-- Student list -->
    <div class="border-b border-line lg:border-b-0 lg:border-r">
        <div class="p-4">
            <label class="visually-hidden" for="studentSearch">Search assigned students</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-muted" aria-hidden="true"></i>
                <input id="studentSearch" type="search" class="form-control pl-10" placeholder="Search assigned students" data-filter-input="#studentList" data-filter-count="#shownCount">
            </div>
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="font-semibold text-ink-soft" id="shownCount"><?= count($rows) ?> shown</span>
                <div class="dropdown">
                    <button class="bg-transparent font-semibold text-brand-700" data-bs-toggle="dropdown" aria-expanded="false"><?= esc($filters[$filter] ?? 'All students') ?> <i class="bi bi-chevron-down"></i></button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($filters as $k => $label): ?>
                            <a class="dropdown-item <?= $filter === $k ? 'active' : '' ?>" href="<?= site_url($base) ?>?filter=<?= $k ?>"><?= $label ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <ul class="m-0 max-h-[70vh] list-none overflow-y-auto border-t border-line p-0" id="studentList">
            <?php foreach ($rows as $r):
                $name = person_name($r);
                $active = $sel && (int) $r['cs_id'] === (int) $sel['id'];
            ?>
                <li data-filter-text="<?= esc($name . ' ' . $r['student_number'], 'attr') ?>">
                    <?php if ($r['cs_id']): ?>
                        <a href="<?= site_url($base) ?>?filter=<?= esc($filter, 'url') ?>&cs=<?= $r['cs_id'] ?>" class="list-row <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="true"' : '' ?>>
                    <?php else: ?>
                        <div class="list-row opacity-70">
                    <?php endif; ?>
                        <span class="avatar h-9 w-9 text-xs"><?= esc(initials($name)) ?></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold"><?= esc($name) ?></span>
                            <span class="block text-xs text-ink-muted"><?= esc($r['student_number']) ?> · <?= $r['decided_at'] ? fmt_date($r['decided_at']) : esc($r['program_code']) ?></span>
                        </span>
                        <span class="flex flex-col items-end gap-1">
                            <?php if (! $r['cs_id']): ?>
                                <span class="badge-status badge-gray"><i class="bi bi-lock" aria-hidden="true"></i>No card yet</span>
                            <?php elseif ($r['status'] === 'PENDING' && $r['clearance_status'] === 'in_progress'): ?>
                                <span class="badge-status badge-blue"><i class="bi bi-eye-fill" aria-hidden="true"></i>Under review</span>
                            <?php elseif ($r['status'] === 'PASSED' && ! $r['signed_at']): ?>
                                <?= status_badge('AWAITING_SIGNATURE', 'Sign') ?>
                            <?php else: ?>
                                <?= status_badge($r['status']) ?>
                            <?php endif; ?>
                            <?php if ((int) $r['reported'] > 0): ?><span class="text-[10.5px] font-semibold text-[#285f94]">Reported done</span><?php endif; ?>
                        </span>
                    <?= $r['cs_id'] ? '</a>' : '</div>' ?>
                </li>
            <?php endforeach; ?>
            <?php if ($rows === []): ?><li class="empty-state"><i class="bi bi-people"></i>No students match this filter.</li><?php endif; ?>
        </ul>
    </div>

    <!-- Review pane -->
    <div class="min-w-0 bg-[#fafbf7]">
        <?php if (! $sel): ?>
            <div class="empty-state h-full"><i class="bi bi-person-check"></i><p class="font-semibold text-ink">Select a student</p><p>Students appear here once the registrar approves their tuition receipt.</p></div>
        <?php else:
            $locked   = $sel['clearance_status'] !== 'in_progress';
            $signed   = ! empty($sel['signed_at']);
            $pendingReqs = count(array_filter($requirements, static fn ($x) => $x['status'] === 'pending'));
            $canDecide = ! $locked && ($sel['status'] === 'PENDING' || $sel['status'] === 'INC' || ($sel['status'] === 'PASSED' && ! $signed));
        ?>
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line bg-paper px-6 py-5">
                <div>
                    <div class="eyebrow">Reviewing student</div>
                    <h3 class="mt-1 text-xl font-bold"><?= esc(person_name($sel)) ?></h3>
                    <div class="text-sm text-ink-muted"><?= esc($sel['student_number']) ?> · <?= esc($sel['program_code']) ?> · <?= year_level_label($sel['year_level']) ?> · <?= esc($sel['reference_no']) ?></div>
                </div>
                <span class="inline-flex items-center gap-2 rounded-lg bg-[#e2edf7] px-3 py-1.5 text-sm font-semibold text-[#285f94]"><i class="bi bi-person" aria-hidden="true"></i>Assigned to you</span>
            </div>

            <div class="flex flex-col gap-5 p-6">
                <!-- Subject summary -->
                <section class="surface p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#e2edf7] font-bold text-[#285f94]"><?= esc(preg_replace('/\d+$/', '', $sel['subject_code'])) ?></span>
                        <div class="flex-1">
                            <div class="font-bold"><?= esc($sel['subject_title']) ?> · <?= esc($sel['subject_code']) ?></div>
                            <div class="text-sm text-ink-muted">Current clearance status for this subject</div>
                        </div>
                        <?= $sel['status'] === 'PASSED' && ! $signed ? status_badge('AWAITING_SIGNATURE') : status_badge(subject_status_key($sel)) ?>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="surface-muted p-3"><div class="kv-label">Final grade</div><div class="kv-value"><?= esc($sel['final_grade'] ?: '—') ?></div></div>
                        <div class="surface-muted p-3"><div class="kv-label">Decided</div><div class="kv-value"><?= fmt_date($sel['decided_at']) ?></div></div>
                        <div class="surface-muted p-3"><div class="kv-label">Signature</div><div class="kv-value"><?= $signed ? 'Signed ' . fmt_date($sel['signed_at']) : 'Not signed' ?></div></div>
                    </div>
                    <?php if ($sel['remarks']): ?><p class="mb-0 mt-3 text-sm text-ink-soft"><span class="font-semibold">Remarks:</span> <?= esc($sel['remarks']) ?></p><?php endif; ?>
                </section>

                <?php if ($locked): ?>
                    <div class="flex items-start gap-3 rounded-xl border border-line bg-paper p-4 text-sm text-ink-soft"><i class="bi bi-lock-fill mt-0.5" aria-hidden="true"></i>This clearance is <?= $sel['clearance_status'] === 'completed' ? 'completed and locked' : 'not active yet' ?>. No further changes can be made.</div>
                <?php elseif ($sel['status'] === 'PASSED' && ! $signed): ?>
                    <section class="surface flex flex-wrap items-center justify-between gap-3 p-5">
                        <div><div class="font-bold">Apply your digital signature</div><div class="text-sm text-ink-muted">The subject is PASSED but not yet signed, so it does not count toward clearance.</div></div>
                        <form action="<?= site_url('teacher/clearance/' . $sel['id'] . '/sign') ?>" method="post" data-confirm="Sign the PASSED approval for <?= esc(person_name($sel), 'attr') ?>? Signed approvals cannot be changed." data-confirm-title="Sign approval" data-confirm-button="Sign approval">
                            <?= csrf_field() ?><button class="btn btn-primary" type="submit"><i class="bi bi-pen"></i>Sign approval</button>
                        </form>
                    </section>
                <?php elseif (in_array($sel['status'], ['PASSED', 'FAILED'], true)): ?>
                    <div class="flex items-start gap-3 rounded-xl border border-line bg-paper p-4 text-sm text-ink-soft"><i class="bi bi-lock-fill mt-0.5" aria-hidden="true"></i>
                        <?= $sel['status'] === 'PASSED' ? 'Signed approvals are final.' : 'This subject is FAILED and is in the re-enrollment process with the registrar (' . esc(status_meta($sel['reenrollment_status'] ?? 'RE_ENROLLMENT_REQUIRED')['label']) . ').' ?>
                    </div>
                <?php endif; ?>

                <?php if ($canDecide && ! ($sel['status'] === 'PASSED')): ?>
                    <!-- Decision form -->
                    <section class="surface p-5">
                        <form action="<?= site_url('teacher/clearance/' . $sel['id'] . '/decision') ?>" method="post" data-decision-form data-confirm-title="Confirm decision">
                            <?= csrf_field() ?>
                            <div class="flex items-start justify-between">
                                <div><div class="eyebrow">Your decision</div><p class="mt-1 text-sm text-ink-muted">This outcome will appear on the student's clearance.</p></div>
                                <span class="text-xs font-semibold text-brand-700">Required</span>
                            </div>
                            <fieldset class="mt-4">
                                <legend class="visually-hidden">Outcome</legend>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <?php foreach ([
                                        'PASSED' => ['PASSED', 'All requirements met', $pendingReqs > 0],
                                        'INC'    => ['INC · Incomplete', 'Needs follow-up', false],
                                        'FAILED' => ['FAILED', 'Re-enrollment path', false],
                                    ] as $value => [$label, $hint, $disabled]): ?>
                                        <label class="decision-card <?= $disabled ? 'pointer-events-none opacity-50' : '' ?>" data-decision-card="<?= $value ?>">
                                            <input type="radio" name="decision" value="<?= $value ?>" class="visually-hidden" <?= $disabled ? 'disabled' : '' ?> <?= old('decision') === $value ? 'checked' : '' ?>>
                                            <span class="block text-sm font-bold"><?= $label ?></span>
                                            <span class="block text-xs text-ink-muted"><?= $disabled ? "Verify {$pendingReqs} pending requirement(s) first" : $hint ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </fieldset>

                            <div class="mt-4 hidden" data-show-for="INC">
                                <span class="form-label d-block">Missing requirement(s)</span>
                                <div class="flex flex-col gap-2" data-requirement-list>
                                    <?php if ($pendingReqs === 0): ?>
                                        <input type="text" name="requirements[]" maxlength="255" class="form-control" placeholder="e.g. Final laboratory activity" aria-label="Missing requirement">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="mt-2 bg-transparent p-0 text-sm font-semibold text-brand-700 hover:underline" data-add-requirement>+ Add requirement</button>
                                <?php if ($pendingReqs > 0): ?><div class="form-text"><?= $pendingReqs ?> pending requirement(s) already on record.</div><?php endif; ?>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_140px]">
                                <div>
                                    <label class="form-label" for="remarks"><i class="bi bi-chat-left-text me-1" aria-hidden="true"></i>Remarks for student <span class="font-normal text-ink-muted">(optional)</span></label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3" maxlength="1000" placeholder="Add context the student should know…"><?= esc(old('remarks', $sel['remarks'] ?? '')) ?></textarea>
                                </div>
                                <div>
                                    <label class="form-label" for="final_grade">Final grade</label>
                                    <input class="form-control" id="final_grade" name="final_grade" maxlength="10" value="<?= esc(old('final_grade', $sel['final_grade'] ?? '')) ?>" placeholder="e.g. 1.75">
                                </div>
                            </div>

                            <div class="mt-4 flex hidden items-center gap-4 rounded-xl border border-[#cde5d4] bg-[#f1f8f3] p-3" data-show-for="PASSED">
                                <img src="<?= site_url('files/signature/' . $authProfile['id']) ?>?v=<?= md5((string) $authProfile['signature_path']) ?>" alt="Your e-signature" class="h-12 max-w-[170px] rounded bg-white object-contain p-1">
                                <p class="mb-0 text-sm text-ink-soft"><i class="bi bi-patch-check-fill text-[#1f6a3f]" aria-hidden="true"></i> Your e-signature will be added to this student's clearance automatically when you save.</p>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                                <span class="text-xs text-ink-muted">You are signing as <?= esc(person_name($authUser)) ?></span>
                                <button type="submit" class="btn btn-primary" data-decision-submit disabled><i class="bi bi-pen" aria-hidden="true"></i><span>Save decision</span></button>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>

                <?php if ($sel['status'] === 'INC' || $requirements !== []): ?>
                    <!-- Missing requirements -->
                    <section class="surface p-5">
                        <div class="eyebrow">Missing requirements</div>
                        <p class="mt-1 text-sm text-ink-muted">Visible to the student while the subject is INC. Verify each one after the student completes it with you in person.</p>
                        <ul class="mt-4 flex list-none flex-col gap-2 p-0">
                            <?php foreach ($requirements as $req): ?>
                                <li class="rounded-xl border <?= $req['status'] === 'completed' ? 'border-[#cde5d4] bg-[#f1f8f3]' : 'border-[#f0dcc0] bg-[#fdf6ec]' ?> px-4 py-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="font-semibold"><?= esc($req['description']) ?></div>
                                            <div class="text-xs text-ink-muted">Added <?= fmt_date($req['created_at']) ?><?= $req['due_date'] ? ' · Due ' . fmt_date($req['due_date']) : '' ?><?= $req['verified_at'] ? ' · Verified ' . fmt_datetime($req['verified_at']) : '' ?></div>
                                            <?php if ($req['student_reported_at'] && $req['status'] === 'pending'): ?>
                                                <div class="mt-1 text-sm text-[#285f94]"><i class="bi bi-send-check" aria-hidden="true"></i> Student reported completion <?= time_ago($req['student_reported_at']) ?><?= $req['student_note'] ? ': “' . esc($req['student_note']) . '”' : '' ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?= status_badge($req['status'] === 'completed' ? 'verified' : ($req['student_reported_at'] ? 'reported' : 'pending')) ?>
                                    </div>
                                    <?php if ($req['status'] === 'pending' && ! $locked && $sel['status'] === 'INC'): ?>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <form action="<?= site_url('teacher/inc/' . $req['id'] . '/verify') ?>" method="post" data-confirm="Confirm that <?= esc(person_name($sel), 'attr') ?> completed “<?= esc($req['description'], 'attr') ?>” with you?" data-confirm-title="Verify requirement" data-confirm-button="Verify">
                                                <?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2-circle"></i>Verify completed</button>
                                            </form>
                                            <?php if ($req['student_reported_at']): ?>
                                                <button class="btn btn-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#return<?= $req['id'] ?>" aria-expanded="false">Not yet complete</button>
                                            <?php endif; ?>
                                            <?php if (count($requirements) > 1): ?>
                                                <form action="<?= site_url('teacher/inc/' . $req['id'] . '/delete') ?>" method="post" data-confirm="Remove this requirement?" data-confirm-variant="danger" data-confirm-button="Remove">
                                                    <?= csrf_field() ?><button class="btn btn-light btn-sm text-[#a3362a]" type="submit" aria-label="Remove requirement"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($req['student_reported_at']): ?>
                                            <form action="<?= site_url('teacher/inc/' . $req['id'] . '/return') ?>" method="post" class="collapse mt-2" id="return<?= $req['id'] ?>">
                                                <?= csrf_field() ?>
                                                <div class="flex gap-2"><input class="form-control form-control-sm" name="notes" maxlength="255" required placeholder="Tell the student what is still missing" aria-label="Note for the student"><button class="btn btn-soft btn-sm" type="submit">Send</button></div>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($sel['status'] === 'INC' && ! $locked): ?>
                            <button type="button" class="mt-3 bg-transparent p-0 text-sm font-semibold text-brand-700 hover:underline" data-bs-toggle="modal" data-bs-target="#addRequirementModal">+ Add requirement</button>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <section class="rounded-2xl border border-[#cde5d4] bg-[#e7f3ea] p-5">
                    <i class="bi bi-shield-check text-xl text-brand-700" aria-hidden="true"></i>
                    <div class="mt-2 font-bold">Your signature is protected</div>
                    <p class="mb-0 text-sm text-ink-soft">Only you can change decisions for <?= esc($o['code']) ?> students assigned to your account. Every change is recorded in the audit trail.</p>
                </section>

                <?php if ($timeline !== []): ?>
                    <section class="surface p-5">
                        <div class="eyebrow">Activity</div>
                        <ol class="mb-0 mt-3 flex list-none flex-col gap-3 p-0">
                            <?php foreach ($timeline as $t): ?>
                                <li class="flex gap-3 text-sm">
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-400" aria-hidden="true"></span>
                                    <span><span class="font-semibold"><?= esc($t['description'] ?: $t['action']) ?></span><br><span class="text-xs text-ink-muted"><?= esc(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? '')) ?: 'System') ?> · <?= fmt_datetime($t['created_at']) ?></span></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endif; ?>
            </div>

            <?php if ($sel['status'] === 'INC' && ! $locked): ?>
                <div class="modal fade" id="addRequirementModal" tabindex="-1" aria-labelledby="addReqTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <form class="modal-content" action="<?= site_url('teacher/clearance/' . $sel['id'] . '/requirements') ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="modal-header"><h2 class="modal-title" id="addReqTitle">Add missing requirement</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                            <div class="modal-body flex flex-col gap-3">
                                <div><label class="form-label" for="reqDesc">Requirement</label><input class="form-control" id="reqDesc" name="description" maxlength="255" required placeholder="e.g. Final laboratory activity"></div>
                                <div><label class="form-label" for="reqInstr">Instructions</label><textarea class="form-control" id="reqInstr" name="instructions" rows="2">Complete the missing requirement personally with your subject teacher.</textarea></div>
                                <div><label class="form-label" for="reqDue">Due date (optional)</label><input class="form-control" id="reqDue" name="due_date" type="date" min="<?= date('Y-m-d') ?>"></div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Add requirement</button></div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
