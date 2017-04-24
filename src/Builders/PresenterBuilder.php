<?php namespace Eyewill\TucleBuilder\Builders;

use Exception;
use Eyewill\TucleBuilder\Module;
use File;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Illuminate\Container\Container;

class PresenterBuilder
{
  /** @var Container */
  protected $app;

  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;

  public function __construct(Container $container, $module, $path, $force)
  {
    $this->app    = $container;
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
  }

  public function make()
  {
    if (!$this->force && file_exists($this->path))
      throw new Exception('presenter already exists');

    $this->app['files']->makeDirectory(dirname($this->path), 02775, true, true);
    $this->app['files']->put($this->path, $this->generateCode());

    return $this->path;
  }

  protected function generateCode()
  {
    $class = new PhpClass();
    $class->addUseStatement('Eyewill\\TucleCore\\Http\\Presenters\\ModelPresenter');
    $class->setQualifiedName('App\\Http\\Presenters\\'.$this->module->studly('Presenter').' extends ModelPresenter');
    $properties = [];
    $properties[] = $this->viewBase();
    $properties[] = $this->pageTitle();
    $properties[] = $this->breadCrumbs();
    $properties[] = $this->forms();
    $properties[] = $this->tableColumns();
    $properties[] = $this->routes();
    $properties[] = $this->filters();
    $class->setProperties($properties);

    $methods = [
      $this->getBreadCrumbs(),
    ];
    $class->setMethods($methods);

    $generator = new CodeGenerator();

    return '<?php '.$generator->generate($class);
  }

  protected function viewBase()
  {
    return PhpProperty::create('viewBase')
      ->setVisibility('protected')
      ->setValue($this->module->snake().'.');
  }

  protected function pageTitle()
  {
    return PhpProperty::create('pageTitle')
      ->setVisibility('protected')
      ->setValue($this->module->studly());
  }

  protected function filters()
  {
    return PhpProperty::create('filters')
      ->setVisibility('protected')
      ->setExpression('[]');
  }

  public function breadCrumbs()
  {
    $breadCrumbs = [];
    $breadCrumbs[] = "\t[\n".
      sprintf("\t\t'label' => '%s',\n", $this->module->studly()).
      sprintf("\t\t'route' => 'index',\n").
      "\t],\n";

    return PhpProperty::create('breadCrumbs')
      ->setVisibility('protected')
      ->setExpression("[\n".implode('', $breadCrumbs)."]");
  }

  protected function forms()
  {
    $columns = $this->module->getTableColumns();
    $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
    $forms = [];

    foreach ($columns as $column)
    {
      if ('published_at' == $column)
      {
        $forms[] = $this->makeParamsString([
          'type' => 'published',
        ]);
      }
      elseif ('terminated_at' == $column)
      {
        continue;
      }
      elseif (preg_match('/^(.+)_file_name$/', $column, $m))
      {
        $forms[] = $this->makeParamsString([
          'type' => 'image',
          'name' => $m[1],
          'label' => $m[1],
        ]);
      }
      elseif (preg_match('/^.+_(file_size|content_type|updated_at)$/', $column))
      {
        continue;
      }
      else
      {
        $forms[] = $this->makeParamsString([
          'type' => $this->module->getFormType($column),
          'name' => $column,
          'label' => $this->module->getColumnLabel($column),
        ]);
      }
    }

    return PhpProperty::create('forms')
      ->setExpression("[\n".implode('', $forms)."]");
  }

  protected function makeParamsString(array $params = [])
  {
    $code = "\t[\n";
    foreach ($params as $name => $value)
    {
      $code.= sprintf("\t\t'%s' => '%s',\n",
        $name,
        $value
      );
    }
    $code.= "\t],\n";

    return $code;
  }

  protected function tableColumns($limit = 5)
  {
    $columns = [];
    foreach ($this->module->getTableColumns() as $i => $column)
    {
      if ($i >= $limit)
        break;
      $columns[] = "\t[\n".
        sprintf("\t\t'name' => '%s',\n", $column).
        sprintf("\t\t'label' => '%s',\n", $this->module->getColumnLabel($column)).
        (in_array($column, ['id', 'title']) ? sprintf("\t\t'links' => true,\n") : '').
        "\t],\n";
    }

    return PhpProperty::create('tableColumns')
      ->setExpression("[\n".implode('', $columns)."]");
  }

  protected function routes()
  {
    $routeNames = [
      'index',
      'create',
      'store',
      'edit',
      'update',
      'show',
      'preview',
      'delete',
      'delete_file',
      'batch.delete'
    ];

    if ($this->module->hasTableColumn('published_at') and $this->module->hasTableColumn('terminated_at'))
    {
      $routeNames = array_merge($routeNames, [
        'batch.publish',
        'batch.terminate'
      ]);
    }

    foreach ($routeNames as $route)
    {
      $routes[] = sprintf("\t'%s' => '%s.%s',\n", $route, $this->module, $route);
    }

    return PhpProperty::create('routes')
      ->setExpression("[\n".implode('', $routes)."]");
  }

  public function getBreadCrumbs()
  {
    $module = $this->module->snake();

    return PhpMethod::create('getBreadCrumbs')
      ->setType('array')
      ->setVisibility('protected')
      ->setParameters([new PhpParameter('name'), new PhpParameter('request')])
      ->setBody(<<<__CODE__
\$model = \$request->route('$module');

\$breadCrumbs = [
  'index' => [
    [
      'label' => '一覧',
    ],
    ],
      'create' => [
    [
      'label' => '新規作成',
    ],
    ],
    'edit' => [
    [
      'label' => \$this->getPageTitle(\$model),
    ],
  ],
];

return array_get(\$breadCrumbs, \$name, []);
__CODE__
    );
  }
}