<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class Notifications extends BaseController
{
    public function index(): string
    {
        $model = model(NotificationModel::class);
        $items = $model->where('user_id', $this->auth->id())->orderBy('id', 'DESC')->paginate(20);

        return $this->page('shared/notifications', [
            'title'    => 'Notifications',
            'eyebrow'  => 'Inbox',
            'subtitle' => 'Updates about receipts, subjects and your clearance.',
            'items'    => $items,
            'pager'    => $model->pager,
            'unread'   => $model->unreadCount((int) $this->auth->id()),
        ]);
    }

    public function open(int $id)
    {
        $model = model(NotificationModel::class);
        $n     = $model->where('user_id', $this->auth->id())->find($id);
        if (! $n) {
            $this->notFound();
        }
        if (! $n['read_at']) {
            $model->update($id, ['read_at' => date('Y-m-d H:i:s')]);
        }

        // Links are stored as site-relative paths; never follow absolute URLs.
        $link = (string) $n['link'];
        if ($link === '' || preg_match('#^[a-z]+:|^//#i', $link)) {
            return redirect()->to(site_url('notifications'));
        }

        return redirect()->to(site_url($link));
    }

    public function readAll()
    {
        model(NotificationModel::class)->where('user_id', $this->auth->id())->where('read_at', null)->set('read_at', date('Y-m-d H:i:s'))->update();

        return redirect()->back()->with('toast_success', 'All notifications marked as read.');
    }
}
