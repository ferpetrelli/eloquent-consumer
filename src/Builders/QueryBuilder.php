<?php

namespace Petrelli\EloquentConsumer\Builders;


use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Container\Container;


class QueryBuilder
{


    /**
     * The orderings for the query.
     *
     * @var array
     */
    public $orders;


    /**
     * The maximum number of records to return.
     *
     * @var int
     */
    public $limit;


    /**
     * The number of records to skip.
     *
     * @var int
     */
    public $offset;


    /**
     * The current page number
     *
     * @var int
     */
    public $page;


    /**
     * The database query grammar instance.
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    public $grammar;


    /**
     * The Cache TTL for this specific query builder
     *
     * @var array
     */
    public $ttl;


    /**
     * The columns that should be returned.
     *
     * @var array
     */
    public $columns;


    /**
     * The ids of the records that should be returned.
     *
     * @var array
     */
    public $ids = [];


    /**
     * The list of extra fields to be included
     *
     * @var array
     */
    public $include = [];


    /**
     * The where constraints for the query.
     *
     * @var array
     */
    public $wheres = [];


    /**
     * Search constraints for the query.
     *
     * @var string
     */
    public $searchText;


    /**
     * Search parameters for a raw ES query.
     *
     * @var array
     */
    public $searchParameters = [];


    /**
     * Completely raw ES query.
     *
     * @var array
     */
    public $rawQuery = [];


    /**
     * Aggregations parameters for a raw ES query.
     *
     * @var array
     */
    public $aggregationParameters = [];


    /**
     * Pagination data saved after a request
     */
    public $paginationData;


    public function __construct($connection, $grammar = null)
    {
        $this->connection = $connection;
        $this->grammar = $grammar ?: $connection->getQueryGrammar();
    }


    /**
     *
     * Bypass whereNotIn function until it's implemented on the API
     *
     */
    public function whereNotIn($column, $values, $boolean = 'and')
    {

        return $this;

    }


