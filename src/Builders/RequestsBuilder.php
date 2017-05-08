<?php namespace Eyewill\TucleBuilder\Builders;

use Exception;
use Eyewill\TucleBuilder\Module;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;
use Illuminate\Container\Container;

class RequestsBuilder
{
  /** @var  Container */
  protected $app;

  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $rules = [];

  public function __construct(Container $container, $module, $path, $force)
  {
    $this->app    = $container;
    $this->module = $module;
    $this->path   = $path;
    $this->force  = $force;
    $this->setRule('store', function ($builder) {
      $code = '';
      $code.= 'return ['.PHP_EOL;
      foreach ($this->module->getTableColumns() as $column)
      {
        if (in_array($column, ['id', 'created_at', 'updated_at']))
          continue;
        $code.= sprintf("'%s' => '%s',", $column, 'required').PHP_EOL;
      }
      $code.= '];';

      return $code;
    });
    $this->setRule('update', 'return parent::rules();');
    $this->setRule('delete', 'return [];');
    $this->setRule('delete_file', 'return [];');
    $this->setRule('batch', 'return [];');
  }

  public function make()
  {
    if (!$this->force && file_exists($this->path))
      throw new Exception($this->path.' already exists.');

    $this->app['files']->makeDirectory($this->path, 02755, true, true);

    foreach ($this->rules as $action => $rule)
    {
      $path = sprintf('%s/%sRequest.php', $this->path, studly_case($action));
      $this->app['files']->put($path, $this->generateCode($action, $rule));
    }

    return $this->path;
  }

  protected function generateCode($action, $rule)
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
      ->setBody($rule));
    $generator = new CodeGenerator();
    return '<?php '.$generator->generate($class);
  }

  public function setRule($action, $content)
  {
    if (is_callable($content))
    {
      $this->rules[$action] = call_user_func($content, $this);
    }
    else
    {
      $this->rules[$action] = $content;
    }
  }
}