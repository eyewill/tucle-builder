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
    $properties[] = $this->entryTableColumns();
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
    $columns = [];
    foreach ($this->module->getTableColumns() as $column)
    {
      $columns[] = "\t[\n".
        sprintf("\t\t'name' => '%s',\n", $column).
        sprintf("\t\t'label' => '%s',\n", $this->module->getColumnLabel($column)).
        "\t],\n";
    }

    return PhpProperty::create('showColumns')
      ->setExpression("[\n".implode('', $columns)."]");
  }

  protected function forms()
  {
    $columns = $this->module->getTableColumns();
    $columns = array_diff($columns, ['id', 'created_at', 'updated_at']);
    $forms = [];
    if (in_array('published_at', $columns) && in_array('terminated_at', $columns))
    {
      $columns = array_diff($columns, ['published_at', 'terminated_at']);
      $forms[] = $this->makeFormSpec([
        'type' => 'published',
      ]);
    }

    foreach ($columns as $column)
    {
      $forms[] = $this->makeFormSpec([
        'type' => $this->module->getFormType($column),
        'name' => $column,
        'label' => $this->module->getColumnLabel($column),
      ]);
    }

    return PhpProperty::create('forms')
      ->setExpression("[\n".implode('', $forms)."]");
  }

  protected function makeFormSpec(array $attributes = [])
  {
    $formSpec = "\t[\n";
    foreach ($attributes as $name => $value)
    {
      $formSpec.= sprintf("\t\t'%s' => '%s',\n",
        $name,
        $value
      );
    }
    $formSpec.= "\t],\n";

    return $formSpec;
  }

  protected function entryTableColumns($limit = 5)
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

    return PhpProperty::create('entryTableColumns')
      ->setExpression("[\n".implode('', $columns)."]");
  }

  protected function routes()
  {
    $routes = [];
    foreach (['index', 'create', 'edit', 'show'] as $route)
    {
      $routes[] = sprintf("\t'%s' => '%s.%s',\n", $route, $this->module, $route);
    }

    return PhpProperty::create('routes')
      ->setExpression("[\n".implode('', $routes)."]");
  }
}