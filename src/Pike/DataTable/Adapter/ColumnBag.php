<?php

namespace Pike\DataTable\Adapter;

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

    /**
     * Adds a column to the column bag
     *
     * @param string  $column   The column name
     * @param string  $label    The friendly name used for this column as heading
     * @param string  $field    The field name to be used when sorting is isseud
     * @param integer $position The position number, can be any number
     * @param boolean $display  Show column
     */
    public function add($column, $label = null, $field = null, $position = null,
            $display = true
    ) {
        $label = isset($label) ? $label : $column;
        $field = isset($field) ? $field : $column;
        $position = isset($position) ? $position : count($this->columns) + 1;

        $this->columns[$column] = array(
            'column' => $column,
            'label' => $label,
            'field' => $field,
            'position' => $position,
            'display' => $display
        );
    }

    /**
     * Sets the label for the specified column
     * 
     * @param  string $column
     * @param  string $label
     * @return ColumnBag
     */
    public function setLabel($column, $label)
    {
        $this->get($column);

        $this->columns[$column] = $label;

        return $this;
    }

    /**
     * Returns the label for the specified column
     * 
     * @param  string $column
     * @return string
     */
    public function getLabel($column)
    {
        $column = $this->get($column);

        return $column['label'];
    }

    /**
     * Sets the field for the specified column
     * 
     * @param  string $column
     * @param  string $field
     * @return ColumnBag
     */
    public function setField($column, $field)
    {
        $this->get($column);

        $this->columns[$column] = $field;

        return $this;
    }

    /**
     * Returns the field for the specified column
     * 
     * @param  string $column
     * @return string
     */
    public function getField($column)
    {
        $column = $this->get($column);

        return $column['field'];
    }

    /**
     * Sets the position for the specified column
     * 
     * @param  string  $column
     * @param  integer $position
     * @return ColumnBag
     */
    public function setPosition($column, $position)
    {
        $this->get($column);

        $this->columns[$column] = $position;

        return $this;
    }

    /**
     * Returns the position for the specified column
     * 
     * @param  string $column
     * @return string
     */
    public function getPosition($column)
    {
        $column = $this->get($column);

        return $column['position'];
    }

    /**
     * Sets the show for the specified column
     * 
     * @param  string  $column
     * @param  boolean $field
     * @return ColumnBag
     */
    public function setDisplay($column, $display)
    {
        $this->get($column);

        $this->columns[$column] = $display;

        return $this;
    }

    /**
     * Returns the display for the specified column
     * 
     * @param  string $column
     * @return boolean
     */
    public function getDisplay($column)
    {
        $column = $this->get($column);

        return $column['display'];
    }

    /**
     * Returns the specified column
     * 
     * @param  string $column
     * @return array
     * @throws \Pike\Exception
     */
    public function get($column)
    {
        if (!$this->has($column)) {
            throw new \Pike\Exception('Ćolumn "' . $column . '" not found');
        }

        return $this->columns[$column];
    }

    /**
     * Returns the column for the specified offset
     * 
     * @param  integer $offset
     * @return array
     */
    public function getOffset($offset)
    {
        $this->sort();

        $columns = array_slice($this->columns, $offset, 1);
        if (count($columns) < 1) {
            throw new \Pike\Exception('Ćolumn for offset "' . $offset . '" not found');
        }

        return current($columns);
    }

    /**
     * Returns all columns
     * 
     * @return array
     */
    public function all()
    {
        $this->sort();

        return $this->columns;
    }

    /**
     * Checks if the specified column exists
     * 
     * @param  string $column
     * @return boolean
     */
    public function has($column)
    {
        return array_key_exists($column, $this->columns);
    }

    /**
     * Returns a list of column names
     * 
     * @return array
     */
    public function keys()
    {
        return array_keys($this->columns);
    }

    /**
     * Clears the column bag
     */
    public function clear()
    {
        $this->columns = array();
    }

    /**
     * Returns an iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * Sorts the columns
     */
    protected function sort()
    {
        $visibleColumns = $this->getVisible();

        if (count($visibleColumns) < count($this->columns)) {
            $columns = array();
            foreach ($this->columns as $column) {
                if (true !== $column['display']) {
                    $columns[] = $column;
                }
            }

            foreach ($visibleColumns as $column) {
                $columns[] = $column;
            }
        } else {
            $names = array();
            $positions = array();

            foreach ($this->columns as $name => $column) {
                $names[$name] = $name;
                $positions[$name] = $column['position'];
            }

            array_multisort($names, SORT_ASC, $positions, SORT_ASC, $this->columns);
        }
    }

    /**
     * Returns the visible columns
     * 
     * @return array
     */
    public function getVisible()
    {
        $visible = array();

        foreach ($this->columns as $column) {
            if (true === $column['display']) {
                $visible[] = $column;
            }
        }

        return $visible;
    }

}