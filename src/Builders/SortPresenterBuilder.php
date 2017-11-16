<?php namespace Eyewill\TucleBuilder\Builders;

use Exception;
use Eyewill\TucleBuilder\Module;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use Illuminate\Container\Container;

class SortPresenterBuilder extends PresenterBuilder
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
      throw new Exception('presenter already exists.');

    $this->app['files']->makeDirectory(dirname($this->path), 02775, true, true);
    $this->app['files']->put($this->path, $this->generateCode());

    return $this->path;
  }

  protected function generateCode()
  {
    $class = new PhpClass();
    $class->setQualifiedName('App\\Http\\Presenters\\'.$this->module->studly('SortPresenter').' extends '.$this->module->studly('Presenter'));
    $properties = [];
    $properties[] = $this->viewBase();
    $properties[] = $this->pageTitle();
    $properties[] = $this->breadCrumbs();
    $properties[] = $this->tableColumns();
    $properties[] = $this->routes();
    $properties[] = $this->searchColumns();
    $properties[] = $this->dataTables();
    $properties[] = PhpProperty::create('hasSearchBox')->setVisibility('protected')->setValue(false);
    $properties[] = PhpProperty::create('hasCheckbox')->setVisibility('protected')->setValue(false);
    $properties[] = PhpProperty::create('filters')->setVisibility('protected')->setExpression('[]');

    $class->setProperties($properties);

    $methods = [
      $this->methodGetBreadCrumbs(),
    ];
    $class->setMethods($methods);

    $generator = new CodeGenerator();

    return '<?php '.$generator->generate($class);
  }

  protected function viewBase()
  {
    return PhpProperty::create('viewBase')
      ->setVisibility('protected')
      ->setValue($this->module->snake().'.sort.');
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
      sprintf("\t\t'route' => 'parent.index',\n").
      "\t],\n";

    return PhpProperty::create('breadCrumbs')
      ->setVisibility('protected')
      ->setExpression("[\n".implode('', $breadCrumbs)."]");
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

  protected function getTableColumns()
  {
    if (!$this->tableColumns)
    {
      $columns = [];
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

  protected function dataTables($limit = 5)
  {
    $expression =<<<__EXPRESSION__
[
  'options' => [
    'columnDefs' => [

__EXPRESSION__;

    $indexes = [];
    foreach ($this->getTableColumns() as $i => $column)
    {
      if ($i >= $limit)
        break;
      if (!array_has($column, 'name'))
      {
        continue;
      }
      $indexes[] = $i;
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
    $indexesString = implode(', ', $indexes);
    $expression.=<<<__EXPRESSION__
    [
      'sortable' => false,
      'targets' => [$indexesString],
    ],

__EXPRESSION__;

    $expression.=<<<__EXPRESSION__
    ],
  ],
]
__EXPRESSION__;

    return PhpProperty::create('dataTables')
      ->setVisibility('protected')
      ->setExpression($expression);
  }

  protected function searchColumns()
  {
    return PhpProperty::create('searchColumns')
      ->setVisibility('protected')
      ->setExpression('[]');
  }

  protected function routes()
  {
    $routeNames = [
      'index' => '%s.sort.index',
      'front.index' => 'front.%s.index',
      'parent.index' => '%s.index',
      'edit' => '%s.edit',
    ];

    foreach ($routeNames as $name => $route)
    {
      $routes[] = sprintf("\t'%s' => '".$route."',\n", $name, $this->module);
    }

    return PhpProperty::create('routes')
      ->setExpression("[\n".implode('', $routes)."]");
  }

  public function methodGetBreadCrumbs()
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
      'label' => '並び替え',
    ],
  ],
];

return array_get(\$breadCrumbs, \$name, []);
__CODE__
    );
  }
}