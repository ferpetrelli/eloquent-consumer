<?php


namespace Petrelli\EloquentConsumer\Transformers;

use Petrelli\EloquentConsumer\Transformers\TransformerInterface;


class BaseTransformer implements TransformerInterface
{

    protected $response;

    /**
     *
     * Create a new transformer instance.
     *
     * @param  $endpoint
     * @return void
     *
     */
    public function __construct($response)
    {

        $this->response = $response;

    }


    public function transform()
    {

        return $this->response;

    }


}
