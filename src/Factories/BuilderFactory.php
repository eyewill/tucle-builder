<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\TucleBuilder;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

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