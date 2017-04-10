<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Builders\RoutesBuilder;
use Illuminate\Container\Container;

class RoutesBuilderFactory
{
  protected $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  public function make($module, $path, $force)
  {
    return new RoutesBuilder(
      $this->container,
      $module,
      $path,
      $force
    );
  }
}