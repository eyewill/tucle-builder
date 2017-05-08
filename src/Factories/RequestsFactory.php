<?php namespace Eyewill\TucleBuilder\Factories;

use Exception;
use Eyewill\TucleBuilder\Module;
use File;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;

class RequestsFactory
{
  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $actions = [
    'store',
    'update',
    'delete',
    'delete_file',
    'batch',
  ];

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;

  }

  public function generator()
  {
    $dir = $this->path.'/'.$this->module->studly();
    File::makeDirectory($dir, 02775, true, true);

    foreach ($this->actions as $action)
    {
      $path = sprintf('%s/%s.php',
        $dir,
        studly_case($action).'Request');

      if (!$this->force && File::exists($path))
      {
        throw new Exception($path.' already exists.');
      }

      File::put($path, $this->make($action));
      yield $path;
    }
  }

  protected function make($action)
  {
    $class = new PhpClass();
    $class->addUseStatement('App\\Http\\Presenters\\'.$this->module->studly('Presenter'));
    if ($action == 'update')
    {
      $class->setQualifiedName('App\\Http\\Requests\\'.$this->module->studly().'\\'.studly_case($action).'Request extends StoreRequest');
    }
    else
    {
      $class->addUseStatement('Eyewill\\TucleCore\\Http\\Requests\\Request');
      $class->setQualifiedName('App\\Http\\Requests\\'.$this->module->studly().'\\'.studly_case($action).'Request extends Request');
    }

    $class->setProperty(
      PhpProperty::create('presenter')
        ->setVisibility('protected')
        ->setExpression($this->module->studly('Presenter').'::class')
    );
      $class->setMethod(PhpMethod::create('rules')
        ->setBody($this->rules($action)));
    $generator = new CodeGenerator();
    return '<?php '.$generator->generate($class);
  }

  protected function rules($action)
  {
    $php = '';
    if ($action == 'store')
    {
      $php.= 'return ['.PHP_EOL;
      foreach ($this->module->getTableColumns() as $column)
      {
        if (in_array($column, ['id', 'created_at', 'updated_at']))
          continue;
        $php.= sprintf("'%s' => '%s',", $column, 'required').PHP_EOL;
      }
      $php.= '];'.PHP_EOL;

      return $php;
    }
    elseif ($action == 'update')
    {
      $php.= 'return parent::rules();'.PHP_EOL;
    }
    else
    {
      $php.= 'return [];'.PHP_EOL;
    }

    return $php;
  }
}