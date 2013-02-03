<?php

/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
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

namespace Pike\DataTable\DataSource;

/**
 * Abstract version of the data source. In his child is defined and retrieves data from
 * the specified data source for example Zend_Db, Doctrine2, etc.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
abstract class AbstractDataSource implements \Countable
{

    /**
     * The columns container
     *
     * @var ColumnBag
     */
    protected $columnBag;

    /**
     * If set, this column tells the data table how each row can be identified
     *
     * @var array
     */
    protected $identifierColumn;

    /**
     * Event that fires on filtering
     *
     * @var Closesure
     */
    protected $onFilter;

    /**
     * Event that fires on ordering the grid
     *
     * @var type Closure
     */
    protected $onOrder;

    /**
     * Closure for auto escaping column strings in the grid result
     *
     * @var closure
     */
    protected $autoEscapeClosure;

    /**
     * Columns that must not be escaped by the auto escape closure
     *
     * @var array
     */
    protected $excludedColumnsFromEscaping = array();

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
     * @return DataSourceInterface
     */
    public function setIdentifierColumn($column)
    {
        if (isset($this->columns[$column])) {
            $this->identifierColumn = $this->columns[$column];
        } else {
            throw new \Pike\Exception('Cannot set identifier to an unknown column "' . $column . '"');
        }

        return $this;
    }

    /**
     * Sets the closure for auto escaping column strings in the grid result
     *
     * @param  Closure $closure
     * @return DataSourceInterface
     */
    public function setAutoEscapeClosure(\Closure $closure)
    {
        $this->autoEscapeClosure = $closure;
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
            $this->excludedColumnsFromEscaping[] = $column;
        }

        return $this;
    }

    /**
     * Resets the entire list of columns to be excluded from escaping. This will set
     * the datasource to normal behavior.
     *
     * @return DataSourceInterface
     */
    public function resetExcludeColumnsFromEscaping()
    {
        $this->excludedColumnsFromEscaping = array();

        return $this;
    }

    /**
     * Escapes a string if the auto escape closure is defined
     *
     * @param  string $string
     * @param  string $columnName
     * @return string
     */
    protected function escape($string, $columName = null)
    {
        if (null !== $this->autoEscapeClosure) {
            if (null === $columName || !in_array($columName, $this->excludedColumnsFromEscaping)) {
                $autoEscapeClosure = $this->autoEscapeClosure;
                $string = $autoEscapeClosure($string);
            }
        }

        return $string;
    }

}