<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Builders\TucleBuilder;
use Illuminate\Container\Container;

class BuilderFactory
{
  protected $container;
  protected $composer;

  public function __construct(Container $container)
  {
    $this->container = $container;
  }

  public function make($module, $force = false, $only = null, $table = null)
  {
    return new TucleBuilder(
      $this->container,
      $module,
      $force,
      $only,
      $table
    );
  }
}