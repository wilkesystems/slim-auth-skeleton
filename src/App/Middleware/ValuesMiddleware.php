<?php
namespace App\Middleware;

class ValuesMiddleware extends Middleware
{

    public function __invoke($request, $response, $next)
    {
        if (isset($_SESSION['values'])) {
            $this->container->view->getEnvironment()->addGlobal('values', $_SESSION['values']);
        }
        if (PHP_SAPI != 'cli') {
            $_SESSION['values'] = $request->getParams();
        }
        $response = $next($request, $response);
        return $response;
    }
}
