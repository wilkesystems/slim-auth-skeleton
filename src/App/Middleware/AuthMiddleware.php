<?php
namespace App\Middleware;

class AuthMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        if (! $this->container->auth->check()) {
            $this->container->flash->addMessage('danger', 'Please sign in before that.');
            return $response->withRedirect($this->container->router->pathFor('auth.signin'));
        }

        $response = $next($request, $response);
        return $response;
    }
}
