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
    'sort/index',
    'sort/partial/actions/index',
    'sort/partial/datatables/actions/rows',
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
      $src = sprintf('%s/%s.blade.stub', __DIR__.'/../../files/views', $view);
      $dest = sprintf('%s/%s.blade.php', $this->path, $view);
      $this->app['files']->makeDirectory(dirname($dest), 02755, true, true);
      if (!$this->force && $this->app['files']->exists($dest))
      {
        throw new Exception($dest.' already exists.');
      }

      $this->app['files']->copy($src, $dest);

      yield $dest;
    }
  }

  protected function make()
  {
    $generator = new CodeFileGenerator();
  }
}