    /**
     *
     * Bypass where function until it's implemented on the API
     *
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {

        return $this;

    }


    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if ($column == 'id') {
            $this->ids($values);
            return $this;
        } else {
            throw new \Exception("whereIn function has been defined only for IDS at the API Query Builder");
        }
    }


    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            $column => ['order' => strtolower($direction) == 'asc' ? 'asc' : 'desc']
        ];

        return $this;
    }


    /**
     * Filter by id's
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function ids($ids = [])
    {
        if (!empty($ids)) {
            $this->ids = $ids;
        }

        return $this;
    }


    /**
     * Include these fields on the resultset
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function include($inclusions = [])
    {
        if (!empty($inclusions)) {
            $this->include = $inclusions;
        }

        return $this;
    }


    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = [], $pageName = 'page', $page = null)
    {
        if (is_null($perPage)) {
            throw new \Exception('You need to pass the amount of elements per page to paginate');
        }

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = $this->forPage($page, $perPage)->get($columns);

        $paginationData = $this->getPaginationData();
        $total = $paginationData ? $paginationData->total : $results->count();

        $data = $results['body']->data;

        return $this->paginator($data, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }


    /**
     * Create a new length-aware paginator instance.
     *
     * @param  \Illuminate\Support\Collection  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int  $currentPage
     * @param  array  $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function paginator($items, $total, $perPage, $currentPage, $options)
    {
        return Container::getInstance()->makeWith(LengthAwarePaginator::class, compact(
            'items', 'total', 'perPage', 'currentPage', 'options'
        ));
    }


    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return \Petrelli\EloquentConsumer\Builders\QueryBuilder
     */
    public function forPage($page, $perPage = 15)
    {
        $this->page = $page;

        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }


    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int  $value
     * @return \Petrelli\EloquentConsumer\Builders\QueryBuilder
     */
    public function skip($value)
    {
        return $this->offset($value);
    }


    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }


    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return \Petrelli\EloquentConsumer\Builders\QueryBuilder
     */
    public function take($value)
    {
        return $this->limit($value);
    }


    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }


    /**
     * Perform a search
     *
     * @param  string  $search
     * @return $this
     */
    public function search($search)
    {
        $this->searchText = empty($search) ? null : $search;

        return $this;
    }


    /**
     * Perform a completely raw ES query
     *
     * @param  array $search
     * @return $this
     */
    public function rawQuery($search)
    {
        $this->rawQuery = array_merge_recursive($this->rawQuery, $search);

        return $this;
    }


    /**
     * Add aggregations to the raw ES search
     *
     * @param  array $aggregations
     * @return $this
     */
    public function aggregations($aggregations)
    {
        $this->aggregationParameters = array_merge_recursive($this->aggregationParameters, $aggregations);

        return $this;
    }


    /**
     * Execute a GET query and setup pagination data
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = [], $endpoint = null)
    {
        return $this->execute($columns, $endpoint, 'GET');
    }


    /**
     * Execute a POST query and setup pagination data
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function post($columns = [], $endpoint = null)
    {
        return $this->execute($columns, $endpoint, 'POST');
    }


    public function execute($columns, $endpoint, $verb = 'GET')
    {

        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        switch($verb) {
            case 'GET':
                $results = $this->runGet($endpoint);
                break;
            case 'POST':
                $results = $this->runPost($endpoint);
                break;

            default:
                throw new \Exception('Verb not defined. Use only GET and POST');
        }


        // If we got anything different than a HIT return the body
        if (isset($results->status) && $results->status != 200) {
            if (isset($results->body)) {
                return $results->body;
            } else {
                return $results;
            }
        }

        $this->columns = $original;

        // If it's a single element return as a collection with 1 element
        if (is_array($results->body->data)) {
            $collection = collectApi($results->body->data);
        } else {
            $collection = collectApi([$results->body->data]);
        }

        $collection->setMetadata([
            'pagination'   => $results->body->pagination ?? null,
            'aggregations' => $results->body->aggregations ?? null,
            'suggestions'  => $results->body->suggest ?? null,
            'response'     => $results,
        ]);

        return $collection;
    }


    public function executeRaw($columns, $endpoint, $verb = 'GET')
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        switch($verb) {
            case 'GET':
                $results = $this->runGet($endpoint);
                break;
            case 'POST':
                $results = $this->runPost($endpoint);
                break;

            default:
                throw new \Exception('Verb not defined. Use only GET and POST');
        }

        if (is_array($results->body)) {
            $collection = collectApi($results->body);
        } else {
            $collection = collectApi([$results->body]);
        }

        $collection->setMetadata([
            'pagination'   => $results->body->pagination ?? null,
            'aggregations' => $results->body->aggregations ?? null,
            'suggestions'  => $results->body->suggest ?? null,
            'response'     => $results,
        ]);

        return $collection;
    }


    /**
     * Execute a POST query and return a raw response
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function postRaw($columns = [], $endpoint = null)
    {
        return $this->executeRaw($columns, $endpoint, 'POST');
    }


    /**
     * Execute a GET query and return a raw response
     *
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getRaw($columns = [], $endpoint = null)
    {
        return $this->executeRaw($columns, $endpoint, 'GET');
    }


    public function getPaginationData() {
        return $this->paginationData;
    }


    /**
     * Build and execute against the API connection a GET call
     *
     * @return array
     */
    public function runGet($endpoint)
    {
        return $this->connection->ttl($this->ttl)->get($endpoint, $this->resolveParameters());
    }


    /**
     * Build and execute against the API connection a POST call
     *
     * @return array
     */
    public function runPost($endpoint)
    {
        return $this->connection->ttl($this->ttl)->post($endpoint, $this->resolveParameters());
    }


    /**
     * Use grammar to generate all parameters from the scopes as an array
     *
     * @return string
     */
    public function resolveParameters()
    {
        return $this->grammar->compileParameters($this);
    }


    /**
     * Set a specific Caching TTL for this request
     *
     * @return array
     */
    public function ttl($ttl = null)
    {
        $this->ttl = $ttl;

        return $this;
    }


}
