<?php

namespace Petrelli\EloquentConsumer;

use Illuminate\Support\ServiceProvider;

use Petrelli\EloquentConsumer\Consumers\ConsumerInterface;
use Petrelli\EloquentConsumer\Consumers\ConsumerGuzzle;
use Petrelli\EloquentConsumer\Commands\MakeEndpoint;


class EloquentConsumerServiceProvider extends ServiceProvider
{


    public function boot()
    {
    	$this->publishConfig();
    }


    public function publishConfig()
    {
		$this->publishes([
	    	__DIR__.'/../config/eloquent-consumer.php' => config_path('eloquent-consumer.php'),
		]);
    }


    public function register()
    {
        $this->app->bind(ConsumerInterface::class, function($app, $options = null) {

            // Add some defaults options for Guzzle if nothing was provided
            if (empty($options)) {
                $options = [
                    'base_uri'   => config('eloquent-consumer.endpoints.base_uri')
                ];
            }

            $options['exceptions'] = false;

            return new ConsumerGuzzle($options);
        });


        $this->commands([
            MakeEndpoint::class
        ]);
    }


}
