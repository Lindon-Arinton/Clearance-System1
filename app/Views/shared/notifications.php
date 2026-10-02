<?= $this->extend('layouts/app') ?>

<?= $this->section('headerActions') ?>
<?php if ($unread > 0): ?>
    <form action="<?= site_url('notifications/read-all') ?>" method="post"><?= csrf_field() ?>
        <button class="btn btn-soft h-10 px-3 text-sm" type="submit"><i class="bi bi-check2-all"></i>Mark all read</button>
    </form>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="surface overflow-hidden">
    <ul class="m-0 list-none p-0">
        <?php foreach ($items as $n): ?>
            <li>
                <a href="<?= site_url('notifications/' . $n['id'] . '/open') ?>" class="flex items-start gap-4 border-b border-line px-5 py-4 text-ink transition hover:bg-brand-50/60 <?= $n['read_at'] ? '' : 'bg-brand-50/50' ?>">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-paper shadow-card"><i class="bi <?= notification_icon($n['type']) ?>" aria-hidden="true"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold"><?= esc($n['title']) ?></span>
                            <?php if (! $n['read_at']): ?><span class="rounded-full bg-brand-700 px-2 py-0.5 text-[10px] font-bold uppercase text-white">New</span><?php endif; ?>
                        </span>
                        <span class="mt-0.5 block text-sm text-ink-soft"><?= esc($n['message']) ?></span>
                        <span class="mt-1 block text-xs text-ink-muted"><?= fmt_datetime($n['created_at']) ?> · <?= time_ago($n['created_at']) ?></span>
                    </span>
                    <i class="bi bi-chevron-right mt-2 text-ink-faint" aria-hidden="true"></i>
                </a>
            </li>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <li class="empty-state"><i class="bi bi-bell-slash"></i>No notifications yet.</li>
        <?php endif; ?>
    </ul>
</section>
<div class="mt-4"><?= $pager->links('default', 'default_full') ?></div>
<?= $this->endSection() ?>
