<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Builders\SortPresenterBuilder;
use Illuminate\Container\Container;

class SortPresenterBuilderFactory
{
  protected $container;
  
  public function __construct(Container $container)
  {
    $this->container = $container;
  }
  
  public function make($module, $path, $force)
  {
    return new SortPresenterBuilder(
      $this->container,
      $module,
      $path,
      $force
    );
  }
}