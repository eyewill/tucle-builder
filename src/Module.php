<?php namespace Eyewill\TucleBuilder;

use Lang;
use Schema;

class Module
{
  protected $name;
  protected $table;

  public function __construct($name, $table = null)
  {
    $this->name = $name;
    $this->table = $table;
  }

  public function studly($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return studly_case($name);
  }

  public function camel($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return camel_case($name);
  }

  public function snake($suffix = null)
  {
    $name = $this->name;
    if ($suffix)
      $name = $this->name.$suffix;

    return snake_case($name);
  }

  public function tableize()
  {
    if (!$this->table)
    {
      $this->table = str_plural(snake_case($this->name));
    }

    return $this->table;
  }

  public function __toString()
  {
    return $this->name;
  }

  public function getTableColumns($except = [])
  {
    return array_diff(Schema::getColumnListing($this->tableize()), $except);
  }

  public function hasTableColumn($column)
  {
    return in_array($column, Schema::getColumnListing($this->tableize()));
  }

  public function getColumnType($column)
  {
    $type = Schema::getColumnType($this->tableize(), $column);
//    $type = 'text';

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
    if (Lang::has('validation.attributes.'.$column))
    {
      return Lang::get('validation.attributes.'.$column);
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