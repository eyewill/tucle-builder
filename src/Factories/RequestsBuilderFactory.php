<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Builders\RequestsBuilder;
use Illuminate\Container\Container;

class RequestsBuilderFactory
{
  protected $container;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  public function make($module, $path, $force)
  {
    return new RequestsBuilder(
      $this->container,
      $module,
      $path,
      $force
    );
  }
}