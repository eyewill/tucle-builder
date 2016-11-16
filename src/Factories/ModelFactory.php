<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Module;
use File;
use Exception;

class ModelFactory
{
  /** @var  Module */
  protected $module;

  protected $path;

  protected $force;

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path = $path;
    $this->force = $force;
  }

  public function make()
  {
    if (!$this->force && File::exists($this->path))
      throw new Exception($this->path.' already exists.');

    File::makeDirectory(dirname($this->path), 02755, true, true);

    file_put_contents($this->path, view('tucle-builder::Model', [
      'module' => $this->module,
    ])->render());

    return $this->path;
  }

}