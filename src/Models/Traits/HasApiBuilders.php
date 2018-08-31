<?php

namespace Petrelli\EloquentConsumer\Models\Traits;

use Petrelli\EloquentConsumer\Builders\ModelBuilder;
use Petrelli\EloquentConsumer\Builders\QueryBuilder;


trait HasApiBuilders
{


    /**
     *
     * Endpoint class used by this model
     *
     * @var string
     *
     */
    protected $endpointClass;


    /**
     *
     * Endpoint assigned to this model
     *
     * @var object
     *
     */
    protected $endpoint;


    /**
     *
     * Endpoint URL's
     *
     * @var array
     *
     */
    protected $endpoints = [];


    /**
     *
     * The array of default scopes on the model.
     *
     * @var array
     *
     */
    protected static $defaultScopes = [];



    /**
     *
     * Create a new query for this model.
     *
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public static function query()
    {
        return (new static)->newQuery();
    }


    /**
     *
     * Create a new query and apply 'with' method
     *
     * @param  array|string $relations
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public static function with($relations)
    {
        return (new static)->newQuery()->with(
            is_string($relations) ? func_get_args() : $relations
        );
    }


    /**
     *
     * Create a new query and apply 'search' method
     *
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public static function search($value)
    {
        return (new static)->newQuery()->search($value);
    }


    /**
     *
     * Get a new query builder for this model.
     * It applies default scopes if any was registered.
     *
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public function newQuery()
    {
        return $this->registerDefaultScopes($this->newQueryWithoutScopes());
    }


    /**
     *
     * Register the global scopes for the passed builder instance.
     *
     * @param  Petrelli\EloquentConsumer\ModelBuilder $builder
     *
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public function registerDefaultScopes($builder)
    {

        foreach ($this->getDefaultScopes() as $name => $parameters) {
            if (empty($parameters)) {
                $builder->$name();
            } else {
                $builder->$name($parameters);
            }
        }

        return $builder;

    }


    /**
     *
     * Get the default scopes for this class.
     *
     * @return array
     *
     */
    public function getDefaultScopes()
    {

        return static::$defaultScopes;

    }



    /**
     *
     * Get a new model builder that doesn't have any global scopes.
     *
     * Inside it contains a lower level Query Builder in charge of actually
     * executing all API calls. The Model Builder is a higher level helper to provide
     * an easy way of build API calls.
     *
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public function newQueryWithoutScopes()
    {

        $builder = $this->newApiModelBuilder($this->newApiQueryBuilder());

        // Once we have the query builders, we will set the model instances so the
        // builder can easily access any information it may need from the model
        // while it is constructing and executing various queries against it.
        return $builder->setModel($this)
                    ->with($this->with);

    }


    /**
     *
     * Returns a new Model Builder.
     *
     * Please overload this function if you need to use a custom builder
     *
     * @param  Petrelli\EloquentConsumer\QueryBuilder $query
     * @return Petrelli\EloquentConsumer\ModelBuilder
     *
     */
    public function newApiModelBuilder($query)
    {

        return new ModelBuilder($query);

    }


    /**
     *
     * Returns a new Query Builder.
     *
     * Please overload this function if you need to use a custom builder
     *
     * @return Petrelli\EloquentConsumer\QueryBuilder
     *
     */
    protected function newApiQueryBuilder()
    {

        $connection = $this->getConnection();
        $grammar    = $this->getGrammar();

        return new QueryBuilder($connection, $grammar);

    }


    /**
     *
     * Returns the model's endpoint connection
     *
     * @return Petrelli\EloquentConsumer\Connections\BaseConnection
     *
     */
    public function getConnection()
    {

        return $this->getEndpoint()->getConnection();

    }


    /**
     *
     * Returns the model's endpoint grammar
     *
     * @return Petrelli\EloquentConsumer\Grammar\BaseGrammar
     *
     */
    public function getGrammar()
    {

        return $this->getEndpoint()->getGrammar();

    }


    /**
     *
     * Returns the model's endpoint instance
     *
     * @return Petrelli\EloquentConsumer\Connections\BaseConnection
     *
     */
    public function getEndpoint()
    {

        return $this->endpoint ?? $this->newEndpoint();

    }


    /**
     *
     * Returns an endpoint instance
     *
     * @return Petrelli\EloquentConsumer\Endpoints\BaseEndpoint
     *
     */
    public function newEndpoint()
    {

        $endpoint = $this->endpointClass ?? config('eloquent-consumer.endpoints.default_endpoint_class');

        if ($endpoint) {

            $this->endpoint = new $endpoint($this->endpoints);

            return $this->endpoint;

        } else {

            throw new \Exception('Please define an endpoint for this model, or define a default one at the eloquent-consumer configuration file');

        }

    }


}

