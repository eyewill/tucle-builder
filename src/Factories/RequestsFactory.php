<?php namespace Eyewill\TucleBuilder\Factories;

use Exception;
use Eyewill\TucleBuilder\Module;
use File;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;

class RequestsFactory
{
  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $requests = [
    'store',
    'update',
  ];

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
  }

  public function generator()
  {
    File::makeDirectory(dirname($this->path), 02775, true, true);

    foreach ($this->requests as $request)
    {
      $path = sprintf('%s/%s.php',
        $this->path,
        studly_case($request).$this->module->studly('Request'));

      if (!$this->force && File::exists($path))
      {
        throw new Exception($path.' already exists.');
      }

      File::put($path, $this->make($request));
      yield $path;
    }
  }

  protected function make($request)
  {
    $class = new PhpClass();
    $class->setQualifiedName('App\\Http\\Requests\\'.studly_case($request).$this->module->studly('Request').' extends Request');
    $class->setMethod(PhpMethod::create('rules')
      ->setBody($this->rules()));
    $generator = new CodeGenerator();
    return '<?php '.$generator->generate($class);
  }

  protected function rules()
  {
    $php = '';
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
}