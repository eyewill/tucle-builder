<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Builders\PresenterBuilder;
use Illuminate\Container\Container;

class PresenterBuilderFactory
{
  protected $container;
  
  public function __construct(Container $container)
  {
    $this->container = $container;
  }
  
  public function make($module, $path, $force)
  {
    return new PresenterBuilder(
      $this->container,
      $module,
      $path,
      $force
    );
  }
}