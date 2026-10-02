<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<?php foreach ($grouped as $label => $list): ?>
    <section class="surface mb-6 overflow-hidden">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="section-title"><?= esc($label) ?></h2>
            <?= (int) $list[0]['is_current'] === 1 ? status_badge('open', 'Current term') : status_badge('closed', 'Past term') ?>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-hover">
                <thead><tr><th class="ps-5">Subject</th><th>Section</th><th class="text-center">Students</th><th class="text-center">Passed</th><th class="text-center">INC</th><th class="text-center">Failed</th><th class="text-center">Pending</th><th class="pe-5"></th></tr></thead>
                <tbody>
                <?php foreach ($list as $o): ?>
                    <tr>
                        <td class="ps-5"><div class="font-semibold"><?= esc($o['title']) ?></div><div class="text-xs text-ink-muted"><?= esc($o['code']) ?> · <?= rtrim(rtrim($o['units'], '0'), '.') ?> units</div></td>
                        <td><?= esc($o['section']) ?><div class="text-xs text-ink-muted"><?= esc($o['schedule'] ?? '') ?></div></td>
                        <td class="text-center font-semibold"><?= (int) $o['students'] ?></td>
                        <td class="text-center text-[#1f6a3f]"><?= (int) $o['passed'] ?></td>
                        <td class="text-center text-[#a4521a]"><?= (int) $o['inc'] ?></td>
                        <td class="text-center text-[#a3362a]"><?= (int) $o['failed'] ?></td>
                        <td class="text-center"><?= (int) $o['pending'] + (int) $o['awaiting_sign'] ?></td>
                        <td class="pe-5 text-end"><a href="<?= site_url('teacher/subjects/' . $o['id']) ?>" class="btn btn-light btn-sm">Review <i class="bi bi-arrow-right"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endforeach; ?>
<?php if ($grouped === []): ?><div class="surface empty-state"><i class="bi bi-journal-x"></i>No subjects have been assigned to you yet.</div><?php endif; ?>
<?= $this->endSection() ?>
