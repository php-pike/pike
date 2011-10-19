<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Abstract version of the data source. In his child is defined and retrieves data from
 * the specified data source for example Zend_Db, Doctrine2, etc.
 */
class Pike_Grid_DataSource_Abstract
{
    /**
     * The columns container
     *
     * @var Pike_Grid_DataSource_Columns
     */
    public $columns;

    /**
     * If set, this column tells jqGrid how each row can be identified
     *
     * @var array
     */
    protected $_identifierColumn;

    /**
     * Event that fires on filtering
     *
     * @var Closesure
     */
    protected $_onFilter;

    /**
     * Event that fires on ordering the grid
     *
     * @var type Closure
     */
    protected $_onOrder;

    /**
     * Posted jqGrid params
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Limit per page
     *
     * @var integer
     */
    protected $_limitPerPage = 50;

    /**
     * Array container where the actual grid data will be loaded in
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Closure for auto escaping column strings in the grid result
     *
     * @var closure
     */
    protected $_autoEscapeClosure;

    /**
     * Columns that must not be escaped by the auto escape closure
     *
     * @var array
     */
    protected $_excludedColumnsFromEscaping = array();

    /**
     * Constructor
     *
     * @param mixed $source
     */
    public function __construct()
    {
        $this->setAutoEscapeClosure(function($string) {
            return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
        });
    }

    /**
     * Sets a column name which identifies every row in the grid.
     *
     * @param  string $column
     * @return Pike_Grid_DataSource_Abstract
     */
    public function setIdentifierColumn($column)
    {
        if (isset($this->columns[$column])) {
            $this->_identifierColumn = $this->columns[$column];
        } else {
            throw new Pike_Exception('Cannot set identifier to a unknown column "' . $column . '"');
        }

        return $this;
    }

    /**
     * Sets the closure for auto escaping column strings in the grid result
     *
     * @param  Closure $closure
     * @return Pike_Grid_DataSource_Abstract
     */
    public function setAutoEscapeClosure(Closure $closure)
    {
        $this->_autoEscapeClosure = $closure;
        return $this;
    }

    /**
     * Exludes columns from escaping
     *
     * The specified columns will not be escaped by the auto escape closure.
     * This is relevant for columns that contain an image for example. Make sure that you do
     * escaping for the content inside that column by yourself, otherwise you'll be vulnerable
     * for XSS attacks!
     *
     * @param array $columns
     */
    public function excludeColumnsFromEscaping(array $columns)
    {
        foreach ($columns as $column) {
            $this->_excludedColumnsFromEscaping[] = $column;
        }

        return $this;
    }

    /**
     * Sets the parameters which probably come from jQuery
     *
     * @param  array $params
     * @return Pike_Grid_DataSource_Abstract
     */
    public function setParameters(array $params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Sets the results to display per page
     *
     * @param  integer $number
     * @return Pike_Grid_DataSource_Abstract
     */
    public function setResultsPerPage($number)
    {
        $this->_limitPerPage = (int) $number;
        return $this;
    }

    /**
     * Renders a single row and adds it to the internal data array
     *
     * @param array $row
     * @param array $excludeColumns
     * @return void
     */
    protected function _renderRow($row, $excludeColumns = array())
    {
        foreach ($this->columns as $index => $column) {
            if (array_key_exists('data', $column)) {
                if (is_callable($column['data'], false, $method)) {
                    $row[$index] = $this->_escape(call_user_func($column['data'], $row), $column['name']);
                } else {
                    // Replace all column tokens that are possibly available in the column data
                    array_walk($row, function($value, $key) use (&$column) {
                        $column['data'] = str_replace('{' . strtolower($key) . '}', $value, $column['data']);
                    });

                    $row[$index] = $this->_escape($column['data'], $column['name']);
                }
            } elseif (array_key_exists($index, $row)) {
                continue;
            } else {
                throw new Pike_Exception(sprintf('Failed rendering data for column "%s"', $index));
            }
        }

        // Sort the row data specified by the column positions
        $columns = $this->columns;

        uksort($row, function($a, $b) use ($columns) {
            if($columns[$a]['position'] > $columns[$b]['position']) {
                return 1;
            } elseif($columns[$a]['position'] < $columns[$b]['position']) {
                return -1;
            } else {
                return 0;
            }
        });

        $record = array();

        $rowColumns = $row;
        foreach ($excludeColumns as $excludeColumn) {
            $rowColumns[$excludeColumn] = null;
        }

        $record['cell'] = array_values($rowColumns);

        if (null !== $this->_identifierColumn) {
            $record['id'] = $this->_identifierColumn['data']($row);
        }

        $this->_data['rows'][] = $record;
    }

    /**
     * Escapes a string if the auto escape closure is defined
     *
     * @param  string $string
     * @param  string $columnName
     * @return string
     */
    protected function _escape($string, $columName = null)
    {
        if (null !== $this->_autoEscapeClosure) {
            if (null === $columName || !in_array($columName, $this->_excludedColumnsFromEscaping)) {
                $autoEscapeClosure = $this->_autoEscapeClosure;
                $string = $autoEscapeClosure($string);
            }
        }

        return $string;
    }
}