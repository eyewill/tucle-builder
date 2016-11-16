<?php namespace Eyewill\TucleBuilder;

use Illuminate\Support\ServiceProvider;
use View;

class TucleBuilderServiceProvider extends ServiceProvider
{
  protected $defer = false;

  protected $commands = [
    'Eyewill\TucleBuilder\Console\Commands\MakeModule',
  ];

  /**
   * Bootstrap the application services.
   *
   * @return void
   */
  public function boot()
  {
    View::addNamespace('tucle-builder', __DIR__.'/../templates');
  }

  /**
   * Register the application services.
   *
   * @return void
   */
  public function register()
  {
    $this->commands($this->commands);
  }

  public function provides()
  {
  }
}
