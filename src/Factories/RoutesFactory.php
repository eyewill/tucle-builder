<?php namespace Eyewill\TucleBuilder\Factories;

use Eyewill\TucleBuilder\Module;
use Exception;
use File;

class RoutesFactory
{
  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $uses = [];
  protected $routes = [
    'index',
    'create',
    'store',
    'edit',
    'update',
    'show',
    'delete',
    'delete_file',
  ];

  public function __construct($module, $path, $force)
  {
    $this->module = $module;
    $this->path = $path;
    $this->force = $force;
  }

  public function make()
  {
    if (!$this->force && File::exists($this->path))
      throw new Exception($this->path.' already exists.');

    File::makeDirectory(dirname($this->path), 02755, true, true);

    $this->uses[] = 'App\\'.$this->module->studly();
    $this->uses[] = 'App\\Http\\Presenters\\'.$this->module->studly('Presenter');
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\StoreRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\UpdateRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\DeleteRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\DeleteFileRequest';
    $routes = [];
    foreach ($this->routes as $route)
    {
      $routes[] = $this->{$route}();
    }

    file_put_contents($this->path, view('tucle-builder::routes', [
      'uses'   => $this->uses,
      'routes' => $routes,
      'module' => $this->module,
    ])->render());

    return $this->path;
  }

  protected function index()
  {
    $module = $this->module;
    $model = $this->module->studly();
    $presenter = $this->module->studly('Presenter');
    return <<< __CODE__
/**
 * Index
 * route GET $module
 * name $module.index
*/
Route::get('$module', function ($presenter \$presenter) {
  \$entries = $model::all();
  return view()->make('$module.index', [
    'presenter' => \$presenter,
    'entries' => \$entries,
  ]);
})->name('$module.index');
__CODE__;
  }

  protected function create()
  {
    $module = $this->module;
    $presenter = $this->module->studly('Presenter');
    return <<< __CODE__
/**
 * Create
 * route GET $module/create
 * name $module.create
*/
Route::get('$module/create', function ($presenter \$presenter) {  
  return view()->make('$module.create', [
    'presenter' => \$presenter,
  ]);
})->name('$module.create');
__CODE__;
  }

  protected function store()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Store
 * route POST $module
*/
Route::post('$module', function (StoreRequest \$request) {  
  \$model = $model::create(\$request->all());
  return redirect()
    ->route('$module.show', \$model)
    ->with('success', '作成しました');
})->name('$module.store');
__CODE__;
  }

  protected function edit()
  {
    $module = $this->module;
    $model = $this->module->studly();
    $presenter = $this->module->studly('Presenter');
    return <<< __CODE__
/**
 * Edit
 * route GET $module/{{$module}}/edit
 * name $module.edit
*/
Route::get('$module/{{$module}}/edit', function ($presenter \$presenter, $model \$model) {  
  return view()->make('$module.edit', [
    'model' => \$model,
    'presenter' => \$presenter,
  ]);
})->name('$module.edit');
__CODE__;
  }

  protected function update()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Update
 * route PUT $module/{{$module}}
*/
Route::put('$module/{{$module}}', function (UpdateRequest \$request, $model \$model) {  
  \$model->fill(\$request->all());
  \$model->save();
  return redirect()
    ->route('$module.show', \$model)
    ->with('success', '更新しました');
})->name('$module.update');
__CODE__;
  }

  protected function show()
  {
    $module = $this->module;
    $model = $this->module->studly();
    $presenter = $this->module->studly('Presenter');
    return <<< __CODE__
/**
 * Show
 * route GET $module/{{$module}}
 * name $module.show
*/
Route::get('$module/{{$module}}', function ($presenter \$presenter, $model \$model) {  
  return view()->make('$module.show', [
    'model' => \$model,
    'presenter' => \$presenter,
  ]);
})->name('$module.show');
__CODE__;
  }

  protected function delete()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Delete
 * route DELETE $module/{{$module}}
*/
Route::delete('$module/{{$module}}', function (DeleteRequest \$request, $model \$model) {  
  \$model->delete();
  return redirect()->back()
    ->with('success', '削除しました');
});
__CODE__;
  }

  protected function delete_file()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Delete within file
 * route DELETE $module/{{$module}}/{file}
*/
Route::delete('$module/{{$module}}/{file}', function (DeleteFileRequest \$request, $model \$model, \$file) {  
  
  \$model->{\$file} = STAPLER_NULL;
  \$model->save(); 
  if (\$request->ajax())
  {
    return response()->json([
      'status' => 'ok',
      'message' => '削除しました',
    ]);
  }
  return redirect()->back()
    ->with('success', '削除しました');
});
__CODE__;
  }
}