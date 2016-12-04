<?php

namespace Fogio\Container;

trait ContainerTrait
{
    protected $_containerServicesDefinitions = [];

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
            } elseif ($definition instanceof \Closure) {
                $service = call_user_func($definition, $this);

            } elseif (is_object($definition)) {
                $service = $definition;
            }
        } elseif (method_exists($this, '_'.$name)) { // static definition
            $service = $this->{"_$name"}();
        }

        if ($name === '_init') {
            return $service;
        }

        if (isset($this->_containerServicesDefinitions['_factory'])) {
            $service = call_user_func($this->_containerServicesDefinitions['_factory'], $service, $name, $this);
        } elseif (method_exists($this, '__factory')) {
            $service = call_user_func([$this, '__factory'], $service, $name);
        }

        return $service;
    }

    public function __call($name, $args)
    {
        $service = $this->$name;
//        if (!$service instanceof InvokableInterface) {
//            throw new \LogicException('Service `$name` does not implement `InvokableInterface`');
//        }
        return call_user_func_array([$service, 'invoke'], $args);
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

        return new \ArrayIterator($services);
    }
}
