<?php

namespace Pike\DataTable\DataSource;

class ColumnBag implements \IteratorAggregate
{

    /**
     * Columns
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Constructor.
     *
     * @param array $columns
     */
    public function __construct(array $columns = array())
    {
        foreach ($columns as $column) {
            $this->add($column);
        }
    }

    public function add($column)
    {
        $this->columns[] = $column;
    }

    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \Pike\Exception('Ćolumn with key "' . $key . '" not found');
        }

        return $this->columns[$key];
    }

    public function all()
    {
        return $this->columns;
    }

    public function set($columns)
    {
        $this->columns = $columns;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->columns);
    }

    public function keys()
    {
        return array_keys($this->columns);
    }

    public function clear()
    {
        return $this->columns = array();
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