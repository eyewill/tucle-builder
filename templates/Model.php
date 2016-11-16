<?php
/**
 * @var Eyewill\TucleBuilder\Module $module
 */
echo '<?php namespace App;';
?>


use Illuminate\Database\Eloquent\Model;

class <?php echo $module->studly() ?> extends Model
{
  protected $fillable = [
<?php foreach ($module->getTableColumns(['id', 'created_at', 'updated_at']) as $column) : ?>
    '<?php echo $column ?>',
<?php endforeach; ?>
  ];

  public function __toString()
  {
    return $this->id;
  }

  public function route()
  {
    return route('<?php echo $module ?>.index');
  }

  public function site()
  {
    return '#';
  }

  <?php if (!$module->hasTableColumn('title')) : ?>
  public function getTitleAttribute()
  {
    return $this->attributes['<?php echo head($module->getTableColumns(['id', 'created_at', 'updated_at'])) ?>'];
  }
  <?php endif; ?>
}