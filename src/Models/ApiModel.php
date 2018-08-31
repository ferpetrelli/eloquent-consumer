<?php

namespace Petrelli\EloquentConsumer\Models;

use Jenssegers\Model\Model as JessengersModel;

use Petrelli\EloquentConsumer\ApiCollection;
use Petrelli\EloquentConsumer\Models\Traits\HasApiBuilders;
use Petrelli\EloquentConsumer\Models\Traits\HasRelationships;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Carbon;


abstract class ApiModel extends JessengersModel implements UrlRoutable
{

    use HasApiBuilders, HasRelationships;


    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];


    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new ApiCollection($this->fromJson($value));
            case 'datetime':
                return $this->asDateTime($value);
            default:
                return $value;
        }
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Illuminate\Support\Carbon
     */
    public function asDateTime($value)
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new Carbon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp($value);
        }

        if ($this->isStandardDateFormat($value)) {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return new Carbon($value);
    }

    /**
     * Determine if the given value is a standard date format.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isStandardDateFormat($value)
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }


    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     */
    public function newCollection($models = [])
    {
        if ($models instanceof ApiCollection) {
            return $models;
        } else {
            return new ApiCollection($models);
        }
    }


    /**
     *
     * Implement Basic URLRoutable functions to be able to pass the object to route builders
     *
     */

    public function getRouteKey()
    {
        return $this->getAttribute($this->getRouteKeyName());
    }

    public function getRouteKeyName() {
        return $this->getKeyName();
    }

    public function resolveRouteBinding($value) {
        return $this->entity;
    }

    public function getKeyName()
    {
        return $this->primaryKey;
    }


}
