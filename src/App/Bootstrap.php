<?php
namespace App;

use PDO;
use Slim\App;
use Slim\Csrf;
use Slim\Flash;
use Slim\Views;
use Monolog;
use Illuminate\Database;
use Respect\Validation\Validator;

class Bootstrap
{

    protected $app;

    protected $container;

    public function __construct()
    {
        $this->main();
    }

    public function cli()
    {
        die('Slim Skeleton');
    }

    public function process($request, $response)
    {
        return $this->app->process($request, $response);
    }

    public function run()
    {
        $this->app->run();
    }

    private function install()
    {
        $file = __DIR__ . '/../../var/databases/app.sqlite';

        if (! file_exists($file)) {
            $db = new PDO(sprintf('sqlite:%s', $file));
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = 'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, ';
            $sql .= 'first_name VARCHAR(255), ';
            $sql .= 'last_name VARCHAR(255), ';
            $sql .= 'email VARCHAR(255), ';
            $sql .= 'password VARCHAR(2000), ';
            $sql .= 'updated_at DATETIME, ';
            $sql .= 'created_at DATETIME)';
            $db->exec($sql);
        }
    }

    private function main()
    {
        // Start Session
        $this->session();

        // Install
        $this->install();

        // Instantiate the app
        $this->app = new App($this->settings());

        // Set up dependencies
        $this->dependencies();

        // Register middleware
        $this->middleware();

        // Register routes
        $this->routes();
    }

    private function dependencies()
    {
        // DIC configuration
        $this->container = $this->app->getContainer();

        $capsule = new Database\Capsule\Manager();
        $capsule->addConnection($this->container['settings']['db']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Validator::with('App\\Validation\\Rules');

        // database
        $this->container['db'] = function ($container) use ($capsule) {
            return $capsule;
        };

        // auth
        $this->container['auth'] = function ($container) {
            return new Auth\Auth();
        };

        // csrf
        $this->container['csrf'] = function ($container) {
            return new Csrf\Guard();
        };

        // flash
        $this->container['flash'] = function ($container) {
            return new Flash\Messages();
        };

        // validator
        $this->container['validator'] = function ($container) {
            return new Validation\Validator();
        };

        // php renderer
        $this->container['renderer'] = function ($container) {
            $settings = $container->get('settings')['renderer'];
            return new Views\PhpRenderer($settings['template_path']);
        };

        // monolog
        $this->container['logger'] = function ($container) {
            $settings = $container->get('settings')['logger'];
            $logger = new Monolog\Logger($settings['name']);
            $logger->pushProcessor(new Monolog\Processor\UidProcessor());
            $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
            return $logger;
        };

        // twig renderer
        $this->container['view'] = function ($container) {
            $settings = $container->get('settings')['twig'];
            $view = new Views\Twig($settings['template_path'], [
                'cache' => $settings['cache'],
                'charset' => $settings['charset']
            ]);
            $view->addExtension(new Views\TwigExtension($container->router, $container->request->getUri()));
            $view->getEnvironment()->addGlobal('auth', [
                'check' => $container->auth->check(),
                'user' => $container->auth->user()
            ]);
            $view->getEnvironment()->addGlobal('flash', $container->flash);
            return $view;
        };

        // auth controller
        $this->container['AuthController'] = function ($container) {
            return new Controllers\AuthController($container);
        };

        // home controller
        $this->container['HomeController'] = function ($container) {
            return new Controllers\HomeController($container);
        };

        // password controller
        $this->container['PasswordController'] = function ($container) {
            return new Controllers\PasswordController($container);
        };

        // not found handler
        $this->container['notFoundHandler'] = function ($container) {
            return function ($request, $response) use ($container) {
                return $container->view->render($response, 'errors/error.twig', [
                    'code' => '404',
                    'error' => 'Not Found'
                ])->withStatus(404);
            };
        };

        // not allowed handler
        $this->container['notAllowedHandler'] = function ($container) {
            return function ($request, $response, $methods) use ($container) {
                return $container->view->render($response, 'errors/error.twig', [
                    'code' => '405',
                    'error' => 'Method Not Allowed'
                ])
                    ->withHeader('Allow', implode(', ', $methods))
                    ->withStatus(405);
            };
        };

        // php error handler
        $this->container['phpErrorHandler'] = function ($container) {
            return function ($request, $response, $error) use ($container) {
                return $container->view->render($response, 'errors/error.twig', [
                    'code' => '500',
                    'error' => 'Internal Server Error'
                ])->withStatus(500);
            };
        };
    }

    private function middleware()
    {
        // Application middleware
        $this->app->add(new Middleware\CsrfViewMiddleware($this->container));
        $this->app->add(new Middleware\ValuesMiddleware($this->container));
        $this->app->add(new Middleware\ValidationErrorsMiddleware($this->container));
        if (PHP_SAPI != 'cli') {
            $this->app->add($this->container->csrf);
        }
    }

    private function routes()
    {
        // Routes
        $this->app->get('/', 'HomeController:index')->setName('home');

        $this->app->group('', function () {
            $this->group('/auth', function () {
                $this->get('/password/change[/]', 'PasswordController:getChangePassword')
                    ->setName('auth.password.change');
                $this->get('/signout[/]', 'AuthController:getSignOut')
                    ->setName('auth.signout');
                $this->post('/password/change[/]', 'PasswordController:postChangePassword');
            });
        })
            ->add(new Middleware\AuthMiddleware($this->container));

        $this->app->group('', function () {
            $this->group('/auth', function () {
                $this->get('/signin[/]', 'AuthController:getSignIn')
                    ->setName('auth.signin');
                $this->get('/signup[/]', 'AuthController:getSignUp')
                    ->setName('auth.signup');
                $this->post('/signup[/]', 'AuthController:postSignUp');
                $this->post('/signin[/]', 'AuthController:postSignIn');
            });
        })
            ->add(new Middleware\GuestMiddleware($this->container));
    }

    private function session()
    {
        if (PHP_SAPI != 'cli') {
            $settings = $this->settings()['settings']['session'];

            if (is_dir($settings['save_path']) && is_writeable($settings['save_path'])) {
                session_save_path($settings['save_path']);
            }

            if (isset($settings['name']) && ! empty($settings['name'])) {
                session_name($settings['name']);
            }

            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }
    }

    private function settings()
    {
        return [
            'settings' => [
                'displayErrorDetails' => true, // set to false in production
                'addContentLengthHeader' => false, // Allow the web server to send the content-length header

                // Database settings
                'db' => [
                    'driver' => 'sqlite',
                    'database' => __DIR__ . '/../../var/databases/app.sqlite',
                    'prefix' => ''
                ],

                // Renderer settings
                'renderer' => [
                    'template_path' => __DIR__ . '/../../templates/'
                ],

                // Session settings
                'session' => [
                    'name' => 'session',
                    'save_path' => __DIR__ . '/../../var/sessions/'
                ],

                // Monolog settings
                'logger' => [
                    'level' => Monolog\Logger::DEBUG,
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../../logs/app.log'
                ],

                // Twig settings
                'twig' => [
                    'cache' => false,
                    'charset' => 'utf8',
                    'template_path' => __DIR__ . '/../../templates/'
                ]
            ]
        ];
    }
}
