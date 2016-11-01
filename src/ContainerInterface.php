<?php

namespace Fogio\Container;

interface ContainerInterface extends \IteratorAggregate
{
    /**
     * Register services.
     *
     * @param array $definitions Definitions where key is a service name and value a dead object
     */
    public function __invoke(array $definitions);

    /**
     * Is service registred.
     *
     * @param string $name Service name
     */
    public function __isset($name);

    /**
     * Get a service.
     *
     * Priority:
     * 1. Dynamic by __invoke or offsetSet
     * 2. Static by `_$name` function in container
     * Static definition can by overwrited
     *
     * @param string $name Service name
     *
     * @return object Service
     */
    public function __get($name);
}
