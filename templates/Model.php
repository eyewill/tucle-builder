<?php
/**
 * @var Eyewill\TucleBuilder\Module $module
 */
echo '<?php namespace App;';
?>


use Codesleeve\Stapler\ORM\EloquentTrait;
use Codesleeve\Stapler\ORM\StaplerableInterface;
use Illuminate\Database\Eloquent\Model;

class <?php echo $module->studly() ?> extends Model implements StaplerableInterface
{
  use EloquentTrait;

  protected $fillable = [
<?php foreach ($module->getFillable() as $column) : ?>
    '<?php echo $column ?>',
<?php endforeach; ?>
  ];

  public function __construct(array $attributes = [])
  {
    // $this->hasAttachedFile('main_image', [
    //   'styles' => [
    //     'thumbnail' => [
    //       'dimensions' => '64x64#',
    //       'auto_orient' => true,
    //     ],
    //   ],
    // ]);

    parent::__construct($attributes);
  }

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