<?php

namespace Fogio\Container;

/**
 * @todo lazy services
 */
trait ContainerTrait
{
    protected $_containerServicesDefinitions = array();
    protected $_containerDefaultShared = false;
    protected $_containerFallback;

    public function __invoke(array $definitions)
    {
        $this->_containerServicesDefinitions = array_merge($this->_containerServicesDefinitions, $definitions);

        return $this;
    }

    public function __isset($name)
    {
        $this->_init;
        $this->_init = true;
        

        if (isset($this->_containerServicesDefinitions[$name])) {  // dynamic definition
            return true;
        }

        if (method_exists($this, '_'.$name)) { // static definition
            return true;
        }

        if ($this->_containerFallback) {
            return $this->_containerFallback->$name;
        }

        return false;
    }

    public function __get($name)
    {
        $this->_init;
        $this->_init = true;

        $service = null;

        if (isset($this->_containerServicesDefinitions[$name])) { // dynamic definition

            $definition = $this->_containerServicesDefinitions[$name];

            if (is_string($definition)) {
                $service = new $definition();
            } elseif ($definition instanceof Closure) {
                $service = call_user_func($definition, $this);
            } elseif (is_object($definition)) {
                $service = $definition;
            }
        } elseif (method_exists($this, '_'.$name)) { // static definition
            $service = $this->{"_$name"}();
        } elseif ($this->_containerFallback) { // check fallback container
            return $this->_containerFallback->$name;
        }

        if (isset($this->_containerServicesDefinitions['_factory'])) {
            $service = call_user_func($this->_containerServicesDefinitions['_factory'], $service, $name, $this);
        } elseif (method_exists($this, '_factory')) {
            $service = call_user_func([$this, '_factory'], $service, $name, $this);
        }

        return $service;
    }

    public function __call($name, $args)
    {
        return call_user_func([$this->$name, 'helper'], $args);
    }

    public function __init()
    {
        $this->_init = null;
    }

    public function has($name)
    {
        return isset($this->$name);
    }

    public function get($name)
    {
        return $this->$name;
    }

    public function setDefaultShared($defaultShared)
    {
        $this->_containerDefaultShared = $defaultShared;

        return $this;
    }

    public function setFallbackContainer(ContainerInterface $container)
    {
        $this->_containerFallback = $container;

        return $this;
    }

    public function getIterator()
    {
        $services = array_keys($this->_containerServicesDefinitions);
        if (in_array('_extend', $services)) {
            unset($services[array_search('_extend', $services)]);
        }
        /* @todo add static definitions to $services */
        return ArrayIterator($services);
    }
}
