<?php


namespace Petrelli\EloquentConsumer\Connections;

use Petrelli\EloquentConsumer\Consumers\ConsumerInterface;


class BaseConnection implements ConnectionInterface
{


    // Consumer that will be used throughout the life of this connection object
    protected $consumer;


    // Query transformer, simple class that just transforms a response into a format
    // we can understand
    protected $transformerClass;


    // Cache key for this connection. Change when you need to reset caching.
    protected $cacheKeyName = 'version-1.0';


    /**
     *
     * Create a new API connection instance.
     *
     * @param  $endpoint
     * @return void
     *
     */
    public function __construct($defaultTTL, $transformerClass = null, ConsumerInterface $consumer)
    {

        $this->consumer = $consumer;
        $this->transformerClass = $transformerClass;

        $this->ttl = $defaultTTL;

    }


    /**
     *
     * Define a custom TTL for this connection instance
     *
     * @param  integer  $ttl
     * @return object
     *
     */
    public function ttl($ttl = null)
    {

        $this->ttl = $ttl;

        return $this;

    }


    /**
     *
     * Run a GET call against the API.
     *
     * @param  array  $params
     * @return object
     *
     */
    public function get($endpoint, $params)
    {

        $response = $this->execute('GET', $endpoint, $params);

        if ($this->transformerClass && class_exists($this->transformerClass)) {
            return (new $this->transformerClass($response))->transform();
        } else {
            return $response;
        }

    }


    /**
     *
     * Run a POST call against the API.
     *
     * @param  array  $params
     * @return object
     *
     */
    public function post($endpoint, $params)
    {

        $response = $this->execute('POST', $endpoint, $params);

        if ($this->transformerClass && class_exists($this->transformerClass)) {
            return (new $this->transformerClass($response))->transform();
        } else {
            return $response;
        }

    }


    /**
     *
     * Execute a general call to the API consumer
     *
     * @param  array  $params
     * @return object
     *
     */
    public function execute($verb = 'GET', $endpoint = null, $params = [])
    {

        // Process parameters and headers
        $processedParams  = $this->prepareParameters($params);
        $processedHeaders = $this->prepareHeaders($params);
        $options = array_merge($processedParams, $processedHeaders);


        // Print logs
        $this->printLog($verb, $endpoint, $options);


        // Perform API request and caching
        if (config('eloquent-consumer.endpoint.cache_enabled')) {
            $cacheKey = $this->buildCacheKey($verb, $endpoint, $options, config('eloquent-consumer.endpoint.cache_version'), $this->cacheKeyName);

            $response =  \Cache::remember($cacheKey, $this->ttl, function () use ($verb, $endpoint, $options) {
                return $this->consumer->request($verb, $endpoint, $options);
            });

            if (isset($response->status) && $response->status != 200) {
                \Cache::forget($cacheKey);
            }

            return $response;
        } else {
            return $this->consumer->request($verb, $endpoint, $options);
        }

    }


    /**
     *
     * Some consumers will need to adapt parameters to work so we call
     * this function on the consumers.
     *
     * For example on the default consumer (Guzzle) parameters should be:
     *
     * ['body' => [ 'par1' => val1, 'par2' => val2 .....]]
     *
     * @param  array $params
     * @return array Adapted parameters
     *
     */
    protected function prepareParameters($params)
    {

        return $this->consumer->adaptParameters($params);

    }


    /**
     *
     * Consumers and/or connections could have a default header transformation
     * so we use this functionality to allow future overload
     *
     * @param  array $params
     * @return array Adapted parameters
     *
     */
    protected function prepareHeaders($params)
    {

        return $this->consumer->headers($params);

    }


    /**
     *
     * Define how to print logs. By default it will use the default
     * Laravel logs functionalities
     *
     */
    protected function printLog($verb, $endpoint, $options)
    {

        if (config('eloquent-consumer.logger')) {

            \Log::info($verb . " ttl = ". $this->ttl . " " . $endpoint);
            \Log::info(print_r($options, true));

        }

    }


    protected function buildCacheKey()
    {

        return json_encode(func_get_args());

    }

}
