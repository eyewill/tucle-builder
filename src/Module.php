<?php namespace Eyewill\TucleBuilder;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContracts;
use Illuminate\Database\Connection;
use Illuminate\Translation\Translator;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class Module
{
  /** @var Container */
  protected $app;

  protected $name;
  protected $table;

  public function __construct(ContainerContracts $container, $name, $table = null)
  {
    $this->app = $container;
    $this->name = $name;
    $this->table = $table;
  }

  /**
   * @return Connection
   */
  protected function getConnection()
  {
    return $this->app['db'];
  }

  /**
   * @return SchemaBuilder
   */
  protected function getSchema()
  {
    return $this->app['db']->getSchemaBuilder();
  }

  /**
   * @return Translator
   */
  protected function getLang()
  {
    return $this->app['translator'];
  }

  public function studly($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return Str::studly($name);
  }

  public function camel($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return Str::camel($name);
  }

  public function snake($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return Str::snake($name);
  }

  public function tableize()
  {
    if (!$this->table)
    {
      $this->table = Str::plural(Str::snake($this->name));
    }

    return $this->table;
  }

  public function __toString()
  {
    return $this->name;
  }

  public function getFiles()
  {
    $columns = $this->getSchema()->getColumnListing($this->tableize());

    $files = [];
    foreach ($columns as $column)
    {
      if (preg_match('/^(.+)_file_name$/', $column, $m))
      {
        $files[] = $m[1];
      }
    }

    return $files;
  }

  public function getFillable($except = [])
  {
    $columns = array_diff($this->getSchema()->getColumnListing($this->tableize()), $except);

    $files = $this->getFiles();

    $fillable = [];
    foreach ($columns as $column)
    {
      if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at']))
      {
        continue;
      }
      foreach ($files as $file)
      {
        if ($column == $file.'_file_name')
        {
          $fillable[] = $file;
          continue 2;
        }
        elseif (in_array($column, [$file.'_file_size', $file.'_content_type', $file.'_updated_at']))
        {
          continue 2;
        }
      }
      $fillable[] = $column;
    }

    return $fillable;
  }

  public function getNullable($except = [])
  {
    $files = $this->getFiles();
    foreach ($files as $file)
    {
      foreach (['_file_name', '_file_size', '_content_type', '_updated_at'] as $suffix)
      {
        $except[] = $file.$suffix;
      }
    }

    $columns = $this->getFillable($except);

    $nullable = [];
    foreach ($columns as $column)
    {
      if (!$this->getConnection()->getDoctrineColumn($this->tableize(), $column)->getNotnull())
      {
        if ($column != '')
          $nullable[] = $column;
      }
    }

    return $nullable;
  }

  public function getTableColumns($except = [])
  {
    return array_diff($this->getSchema()->getColumnListing($this->tableize()), $except);
  }

  public function hasTableColumn($column)
  {
    return in_array($column, $this->getSchema()->getColumnListing($this->tableize()));
  }

  public function getColumnType($column)
  {
    $type = $this->getSchema()->getColumnType($this->tableize(), $column);

    return $type;
  }

  public function getFormType($column, $default = 'text')
  {
    $formTypes = [
      'text'     => ['string', 'integer', 'datetime'],
      'textarea' => ['text'],
    ];

    $columnType = $this->getColumnType($column);
    foreach ($formTypes as $formType => $columnTypes)
    {
      if (in_array($columnType, $columnTypes))
      {
        return $formType;
      }
    }

    return $default;
  }

  public function getColumnLabel($column)
  {
    if ($this->getLang()->has('validation.attributes.'.$column))
    {
      return $this->getLang()->get('validation.attributes.'.$column);
    }
    return $column;
  }

  public function bladeRoute($route)
  {
    $blade = '';
    $blade.= '{{ ';
    $blade.= sprintf("route('%s.%s')", $this->name, $route);
    $blade.= ' }}';
    return $blade;
  }
}