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
     * @var array
     */
    public $filters = array();

    /**
     * @var array
     */
    protected $sorts = array();

    /**
     * If set, this column tells the data table how each row can be identified
     *
     * @var array
     */
    protected $identifierColumn;

    /**
     * Sets a column name which identifies every row in the grid.
     *
     * @param  string              $column
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
     * Adds filters to the data source for filtering results
     *
     * @param array $filters
     */
    public function setFilters(array $filters)
    {
        foreach ($filters as $filter) {
            $operator = isset($filter['operator']) ? $filter['operator'] : null;
            $this->addFilter($filter['field'], $filter['data'], $operator);
        }
    }

    /**
     * Adds a filter to the data source for filtering the results
     *
     * @param string $field
     * @param string $data
     * @param string $operator
     */
    public function addFilter($field, $data, $operator = null)
    {
        $this->filters[] = array(
            'field' => $field,
            'data' => $data,
            'operator' => 'OR' === $operator ? $operator : 'AND'
        );
    }

    /**
     * Adds sorts to the data source for sorting the results
     *
     * @param array $sorts
     */
    public function setSorts(array $sorts)
    {
        foreach ($sorts as $sort) {
            $this->addFilter($sort['field'], $sort['direction']);
        }
    }

    /**
     * Returns the fields to be sorted on.
     *
     * @return array
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * Adds a sort to the data source for sorting the results
     *
     * @param string $field
     * @param string $direction ASC or DESC
     */
    public function addSort($field, $direction)
    {
        $this->sorts[] = array(
            'field' => $field,
            'direction' => $direction
        );
    }

}
