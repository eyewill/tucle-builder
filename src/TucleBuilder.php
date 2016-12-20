<?php namespace Eyewill\TucleBuilder;

use Exception;
use Eyewill\TucleBuilder\Factories\ModelFactory;
use Eyewill\TucleBuilder\Factories\PresenterFactory;
use Eyewill\TucleBuilder\Factories\RequestsFactory;
use Eyewill\TucleBuilder\Factories\RoutesFactory;
use Eyewill\TucleBuilder\Factories\ViewsFactory;
use Generator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class TucleBuilder
{
  /** @var Container */
  protected $app;
  /** @var Module */
  protected $module;
  protected $instance;
  protected $force;
  protected $targets = [];

  protected function routesPath()
  {
    return $this->app['path'].'/Http/routes';
  }

  protected function modelPath()
  {
    return $this->app['path'];
  }

  protected function presenterPath()
  {
    return $this->app['path'].'/Http/Presenters';
  }

  protected function viewsPath()
  {
    return $this->app->basePath().'/resources/views';
  }

  protected function requestsPath()
  {
    return $this->app['path'].'/Http/Requests';
  }

  public function __construct(Container $container, $module, $force = false, $only = null, $table = null)
  {
    $this->app = $container;
    $this->module = new Module($container, $module, $table);
    $this->force = $force;
    $this->targets = ['routes', 'model', 'presenter', 'views', 'requests'];
    if (!is_null($only))
    {
      $only = explode(',', $only);
      $this->targets = array_where($this->targets, function ($key, $value) use ($only) {
        return in_array($value, $only);
      });
    }
    if (!$this->getSchema()->hasTable($this->module->tableize()))
    {
      throw new Exception($this->module->tableize().'テーブルを作成してください');
    }
  }

  /**
   * @return SchemaBuilder
   */
  protected function getSchema()
  {
    return $this->app['db']->getSchemaBuilder();
  }

  protected function getRoutesFactory()
  {
    $path = $this->routesPath().'/'.$this->module->snake().'.php';
    $factory = new RoutesFactory($this->module, $path, $this->force);

    return $factory;
  }

  protected function getModelFactory()
  {
    $path = $this->modelPath().'/'.$this->module->studly().'.php';
    $factory = new ModelFactory($this->app, $this->module, $path, $this->force);

    return $factory;
  }

  protected function getPresenterFactory()
  {
    $path = $this->presenterPath().'/'.$this->module->studly('Presenter').'.php';
    $factory = new PresenterFactory($this->module, $path, $this->force);

    return $factory;
  }

  protected function getViewsFactory()
  {
    $path = $this->viewsPath().'/'.$this->module->snake();
    $factory = new ViewsFactory($this->app, $this->module, $path, $this->force);

    return $factory;
  }

  protected function getRequestsFactory()
  {
    $path = $this->requestsPath();
    $factory = new RequestsFactory($this->module, $path, $this->force);

    return $factory;
  }

  /**
   * @return Generator
   */
  public function generator()
  {
    if (in_array('routes', $this->targets))
    {
      $factory = $this->getRoutesFactory();
      $routesPath = $factory->make();
      yield $routesPath.' generated.';
    }

    if (in_array('model', $this->targets))
    {
      $factory = $this->getModelFactory();
      $presenterPath = $factory->make();
      yield $presenterPath.' generated.';
    }

    if (in_array('presenter', $this->targets))
    {
      $factory = $this->getPresenterFactory();
      $presenterPath = $factory->make();
      yield $presenterPath.' generated.';
    }

    if (in_array('views', $this->targets))
    {
      $factory = $this->getViewsFactory();
      foreach ($factory->generator() as $generator)
      {
        yield $generator.' generated.';
      }
    }

    if (in_array('requests', $this->targets))
    {
      $factory = $this->getRequestsFactory();
      foreach ($factory->generator() as $generator)
      {
        yield $generator.' generated.';
      }
    }
  }
}