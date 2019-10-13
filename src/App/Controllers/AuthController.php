<?php
namespace App\Controllers;

use App\Models\User;
use Respect\Validation\Validator;

class AuthController extends Controller
{

    public function __construct($container)
    {
        parent::__construct($container);
    }

    public function getSignIn($request, $response)
    {
        return $this->view->render($response, 'auth/signin.twig');
    }

    public function getSignOut($request, $response)
    {
        $this->auth->logout();

        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function getSignUp($request, $response)
    {
        return $this->view->render($response, 'auth/signup.twig');
    }

    public function postSignIn($request, $response)
    {
        $auth = $this->auth->attempt($request->getParam('email'), $request->getParam('password'));

        if (! $auth) {
            $this->flash->addMessage('danger', 'Could not sign you in with those details.');
            return $response->withRedirect($this->router->pathFor('auth.signin'));
        }

        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function postSignUp($request, $response)
    {
        $validation = $this->validator->validate($request, [
            'first_name' => Validator::notEmpty(),
            'last_name' => Validator::notEmpty(),
            'email' => Validator::noWhitespace()->notEmpty()
                ->email()
                ->emailAvailable(),
            'password' => Validator::noWhitespace()->notEmpty()
        ]);

        if ($validation->failed()) {
            $this->flash->addMessage('danger', 'Please verify your data.');
            return $response->withRedirect($this->router->pathFor('auth.signup'));
        }

        $user = User::create([
            'first_name' => $request->getParam('first_name'),
            'last_name' => $request->getParam('last_name'),
            'email' => $request->getParam('email'),
            'email_verified' => 0,
            'password' => password_hash($request->getParam('password'), PASSWORD_DEFAULT)
        ]);

        $this->flash->addMessage('info', 'You have been signed up!');

        $this->auth->attempt($user->email, $request->getParam('password'));

        return $response->withRedirect($this->router->pathFor('home'));
    }
}
