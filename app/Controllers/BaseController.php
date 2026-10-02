<?php

namespace App\Controllers;

use App\Libraries\Auth;
use App\Services\WorkflowException;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected Auth $auth;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = service('auth');
    }

    /**
     * Render a page inside the application shell.
     */
    protected function page(string $view, array $data = []): string
    {
        $user = $this->auth->user();

        return view($view, $data + [
            'authUser'    => $user,
            'authRole'    => $user['role'] ?? null,
            'authProfile' => $this->auth->profile(),
        ]);
    }

    protected function notFound(string $message = 'The page you requested could not be found.'): never
    {
        throw PageNotFoundException::forPageNotFound($message);
    }

    /**
     * Run a workflow action and turn the result into a redirect with a toast.
     */
    protected function act(callable $action, string $success, ?string $redirectTo = null): RedirectResponse
    {
        try {
            $result = $action();
            $message = is_string($result) && $result !== '' ? $result : $success;
            $redirect = $redirectTo ? redirect()->to(site_url($redirectTo)) : redirect()->back();

            return $redirect->with('toast_success', $message);
        } catch (WorkflowException $e) {
            return redirect()->back()->withInput()->with('toast_error', $e->getMessage());
        }
    }

    protected function postBool(string $key): bool
    {
        return in_array($this->request->getPost($key), ['1', 'on', 'true', 'yes'], true);
    }
}
