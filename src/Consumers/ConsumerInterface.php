<?php


namespace Petrelli\EloquentConsumer\Consumers;


interface ConsumerInterface
{


    public function request($method, $uri = '', array $options = []);

    public function adaptParameters($params);

    public function headers($params);

    public function __call($name, $args);


}
