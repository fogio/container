Fogio Container
===============

Dependency Injection Container, IoC - Inverse of Control, simple, fast,
no auto injection, static or dynamic services definition, lazy dynamic services definitions


Instalation
-----------

```
composer require fogio/container
````

Usage
-----

```php
<?php

use Fogio\Container;

class App extends Container // static definitions
{
    protected function _config()
    {
        return (object)[ // non shared service definition
            'db' => 'mysql:host=localhost;dbname=test',
        ];
    }

    protected function _db()
    {
        return $this->db = new Pdo($this->config->db); // shared definition, injection
    }
}

$app = new App();
$app([ // dynamic definition, dynamic has higher priority
    'mailer' => Mailer::class, // shared, defult is setDefaultShared(true)
    'newsletter' => function ($container) { 
        return new (Newsletter()) // non shared 
            ->setMailer($container->mailer) // injection
            ->setDb($container->db)
    },
]);
$app->newsletter->send();
```

### Using trait

```php
<?php

use Fogio\ContainerTrait;

class App 
{
    use ContainerTrait;
}
```

### Extending each service in container using `_factory`


```php
<?php

use Fogio\Container;

$validators = new Container();
$validators([
    'notEmpty' => NotEmpty::class,
    'email'    => Email::class,
    '_factory' => function($service, $name, $container) {
        return $service->setTranslator(new Translator());
    }
]);

```
 
### Lazy dynamic services definition using `_init`, no proxy manager needed

```php
<?php

use Fogio\ContainerTrait;

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
