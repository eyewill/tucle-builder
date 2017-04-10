<?php namespace Eyewill\TucleBuilder\Builders;

use Eyewill\TucleBuilder\Module;
use Exception;
use File;
use Illuminate\Container\Container;

class RoutesBuilder
{
  /** @var Container */
  protected $app;

  /** @var  Module */
  protected $module;
  protected $path;
  protected $force;
  protected $uses = [];

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
    {
      throw new Exception($this->path.' already exists.');
    }

    $this->app['files']->makeDirectory(dirname($this->path), 02755, true, true);

    $this->uses[] = 'App\\'.$this->module->studly();
    $this->uses[] = 'App\\Http\\Presenters\\'.$this->module->studly('Presenter');
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\StoreRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\UpdateRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\DeleteRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\DeleteFileRequest';
    $this->uses[] = 'App\\Http\\Requests\\'.$this->module->studly().'\\BatchRequest';
    $routeNames = [
      'index',
      'create',
      'store',
      'edit',
      'update',
      'show',
      'preview',
      'delete',
      'delete_file',
      'batch.delete',
    ];
    if ($this->module->hasTableColumn('published_at') and $this->module->hasTableColumn('terminated_at'))
    {
      $routeNames = array_merge($routeNames, [
        'batch.publish',
        'batch.terminate',
      ]);
    }
    $routes = [];
    foreach ($routeNames as $route)
    {
      $routes[] = $this->{str_replace('.', '_', $route)}();
    }

    $content = $this->app['view']->make('tucle-builder::routes', [
      'uses'   => $this->uses,
      'routes' => $routes,
      'module' => $this->module,
    ])->render();

    file_put_contents($this->path, $content);

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
  if (\$request->ajax())
  {
  session()->flash('success', '削除しました');
    return response()->json([
      'status' => 'ok',
      'message' => '削除しました',
    ]);
  }
  return redirect()->route('$module.index')
    ->with('success', '削除しました');
})->name('$module.delete');
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
  session()->flash('success', '削除しました');
    return response()->json([
      'status' => 'ok',
      'message' => '削除しました',
    ]);
  }
  return redirect()->route('$module.index')
    ->with('success', '削除しました');
})->name('$module.delete_file');
__CODE__;
  }

  protected function preview()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Preview
 * route Get $module/{{$module}}/preview
*/
Route::get('$module/{{$module}}/preview', function ($model \$model) {  
  return response()->make('preview...');
})->name('$module.preview');
__CODE__;
  }


  protected function batch_delete()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Batch delete
 * route POST $module/batch/delete
*/
Route::post('$module/batch/delete', function (BatchRequest \$request) {  
  
  \$complete = $model::batch('delete', \$request->json());
  \$message = sprintf('%d件中%d件のレコードを削除しました', count(\$request->json()), \$complete);

  session()->flash('success', \$message);

  return response()->json([
    'status' => 'ok',
    'message' => \$message,
  ]);

})->middleware('json')->name('$module.batch.delete');
__CODE__;
  }

  protected function batch_publish()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Batch publish
 * route POST $module/batch/publish
*/
Route::post('$module/batch/publish', function (BatchRequest \$request) {  
  
  \$complete = $model::batch('publish', \$request->json());
  \$message = sprintf('%d件中%d件のレコードを公開しました', count(\$request->json()), \$complete);

  session()->flash('success', \$message);

  return response()->json([
    'status' => 'ok',
    'message' => \$message,
  ]);

})->middleware('json')->name('$module.batch.publish');
__CODE__;
  }


  protected function batch_terminate()
  {
    $module = $this->module;
    $model = $this->module->studly();
    return <<< __CODE__
/**
 * Batch terminate
 * route POST $module/batch/terminate
*/
Route::post('$module/batch/terminate', function (BatchRequest \$request) {  
  
  \$complete = $model::batch('terminate', \$request->json());
  \$message = sprintf('%d件中%d件のレコードの公開を終了しました', count(\$request->json()), \$complete);

  session()->flash('success', \$message);

  return response()->json([
    'status' => 'ok',
    'message' => \$message,
  ]);

})->middleware('json')->name('$module.batch.terminate');
__CODE__;
  }

}