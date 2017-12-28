<?php namespace Eyewill\TucleBuilder\Builders;

use Exception;
use Eyewill\TucleBuilder\Module;
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

  protected $tableColumns;

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
      throw new Exception('presenter already exists.');

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
    $properties[] = $this->searchColumns();
    $properties[] = $this->dataTables();
    $properties[] = $this->defaultSortColumn();
    $properties[] = $this->defaultSortOrder();

    $class->setProperties($properties);

    $methods = [
      $this->getBreadCrumbs(),
    ];
    $class->setMethods($methods);

    $generator = new CodeGenerator();

    return '<?php '.$generator->generate($class);
  }

  protected function defaultSortColumn()
  {
    $property = PhpProperty::create('defaultSortColumn')
      ->setVisibility('protected');
    $columns = [];
    if ($this->module->hasTableColumn('order'))
    {
      $columns[] = 'order';
    }
    if ($this->module->hasTableColumn('published_at'))
    {
      $columns[] = 'published_at';
    }
    $columns[] = 'created_at';
    $property->setExpression("['".implode("', '", $columns)."']");

    return $property;
  }

  protected function defaultSortOrder()
  {
    $property = PhpProperty::create('defaultSortOrder')
      ->setVisibility('protected');

    $orders = [];
    if ($this->module->hasTableColumn('order'))
    {
      $orders[] = 'asc';
    }
    if ($this->module->hasTableColumn('published_at'))
    {
      $orders[] = 'desc';
    }
    $orders[] = 'desc';

    $property->setExpression("['".implode("', '", $orders)."']");

    return $property;
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
      elseif ('order' == $column)
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

  protected function searchColumns()
  {
    return PhpProperty::create('searchColumns')
      ->setVisibility('protected')
      ->setExpression('[]');
  }

  protected function getTableColumns()
  {
    if (!$this->tableColumns)
    {
      $columns = [];
      $columns[] = [
        'type' => 'checkbox',
      ];
      if ($this->module->hasTableColumn('published_at') && $this->module->hasTableColumn('terminated_at'))
      {
        $columns[] = [
          'type' => 'status',
        ];
      }
      foreach ($this->module->getTableColumns(['id', 'note']) as $i => $column)
      {
        $item = [
          'name' => $column,
          'label' => $this->module->getColumnLabel($column),
        ];
        if (in_array($column, ['title', 'name', 'label']))
        {
          $item['links'] = true;
        }

        $columns[] = $item;
      }

      $this->tableColumns = $columns;
    }

    return $this->tableColumns;
  }

  protected function tableColumns($limit = 5)
  {
    $columns = [];
    foreach ($this->getTableColumns() as $i => $column)
    {
      if ($i >= $limit)
        break;
      $item = [];
      foreach ($column as $name => $value)
      {
        $value = ($value === true) ? 'true': (($value === false) ? 'false' : (is_numeric($value) ? $value : "'$value'"));
        $item[] = sprintf("'%s' => %s", $name, $value);
      }
      $columns[] = $item;
    }

    $code = '';
    $code.= "[\n";
    foreach ($columns as $item)
    {
      $code.= "\t[\n";
      $code.= "\t\t".implode(",\n\t\t", $item)."\n";
      $code.= "\t],\n";
    }
    $code.= "]";

    return PhpProperty::create('tableColumns')
      ->setExpression($code);
  }

  protected function dataTables($limit = 5)
  {
    $expression =<<<__EXPRESSION__
[
  'options' => [
    'columnDefs' => [

__EXPRESSION__;

    foreach ($this->getTableColumns() as $i => $column)
    {
      if ($i >= $limit)
        break;
      if (!array_has($column, 'name'))
      {
        continue;
      }
      if (array_get($column, 'name') == 'order')
      {
        $expression.=<<<__EXPRESSION__
      [
        'className' => 'align-middle text-center',
        'type' => 'num',
        'width' => '80px',
        'targets' => [$i],
      ],

__EXPRESSION__;
      }
      else
      {
        $expression.=<<<__EXPRESSION__
      [
        'className' => 'align-middle',
        'targets' => [$i],
      ],

__EXPRESSION__;
      }
    }
    $expression.=<<<__EXPRESSION__

    ],
  ],
]
__EXPRESSION__;

    return PhpProperty::create('dataTables')
      ->setVisibility('protected')
      ->setExpression($expression);
  }

  protected function routes()
  {
    $routeNames = [
      'index' => '%s.index',
      'create' => '%s.create',
      'store' => '%s.store',
      'edit' => '%s.edit',
      'update' => '%s.update',
      'preview' => '%s.preview',
      'show' => '%s.show',
      'delete' => '%s.delete',
      'delete_file' => '%s.delete_file',
      'front.index' => 'front.%s.index',
      'batch.delete' => '%s.batch.delete',
    ];

    if ($this->module->hasTableColumn('published_at') and $this->module->hasTableColumn('terminated_at'))
    {
      $routeNames = array_merge($routeNames, [
        'batch.publish' => '%s.batch.publish',
        'batch.terminate' => '%s.batch.terminate',
      ]);
    }

    if ($this->module->hasTableColumn('order'))
    {
      $routeNames = array_merge($routeNames, [
        'sort' => '%s.sort.index',
      ]);
    }

    foreach ($routeNames as $name => $route)
    {
      $routes[] = sprintf("\t'%s' => '".$route."',\n", $name, $this->module);
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