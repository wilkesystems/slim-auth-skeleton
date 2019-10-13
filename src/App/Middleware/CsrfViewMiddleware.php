<?php
namespace App\Middleware;

class CsrfViewMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        $this->container->view->getEnvironment()->addGlobal('csrf', [
            'name_key' => $this->container->csrf->getTokenNameKey(),
            'name' => $this->container->csrf->getTokenName(),
            'value_key' => $this->container->csrf->getTokenValueKey(),
            'value' => $this->container->csrf->getTokenValue()
        ]);

        $response = $next($request, $response);
        return $response;
    }
}
