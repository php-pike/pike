<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (Platina Designs) and Kees Schepers (SkyConcepts)
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
 * Abstract version of the datasource. In his child is defined and retrieves data from
 * the specified datasource for example Zend_Db, Doctrine2, etc.
 */
class Pike_Grid_Datasource_Abstract
{

    /**
     *
     * Event that fires on filtering
     *
     * @var Closesure
     */
    protected $onFilter;
    /**
     *
     * Event that fires on ordering the grid
     *
     * @var type Closure
     */
    protected $onOrder;
    /**
     * Posted jqGrid params
     *
     * @var array
     */
    protected $_params = array();
    protected $_limitPerPage = 50;
    /**
     * Array container where the actual grid data will be loaded in.
     *
     * @var array
     */
    protected $_data = array();
    /**
     *
     * The columns 'container'
     *
     * @var Pike_Grid_Datasource_Columns
     */
    public $columns;

    /**
     *
     * Set the parameters which proberly come from jquery.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        $this->_params = $params;
    }

    public function setResultsPerPage($num)
    {
        $this->_limitPerPage = (int) $num;
    }
}