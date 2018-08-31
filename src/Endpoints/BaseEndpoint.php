<?php


namespace Petrelli\EloquentConsumer\Endpoints;

use Petrelli\EloquentConsumer\Consumers\ConsumerInterface;


class BaseEndpoint
{


    protected $baseUri;


    protected $defaultTTL;


    protected $grammarClass;
    protected $grammar;


    protected $connectionClass;
    protected $connection;


    protected $consumerClass;


    public function __construct($endpoints)
    {

        $this->baseUri = $this->createBaseUri();

        $this->grammar = $this->createGrammar();

        $this->connection = $this->createConnection();

        $this->defaultTTL = $this->defaultTTL ?? config('eloquent-consumer.endpoints.cache_default_ttl');


        /**
         * Endpoints are coming from the model definition
         */
        $this->endpoints = $endpoints;

    }


    public function getBaseUri()
    {

        return $this->baseUri;

    }


    public function getGrammar()
    {

        return $this->grammar;

    }


    public function getConnection()
    {

        return $this->connection;

    }


    public function getTTL()
    {

        return $this->defaultTTL;

    }


    public function getEndpoint($type)
    {

        return $this->endpoints[$type];

    }


    /**
     * Parse API endpoint. Replace brackets {name} with the 'name' attribute value (usually datahub_id)
     *
     * This way you can define an endpoint like:
     * protected $endpoint = '/api/v1/exhibitions/{datahub_id}/artwork/{id}';
     *
     * And the elements will be dinamically replaced with the params values passed
     *
     * @return string
     */
    public function parseEndpoint($type, $params = [])
    {
        return preg_replace_callback('!\{(\w+)\}!', function($matches) use ($params) {
            $name = $matches[1];
            return $params[$name];
        }, $this->getEndpoint($type));
    }


    protected function createBaseUri()
    {

        $baseUri = $this->baseUri ?? config('eloquent-consumer.endpoints.base_uri');

        if ($baseUri) {
            return $baseUri;
        } else {
            throw new \Exception('Please define a baseUri for this endpoint, or define a default one at the eloquent-consumer configuration file');
        }

    }


    protected function createGrammar()
    {

        $grammar = $this->grammarClass ?? config('eloquent-consumer.endpoints.default_grammar');

        if ($grammar && class_exists($grammar)) {
            return new $grammar();
        } else {
            throw new \Exception('Please define a grammar class for this endpoint, or define a default one at the eloquent-consumer configuration file');
        }

    }


    protected function createConnection()
    {

        $connection = $this->connectionClass ?? config('eloquent-consumer.endpoints.default_connection');

        if ($connection && class_exists($connection)) {

            /**
             * You can be explicit to define the consumer, or just
             * use the default binded class (Guzzle consumer)
             *
             */
            if ($this->consumerClass && class_exists($this->consumerClass)) {
                return new $connection($this->defaultTTL, new $this->consumerClass);
            } else {
                return new $connection($this->defaultTTL, app(ConsumerInterface::class, $this->getConsumerOptions()));
            }
        } else {
            throw new \Exception('Please define a connection class for this endpoint, or define a default one at the eloquent-consumer configuration file');
        }

    }


    protected function getConsumerOptions()
    {
        return [
            'base_uri' => $this->getBaseUri()
        ];
    }


}
