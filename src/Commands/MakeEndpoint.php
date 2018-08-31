<?php


namespace Petrelli\EloquentConsumer\Commands;


use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;


class MakeEndpoint extends Command
{


    protected $signature = 'eloquent-consumer:endpoint {endpointName} {namespace?}';

    protected $description = 'Create a new Eloquent Consumer Endpoint';

    protected $files;

    protected $composer;


    public function __construct(Filesystem $files, Composer $composer)
    {

        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;

    }

    public function handle()
    {

        $endpointName = $this->argument('endpointName');
        $namespace = $this->argument('namespace') ?? 'ApiConsumer/Endpoints';

        $className = Str::studly(Str::singular($endpointName));

        $this->createEndpoint($className, $namespace);

        $fullPath = join('/', [$namespace, $endpointName]);
        $this->info("Adding the new Endpoint at app/{$fullPath}.php");

        $this->composer->dumpAutoloads();

    }


    private function createEndpoint($className, $namespace)
    {

        if (!class_exists($className)) {

            if (!$this->files->isDirectory(app_path($namespace))) {
                $this->files->makeDirectory(app_path($namespace), 0777, true);
            }

            $stub = str_replace(
                ['{{className}}', '{{namespace}}'],
                [$className, str_replace('/', '\\', $namespace)],
                $this->files->get(__DIR__ . '/stubs/endpoint.stub')
            );

            $this->files->put(app_path($namespace . '/' . $className . '.php'), $stub);

            $this->info('Migration created successfully! Add some fields!');
        }

    }


}
