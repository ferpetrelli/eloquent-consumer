<?php


namespace Petrelli\EloquentConsumer\Connections;

use Petrelli\EloquentConsumer\Consumers\ConsumerInterface;


interface ConnectionInterface
{


    public function __construct($defaultTTL, $transformerClass, ConsumerInterface $client);

    /**
     *
     * Run a GET call against the API.
     *
     * @param  array  $params
     * @return object
     *
     */
    public function get($endpoint, $params);


    /**
     *
     * Run a POST call against the API.
     *
     * @param  array  $params
     * @return object
     *
     */
    public function post($endpoint, $params);


    /**
     *
     * Execute a general call to the API consumer
     *
     * @param  array  $params
     * @return object
     *
     */
    public function execute($verb = 'GET', $endpoint = null, $params = []);


}
