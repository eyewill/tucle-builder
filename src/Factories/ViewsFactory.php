<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Module;
use Exception;
use File;
use gossi\codegen\generator\CodeFileGenerator;

class ViewsFactory
{
  /** @var Module */
  protected $module;

  protected $path;

  protected $force;

  protected $views = [
    'index',
    'show',
    'create',
    'edit',
  ];

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
  }

  public function generator()
  {
    File::makeDirectory($this->path, 02775, true, true);

    foreach($this->views as $view)
    {
      $path = sprintf('%s/%s.blade.php', $this->path, $view);
      if (!$this->force && File::exists($path))
      {
        throw new Exception($path.' already exists.');
      }

      File::put($path, sprintf("@extends('tucle::base.%s')", $view));
      yield $path;
    }
  }

  protected function make()
  {
    $generator = new CodeFileGenerator();
  }
}