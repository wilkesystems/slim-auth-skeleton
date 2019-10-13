<?php
namespace App\Controllers;

use Respect\Validation\Validator;

class PasswordController extends Controller
{

    public function __construct($container)
    {
        parent::__construct($container);
    }

    public function getChangePassword($request, $response)
    {
        return $this->view->render($response, 'auth/password/change.twig');
    }

    public function postChangePassword($request, $response)
    {
        $validation = $this->validator->validate($request, [
            'current_password' => Validator::noWhitespace()->notEmpty()
                ->matchesPassword($this->auth->user()->password),
            'password' => Validator::noWhitespace()->notEmpty()
        ]);
        
        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('auth.password.change'));
        }
        
        $this->auth->user()->setPassword($request->getParam('password'));

        $this->flash->addMessage('info', 'Your password was changed.');
        return $response->withRedirect($this->router->pathFor('home'));
    }
}
