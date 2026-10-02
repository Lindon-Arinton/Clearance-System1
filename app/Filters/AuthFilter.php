<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Requires a signed-in, active user. Falls back to the remember-me cookie.
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $auth = service('auth');

        if (! $auth->check() && ! $auth->loginFromRememberCookie()) {
            if (strtolower($request->getMethod()) === 'get') {
                $query = $request->getUri()->getQuery();
                session()->set('intended_url', current_url() . ($query !== '' ? '?' . $query : ''));
            }

            return redirect()->to(site_url('login'))->with('toast_error', 'Please sign in to continue.');
        }

        // Accounts created or reset by an admin must set their own password first.
        $user = $auth->user();
        if ((int) $user['must_change_password'] === 1) {
            $path = trim($request->getUri()->getPath(), '/');
            if (! preg_match('#(^|/)(profile|profile/password|logout)$#', $path)) {
                return redirect()->to(site_url('profile'))->with('toast_error', 'Please set a new password before continuing.');
            }

            return null;
        }

        // Teachers must upload their e-signature before they can clear anyone.
        if ($user['role'] === 'teacher' && empty($auth->profile()['signature_path'])) {
            $path = trim($request->getUri()->getPath(), '/');
            if (! preg_match('#(^|/)(profile|profile/signature|profile/password|logout|notifications.*|files/signature/\d+)$#', $path)) {
                return redirect()->to(site_url('profile#signature'))->with('toast_error', 'Upload your e-signature to start clearing students.');
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Authenticated pages must never be served from a shared/browser cache.
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');

        return $response;
    }
}
