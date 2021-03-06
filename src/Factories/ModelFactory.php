<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Module;
use Exception;
use gossi\codegen\generator\CodeGenerator;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpProperty;
use gossi\codegen\model\PhpTrait;
use Illuminate\Contracts\Container\Container;

class ModelFactory
{
  protected $app;
  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;

  public function __construct(Container $container, $module, $path, $force)
  {
    $this->app = $container;
    $this->module = $module;
    $this->path = $path;
    $this->force = $force;
  }

  public function make()
  {
    if (!$this->force && file_exists($this->path))
      throw new Exception($this->path.' already exists.');

    $this->app['files']->makeDirectory(dirname($this->path), 02755, true, true);

    $this->app['files']->put($this->path, $this->generateCode());

    return $this->path;
  }

  protected function generateCode()
  {
    $class = new PhpClass();
    $isExpirable = $this->module->hasTableColumn('published_at') && $this->module->hasTableColumn('terminated_at');
    $isSortable = $this->module->hasTableColumn('order');
    $uses = [
      'Codesleeve\\Stapler\\ORM\\StaplerableInterface',
      'Illuminate\\Database\\Eloquent\\Model',
    ];
    $traits = [
      PhpTrait::create('Codesleeve\\Stapler\\ORM\\EloquentTrait'),
      PhpTrait::create('Eyewill\\TucleCore\\Eloquent\\Nullable'),
      PhpTrait::create('Eyewill\\TucleCore\\Eloquent\\Batch'),
    ];
    $implements = [
      'StaplerableInterface',
    ];
    if ($isSortable)
    {
      $implements[] = 'SortableInterface';
      $uses[] = 'Eyewill\\TucleCore\\Contracts\\Eloquent\\SortableInterface';
      $traits[] = PhpTrait::create('Eyewill\\TucleCore\\Eloquent\\Sortable');
    }
    if ($isExpirable)
    {
      $implements[] = 'ExpirableInterface';
      $uses[] = 'Eyewill\\TucleCore\\Contracts\\Eloquent\\ExpirableInterface';
      $traits[] = PhpTrait::create('Eyewill\\TucleCore\\Eloquent\\Expirable');
      $traits[] = PhpTrait::create('Eyewill\\TucleCore\\Eloquent\\EventLogTrait');
    }
    $class->setQualifiedName('App\\'.$this->module->studly().' extends Model implements '.implode(', ', $implements));
    $class->setUseStatements($uses);
    $class->setTraits($traits);

    $class->setProperties([
      $this->fillable(),
      $this->nullable(),
    ]);

    $class->setMethods([
      $this->construct(),
      $this->toString(),
      $this->route(),
      $this->getTitleAttribute(),
    ]);

    $generator = new CodeGenerator();

    return '<?php '.$generator->generate($class);
  }

  protected function fillable()
  {
    return PhpProperty::create('fillable')
      ->setVisibility('protected')
      ->setExpression($this->arrayExpression($this->module->getFillable()));
  }

  protected function nullable()
  {
    return PhpProperty::create('nullable')
      ->setVisibility('protected')
      ->setExpression($this->arrayExpression($this->module->getNullable()));
  }

  protected function arrayExpression($array = [])
  {
    if (empty($array))
      return "[]";

    return "[\n\t'".implode("',\n\t'", $array)."'\n]";
  }

  public function construct()
  {
    return PhpMethod::create('__construct')
      ->setVisibility('public')
      ->setParameters([PhpParameter::create('attributes')->setType('array')->setExpression('[]')])
      ->setBody(<<<__PHP__
// \$this->hasAttachedFile('main_image', [
//   'styles' => [
//     'thumbnail' => [
//       'dimensions' => '64x64#',
//       'auto_orient' => true,
//     ],
//   ],
// ]);

parent::__construct(\$attributes);
__PHP__
    );
  }

  public function toString()
  {
    return PhpMethod::create('__toString')
      ->setVisibility('public')
      ->setBody(<<<__PHP__
return \$this->id;
__PHP__
    );
  }

  protected function route()
  {
    $name = $this->module;
    return PhpMethod::create('route')
      ->setVisibility('public')
      ->setBody(<<<__PHP__
return route('$name.index');
__PHP__
      );
  }

  protected function getTitleAttribute()
  {
    return PhpMethod::create('getTitleAttribute')
      ->setVisibility('public')
      ->addParameter(PhpParameter::create('value'))
      ->setBody(<<<__PHP__
return \$this->attributes['title'];
__PHP__
      );
  }
}
