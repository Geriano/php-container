<?php

namespace Geriano\Container;

use Closure;
use ReflectionClass;
use ReflectionMethod;

trait Injection
{
  /**
   * method injecteds
   */
  private static array $injecteds = [];

  /**
   * add method to inject
   */
  public static function inject(string $method, Closure $callback) : void
  {
    static::$injecteds[$method] = $callback;
  }

  /**
   * call method dinamically
   */
  public function __call(string $method, array $parameters = [])
  {
    if(! array_key_exists($method, static::$injecteds))
      throw new BindingResolutionException(sprintf(
        'Method %s::%s not exists.', static::class, $method
      ));

    return Container::getInstance()->call(static::$injecteds[$method], $parameters);
  }

  /**
   * call method dinamically
   */
  public static function __callStatic(string $method, array $parameters = [])
  {
    if(! array_key_exists($method, static::$injecteds))
      throw new BindingResolutionException(sprintf(
        'Method %s::%s not exists.', static::class, $method
      ));

    return Container::getInstance()->call(static::$injecteds[$method], $parameters);
  }
}