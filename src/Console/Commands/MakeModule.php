<?php namespace Eyewill\TucleBuilder\Console\Commands;

use Exception;
use Eyewill\TucleBuilder\TucleBuilder;
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
      $factory = new TucleBuilder($module, $force, $only, $table);
      foreach ($factory->generator() as $message)
        $this->info($message);
    } catch (Exception $e) {

      $this->error($e->getFile().':'.$e->getLine().' '.$e->getMessage());
      exit(-1);
    }

    $this->info($module.' Module created!');
  }
}
