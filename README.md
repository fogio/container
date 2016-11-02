Fogio Container
===============

Dependency Injection Container; IoC - Inverse of Control; fast; simple; 
no auto injection; static, dynamic, lazy services definition


Instalation
-----------

```
composer require fogio/container
````

Usage
-----

Static definitions
```php
<?php

use Fogio\Container;

class App extends Container
{
    protected function _db() // service name is prefixed with `_`
    {
        return $this->db = new Pdo('mysql:host=localhost;dbname=test'); // shared, injection
    }

    protected function _mailer()
    {
        return Mailer::class; // shared, setDefaultShared(true) by default
    }

    protected function _newsletter()
    {
        return new (Newsletter()) // non shared 
            ->setMailer($this->mailer) // injection
            ->setDb($this->db)
    }
}

$app = new App();
$app->newsletter->send();
```


Dynamic definitions by `__invoke`
```php
<?php

use Fogio\Container;

$app = new Container();
$app([
    'newsletter' => function ($c) { 
        return $c->db = new Pdo(mysql:host=localhost;dbname=test); // shared
    },
    'mailer' => Mailer::class, // shared
    'newsletter' => function ($c) { 
        return new (Newsletter()) // non shared 
            ->setMailer($c->mailer) // injection
            ->setDb($c->db)
    },
]);
$app->newsletter->send();
```


Dynamic has higher priority
```php
<?php

use Fogio\Container;

class App extends Container
{
    protected function _newsletter()
    {
        return NewsletterA::class;
    }
}

$app = new App();
$app([
    'newsletter' => function ($c) { 
        return NewsletterB::class; // shared
    },
]);
echo get_class($app->newsletter); // NewsletterB
```


Using trait
```php
<?php

use Fogio\ContainerTrait;

class App 
{
    use ContainerTrait;
}
```


Extending each service in container using `_factory`
```php
<?php

use Fogio\Container;

$validators = new Container();
$validators([
    'notEmpty' => NotEmpty::class,
    'email'    => Email::class,
    '_factory' => function($service, $name, $container) {
        if ($service instanceof TranslatorAwareInterface) {
            $service->setTranslator(new Translator());
        }

        return $service;
    }
]);

```


`_factory` is called even if service is not defined
```php
<?php

use Fogio\Container;

$services = new Container();
$services([
    '_factory' => function($service, $name, $container) {
        if ($service == null) {
            $service = new DefaultService();
        }

        return $service;
    }
]);

```


Lazy dynamic services definition using `_init`, no proxy manager needed
```php
<?php

use Fogio\Container;

class Validators extends Container implements LostInToughtInterface
{
    protected function __init()
    {
        configure($this);
    }
}

$validators = new Validators();
// $validators instanceof LostInToughtInterface === true
// services are not defined yet

function configure($container) {
    $container([
        'notEmpty' => NotEmpty::class,
        'email'    => Email::class,
    ]);
}

// getting service from container  
$emailValidator = $validators->email;

// or checking if service is defined
isset($validators->email);

// calls the `_init` feature and configure the container

```

`_init` and `factory` are reserved services names


Simple auto-injection mechanism by interfaces
```php
<?php

use Fogio\Container;

class App extends Container
{
    protected function _db()
    {
        return $this->db = new Pdo('mysql:host=localhost;dbname=test');
    }

    protected function _mailer()
    {
        return Mailer::class;
    }

    protected function _news()
    {
        return News::class;
    }

    protected function _newsletter()
    {
        return Newsletter::class;
    }

    protected function __factory($service, $name)
    {
        foreach (
            [
                DbAwareInterface => ['setDb' => 'db'],
                MailerAwareINterface => ['setMailer' => 'mailer'],
            ] 
            as $interface => $injections
        ) {
            foreach ($injections as $method => $dependence) {
                $service->{$method}($this->$dependence);
            }
        }

    }
}

class News implements DbAwareInterface {}
class Newsletter implements DbAwareInterface, MailerAwareInterface {}

$app = new App();
$newsletter = $app->newsletter;
```