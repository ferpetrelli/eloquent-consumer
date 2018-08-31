<?php


if (! function_exists('collectApi')) {

    /**
     * Create a new ApiCollection. It differ from Laravel's collection
     * on the extra added metadata functionality
     *
     * @param  mixed  $value
     * @return \Petrelli\EloquentConsumer\ApiCollection
     */
    function collectApi($value = null)
    {

        return new \Petrelli\EloquentConsumer\ApiCollection($value);

    }

}


?>
