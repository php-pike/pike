<?php

namespace Pike\DataTable\Adapter;

class ParameterBag implements \IteratorAggregate
{

    /**
     * Allowed parameters
     * 
     * @var array 
     */
    protected $keys = array();

    /**
     * Parameters
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = array())
    {
        foreach ($parameters as $parameter) {
            $this->add($parameter);
        }
    }

    public function add($parameter)
    {
        $this->parameters[] = $parameter;
    }

    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \Pike\Exception('Parameter with key "' . $key . '" not found');
        }

        return $this->parameters[$key];
    }

    public function all()
    {
        return $this->parameters;
    }

    public function set($parameters)
    {
        $this->parameters = $parameters;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->parameters);
    }

    public function keys()
    {
        return array_keys($this->parameters);
    }

    public function clear()
    {
        return $this->parameters = array();
    }

    /**
     * Returns an iterator for flashes.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

}