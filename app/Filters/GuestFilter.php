<?php

namespace App\Filters;

use App\Libraries\Auth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Keeps signed-in users away from the login screen.
 */
class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = service('auth');

        if ($auth->check() || $auth->loginFromRememberCookie()) {
            return redirect()->to(site_url(Auth::homeFor($auth->role())));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
