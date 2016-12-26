<?php namespace Eyewill\TucleBuilder\Factories;

use Exception;
use Eyewill\TucleBuilder\Module;
use File;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpProperty;

class PresenterFactory
{
  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $routes = [
    'index',
    'create',
    'store',
    'edit',
    'update',
    'show',
    'preview',
    'batch',
    'delete',
    'delete_file',
  ];

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
  }

  public function make()
  {
    if (!$this->force && File::exists($this->path))
      throw new Exception('presenter already exists');

    File::makeDirectory(dirname($this->path), 02775, true, true);

    File::put($this->path, $this->generateCode());

    return $this->path;
  }

  protected function generateCode()
  {
    $class = new PhpClass();
    $class->addUseStatement('Eyewill\\TucleCore\\Http\\Presenters\\ModelPresenter');
    $class->setQualifiedName('App\\Http\\Presenters\\'.$this->module->studly('Presenter').' extends ModelPresenter');
    $properties = [];
    $properties[] = $this->pageTitle();
    $properties[] = $this->breadCrumbs();
    $properties[] = $this->forms();
    $properties[] = $this->showColumns();
    $properties[] = $this->tableColumns();
    $properties[] = $this->routes();

    $class->setProperties(array_reverse($properties));

    $generator = new CodeGenerator();

    return '<?php '.$generator->generate($class);
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

  public function showColumns()
  {
    $columns = $this->module->getTableColumns();

    $entries = [];
    foreach ($columns as $column)
    {
      if (preg_match('/^(.+)_file_name$/', $column, $m))
      {
        $entries[] = $this->makeParamsString([
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
        $entries[] = $this->makeParamsString([
          'name' => $column,
          'label' => $this->module->getColumnLabel($column),
        ]);
      }
    }

    return PhpProperty::create('showColumns')
      ->setExpression("[\n".implode('', $entries)."]");
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
    $routes = [];
    foreach ($this->routes as $route)
    {
      $routes[] = sprintf("\t'%s' => '%s.%s',\n", $route, $this->module, $route);
    }

    return PhpProperty::create('routes')
      ->setExpression("[\n".implode('', $routes)."]");
  }
}