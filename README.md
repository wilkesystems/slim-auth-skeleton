# Slim Framework 3 Skeleton Application with authentication

Use this skeleton application to quickly setup and start working on a new Slim Framework 3 application. This application uses the latest Slim 3 with the PHP-View template renderer. It also uses the Monolog logger.

This skeleton application was built for Composer. This makes setting up a new Slim Framework application quick and easy.

## Install the Application

Install Composer

    curl -sS https://getcomposer.org/installer | /usr/bin/php
    mv composer.phar /usr/bin/composer
    chmod +x /usr/bin/composer

Run this command from the directory in which you want to install your new Slim Framework application.

    composer create-project wilkesystems/slim-auth-skeleton [app]

Replace `[app]` with the desired directory name for your new application. You'll want to:

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

To run the application in development, you can run these commands 

    cd [app]
    composer start

Run this command in the application directory to run the test suite

    composer test

That's it! Now go build something cool.
