<?php namespace Eyewill\TucleBuilder\Console\Commands;

use Exception;
use Eyewill\TucleBuilder\Factories\BuilderFactory;
use Illuminate\Console\Command;

class MakeModule extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'make:module {module} {--force} {--only=} {--table=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Scaffold Tucle modules';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {
    $module = $this->argument('module');
    $force = $this->option('force');
    $only = $this->option('only');
    $table = $this->option('table');

    try {
      /** @var BuilderFactory $factory */
      $factory = $this->getLaravel()->make(\Eyewill\TucleBuilder\Factories\BuilderFactory::class);
      $builder = $factory->make($module, $force, $only, $table);

      foreach ($builder->generator() as $message)
      {
        $this->info($message);
      }

      $this->info($module.' Module created!');

    } catch (Exception $e) {

      $this->error($e->getFile().':'.$e->getLine().' '.$e->getMessage());
    }
  }
}
