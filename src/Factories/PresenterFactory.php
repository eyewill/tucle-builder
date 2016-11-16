<?php namespace Eyewill\TucleBuilder\Factories;

use Exception;
use Eyewill\TucleBuilder\Module;
use File;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\generator\GeneratorStrategy;
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
    /** @var GeneratorStrategy $strategy */
//    $strategy = $generator->getGeneratorStrategy();
//    $strategy->setPropertySortFunc(function ($a, $b) { // 定義順
//      return 0;
//    });

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
    $forms = [];
    foreach ($this->module->getTableColumns() as $column)
    {
      if (!in_array($column, ['id', 'created_at', 'updated_at']))
      {
        $forms[] = "\t[\n".
          sprintf("\t\t'type' => '%s',\n", $this->module->getFormType($column)).
          sprintf("\t\t'name' => '%s',\n", $column).
          sprintf("\t\t'label' => '%s',\n", $this->module->getColumnLabel($column)).
          "\t],\n";
      }
    }

    return PhpProperty::create('forms')
      ->setExpression("[\n".implode('', $forms)."]");
  }

  protected function entryTableColumns()
  {
    $columns = [];
    foreach ($this->module->getTableColumns() as $column)
    {
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