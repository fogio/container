<?php

namespace Fogio\Container;

trait ContainerTrait
{
    protected $_define = [];

    public function __invoke(array $definitions)
    {
        return $this->define($definitions);
    }

    public function __isset($name)
    {
        $this->_init;
        $this->_init = true;
        

        if (isset($this->_define[$name])) {  // dynamic definition
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

        if (isset($this->_define[$name])) { // dynamic definition

            $definition = $this->_define[$name];
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

        if (isset($this->_define['_factory'])) {
            $service = call_user_func($this->_define['_factory'], $service, $name, $this);
        } elseif (method_exists($this, '__factory')) {
            $service = call_user_func([$this, '__factory'], $service, $name);
        }

        return $service;
    }

    public function __call($name, $args)
    {
        if (isset($this->_define[':' . $name])) {
            array_unshift($args, $this);
            return call_user_func_array($this->_define[':' . $name], $args);
        }

        $service = $this->$name;
//        if (!$service instanceof InvokableInterface) {
//            throw new \LogicException('Service `$name` does not implement `InvokableInterface`');
//        }
        return call_user_func_array([$service, 'invoke'], $args);
    }

    public function define(array $definitions)
    {
        $this->_define = array_merge($this->_define, $definitions);

        return $this;
    }

    public function __init()
    {
        $this->_init = true;
    }

    public function getIterator()
    {
        $services = array_keys($this->_define);
        
        if (in_array('_factory', $services)) {
            unset($services[array_search('_factory', $services)]);
        }

        return new \ArrayIterator($services);
    }
}
