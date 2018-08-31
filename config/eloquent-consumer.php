<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Default Endpoint configuration
    |--------------------------------------------------------------------------
    |
    | It will be used by our API consumer as a base to build
    | and process queries.
    |
    */

    'endpoints' => [

        'base_uri' => '',

        'default_grammar' => \Petrelli\EloquentConsumer\Grammar\BaseGrammar::class,

        'default_connection' => \Petrelli\EloquentConsumer\Connections\BaseConnection::class,

        'default_endpoint_class' => '',


        // Caching

        'cache_default_ttl' => env('API_CACHE_DEFAULT_TTL', 3600),

        'cache_enabled' => env('API_CACHE_ENABLED', false),

        'cache_version' => env('API_CACHE_VERSION', 1),

    ],



    /*
    |--------------------------------------------------------------------------
    | API logger
    |--------------------------------------------------------------------------
    |
    */

    'logger' => env('API_LOGGER', false)


];
