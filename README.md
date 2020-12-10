# php-container
[h1]PHP Dependency Injection

Usage:
```php

use Geriano\Container\Container;

$container = new Container();

class Bar {
  // 
}

class Foo {
  public function __construct(
    protected Bar $bar
  ) {}
}

$foo = $container->make(Foo::class);

var_dump($foo);
// object Foo {
//   protected $bar = object Bar {
//   }
// }

class Foo {
  public function __constructor(
    protected string $name
  )
}

var_dump($container->make(Foo::class, [
  'name' => 'World'
]));

// object Foo {
//   protected $name = 'World'
// }

```

Injection Usage:
```php

use Geriano\Container\Injection;

class Foo {
  use Injection;

  public function __construct()
  {
    $this->inject('bar', function () {
      return 'Bar Injected';
    });
  }
}

$foo = new Foo();

var_dump($foo->bar());
// Bar Injected
```

Requirement:
**php: 8

Instalation:
```bash
composer require geriano/container
```