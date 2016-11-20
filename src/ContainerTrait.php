<?php

namespace Fogio\Container;

trait ContainerTrait
{
    protected $_containerServicesDefinitions = array();

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
                $this->$name = $service = new $definition(); // defined as string are shared
            } elseif ($definition instanceof Closure) {
                $service = call_user_func($definition, $this);
            } elseif (is_object($definition)) {
                $service = $definition;
            }
        } elseif (method_exists($this, '_'.$name)) { // static definition
            $service = $this->{"_$name"}();
        }

        if (isset($this->_containerServicesDefinitions['_factory'])) {
            $service = call_user_func($this->_containerServicesDefinitions['_factory'], $service, $name, $this);
        } elseif (method_exists($this, '_factory')) {
            $service = call_user_func([$this, '_factory'], $service, $name);
        }

        return $service;
    }

    public function __call($name, $args)
    {
        return call_user_func([$this->$name, 'invoke'], $args);
    }

    public function __init()
    {
        $this->_init = true;
    }

    public function getIterator()
    {
        $services = array_keys($this->_containerServicesDefinitions);
        
        if (in_array('_factory', $services)) {
            unset($services[array_search('_factory', $services)]);
        }

        return ArrayIterator($services);
    }
}
