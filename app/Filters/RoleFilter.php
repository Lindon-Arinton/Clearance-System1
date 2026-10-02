<?php

namespace App\Filters;

use App\Services\AuditLogger;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Usage: 'role:admin' or 'role:teacher,admin'. Must run after 'auth'.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $role = service('auth')->role();

        if ($role === null || ! in_array($role, (array) $arguments, true)) {
            AuditLogger::log('auth.forbidden', 'route', null, null, null, 'Blocked access to ' . $request->getUri()->getPath());

            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/forbidden'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
