<?php

namespace App\Controllers;

use App\Libraries\Auth;

class Home extends BaseController
{
    public function index()
    {
        if ($this->auth->check() || $this->auth->loginFromRememberCookie()) {
            return redirect()->to(site_url(Auth::homeFor($this->auth->role())));
        }

        return redirect()->to(site_url('login'));
    }
}
