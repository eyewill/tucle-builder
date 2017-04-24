<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Module;
use Exception;
use gossi\codegen\generator\CodeFileGenerator;
use Illuminate\Contracts\Container\Container;
class ViewsFactory
{
  /** @var Container */
  protected $app;

  /** @var Module */
  protected $module;

  protected $path;

  protected $force;

  protected $views = [
    'index',
    'create',
    'edit',
  ];

  public function __construct(Container $container, $module, $path, $force)
  {
    $this->app = $container;
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
  }

  public function generator()
  {
    $this->app['files']->makeDirectory($this->path, 02775, true, true);

    foreach($this->views as $view)
    {
      $path = sprintf('%s/%s.blade.php', $this->path, $view);
      if (!$this->force && $this->app['files']->exists($path))
      {
        throw new Exception($path.' already exists.');
      }

      $this->app['files']->put($path, sprintf("@extends('tucle::base.%s')", $view));
      yield $path;
    }
  }

  protected function make()
  {
    $generator = new CodeFileGenerator();
  }
}