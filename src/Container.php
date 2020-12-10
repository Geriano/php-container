<?php

namespace Geriano\Container;

use ArrayAccess;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Container implements ArrayAccess 
{
  /**
   * Self instance
   */
  private static Container $instance;

  /**
   * Saved class instances
   */
  private array $instances = [];

  /**
   * Saved class bindings
   */
  private array $bindings = [];

  /**
   * Saved class aliases
   */
  private array $aliases = [];

  /**
   * Set instance container class
   * Maybe override with child class
   */
  protected static function setInstance(Container $instance) : Container
  {
    return static::$instance = $instance;
  }

  /**
   * Get current container instance
   */
  public function getInstance() : Container
  {
    // if container instance not exists
    // we set it to default
    return static::$instance ?? static::setInstance(new Container());
  }

  /**
   * Save a class instance
   */
  public function instance(string $abstract, $instance)
  {
    $abstract = $this->binding($abstract);

    if($this->has($abstract)) 
      return $instance;

    return $this->instances[$abstract] = $instance;
  }

  /**
   * Set|Get abstract class binding
   */
  public function binding(string $abstract, string $binding = null) : string
  {
    if($binding)
      return $this->bindings[$abstract] = $binding;

    return $this->bindings[$abstract] ?? $this->alias($abstract);
  }

  /**
   * Set|Get abstract class binding
   */
  public function alias(string $abstract, string $binding = null) : string
  {
    if($binding)
      return $this->aliases[$abstract] = $binding;

    return $this->aliases[$abstract] ?? $abstract;
  }

  /**
   * Check if class already instance
   */
  public function has(string $abstract) : bool
  {
    return array_key_exists($this->binding($abstract), $this->instances);
  }

  /**
   * Remove class instance from container
   */
  public function remove(string $abstract) : self
  {
    if($this->has($abstract = $this->binding($abstract)))
      unset($this->instances[$abstract]);

    return $this;
  }

  /**
   * Create a class instance
   */
  public function make(string $abstract, array $parameters = [])
  {
    $abstract = $this->binding($abstract);

    if($this->has($abstract))
      return $this->instances[$abstract];

    return $this->instance($abstract, $this->build(
      $abstract, $parameters
    ));
  }

  /**
   * Build an instance class
   */
  private function build(string $abstract, array $parameters = [])
  {
    try {
      $reflection  = new ReflectionClass($abstract);
      $constructor = $reflection->getConstructor();
    } catch (ReflectionException $e) {
      throw new BindingResolutionException($e->getMessage());
    }

    if($constructor) {
      try {
        $dependencies = $this->dependencies($constructor->getParameters(), $parameters);
      } catch(BindingResolutionException $e) {
        throw new BindingResolutionException(sprintf(
          'Unable to build class [%s], %s', $abstract, $e->getMessage()
        ));
      }

      return $reflection->newInstanceArgs($dependencies);
    }

    return $reflection->newInstance();
  }

  /**
   * Find dependencies from reflection
   */
  protected function dependencies(array $reflections = [], array $parameters = [])
  {
    $dependencies = [];

    foreach($reflections as $reflection) {
      if(array_key_exists($reflection->name, $parameters)) {
        $dependencies[] = $parameters[$reflection->name];
      } else {
        if(! in_array($reflection->getType()->getName(), ['string', 'array', 'int', 'float', 'bool'])) {
          try {
            $dependencies[] = $this->make($reflection->getType()->getName());
          } catch (BindingResulutionException $e) {
            if(! $reflection->isDefaultValueAvailable())
              throw $e;

            $dependencies[] = $reflection->getDefaultValue();
          }
        } else {
          if(! $reflection->isDefaultValueAvailable()) 
            throw new BindingResolutionException(sprintf(
              'Unable to resolve dependency [%s]', $reflection->name
            ));

          $dependencies[] = $reflection->getDefaultValue();
        }
      }
    }

    return $dependencies;
  }

  /**
   * @{implements}
   */
  public function offsetExists($abstract)
  {
    return $this->has($abstract);
  }

  /**
   * @{implements}
   */
  public function offsetGet($abstract)
  {
    return $this->make($abstract);
  }

  /**
   * @{implements}
   */
  public function offsetSet($abstract, $instance)
  {
    return $this->has($abstract, $instance);
  }

  /**
   * @{implements}
   */
  public function offsetUnset($abstract)
  {
    return $this->remove($abstract);
  }
}