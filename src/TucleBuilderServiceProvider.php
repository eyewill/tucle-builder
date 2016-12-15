<?php namespace Eyewill\TucleBuilder;

use Illuminate\Support\ServiceProvider;

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
    $this->app['view']->addNamespace('tucle-builder', __DIR__.'/../templates');
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
