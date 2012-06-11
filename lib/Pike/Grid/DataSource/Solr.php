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

/**
 * Data source for Doctrine queries and entities. You can use this data source with
 * Pike_Grid which will both create all neccasary javascript and JSON for drawing
 * a grid with JQgrid.
 *
 * Dependecies: jqGrid, Pecl-Solr, Zend Framework
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Grid_DataSource_Solr extends Pike_Grid_DataSource_Abstract implements Pike_Grid_DataSource_Interface
{

    /**
     * @var $_query SolrQuery
     */
    protected $_query;

    /**
     *
     * @var SolrClient 
     */
    protected $_client;

    /**
     * Constructor
     *
     * @param SolrClient
     */
    public function __construct(SolrClient $client, SolrQuery $query)
    {
        parent::__construct();

        $this->_client = $client;
        $this->_query = $query;

        $this->_setColumns();
        $this->_initEvents();
    }

    /**
     * Obtain the Solr query, mostly used for/in events
     * 
     * @return SolrQuery
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Initializes default behavior for sorting, filtering, etc.
     *
     * @return void
     */
    private function _initEvents()
    {
        $onOrder = function(array $params, Pike_Grid_DataSource_Interface $dataSource) {
                    $sidx = $params['sidx'];
                    switch (strtoupper($params['sord'])) {
                        case 'ASC' :
                            $sord = SolrQuery::ORDER_ASC;
                            break;
                        case 'DESC' :
                            $sord = SolrQuery::ORDER_DESC;
                            break;
                    }

                    $query = $dataSource->getQuery();
                    $query->addSortField($sidx, $sord);
                };

        $this->_onOrder = $onOrder;

        $onFilter = function(array $params, Pike_Grid_DataSource_Interface $dataSource) {
                    $filters = json_decode($params['filters']);

                    $query = $dataSource->getQuery();
                    $queryString = '';

                    foreach ($filters->rules as $field) {
                        if(!empty($field->data)) {
                            $queryString .= $field->field . ':' . SolrUtils::escapeQueryChars($field->data) . ' AND ';
                        }
                    }

                    if(strlen($queryString) > 0) {
                        $queryString = substr($queryString, 0, strlen($queryString) - 4);
                        $query->setQuery($queryString);
                    }
                };

        $this->_onFilter = $onFilter;
    }

    /**
     * Looks up in the AST what select expression we use and analyses which
     * fields are used. This is passed thru the datagrid for displaying fieldnames.
     *
     * @return array
     */
    private function _setColumns()
    {
        $this->columns = new Pike_Grid_DataSource_Columns();

        foreach ($this->_query->getFields() as $field) {
            $this->columns->add($field, null, $field);
        }

        //not finished
    }

    /**
     *
     * @return fluent-interface
     */
    public function setEventSort(Closure $function)
    {
        $this->_onOrder = $function;

        return $this;
    }

    /**
     *
     * @return fluent-interface
     */
    public function setEventFilter(Closure $function)
    {
        $this->_onFilter = $function;

        return $this;
    }

    /**
     * Looks if there is default sorting defined in the original query by asking the AST. Defining
     * default sorting is done outside the data source where query or querybuilder object is defined.
     *
     * @return array
     */
    public function getDefaultSorting()
    {
        if (null !== ($sortFields = $this->_query->getSortFields())) {
            return array('index' => 'dont know yet', 'direction' => 'dontknow');
        } else {
            return null;
        }
    }

    /**
     * Returns a JSON string useable for JQuery Grid. This grids interprets this
     * data and is able to draw a grid.
     *
     * @param boolean $encode         If data must be JSON encoded. If false, you must encode the returned
     *                                data yourself through a JSON encode function.
     * @param array   $excludeColumns If you have columns in your query with the only purpose of
     *                                suplementing data for the construction of other columns and
     *                                you also probably set the column as hidden and you don't need
     *                                their value on the client side for manipulation, then you can
     *                                exclude the columns here to gain some performance.
     * @return string JSON data
     */
    public function getJson($encode = true, array $excludeColumns = array())
    {
        $offset = $this->_limitPerPage * ($this->_params['page'] - 1);

        $this->_query->setStart($offset);
        $this->_query->setRows($this->_limitPerPage);

        /**
         * Run  events
         */
        if (array_key_exists('sidx', $this->_params) && strlen($this->_params['sidx']) > 0) {
            $onSort = $this->_onOrder;
            $onSort($this->_params, $this);
        }

        if (array_key_exists('filters', $this->_params) && (array_key_exists('_search', $this->_params)
                && $this->_params['_search'] == true)) {

            $onFilter = $this->_onFilter;
            $onFilter($this->_params, $this);
        }

        $response = $this->_client->query($this->_query);
        $resultResponse = $response->getResponse()->response;

        $count = $resultResponse->numFound;
        $result = $resultResponse->docs;

        $this->_data = array();
        $this->_data['page'] = (int) $this->_params['page'];
        $this->_data['total'] = ceil($count / $this->_limitPerPage);
        $this->_data['records'] = $count;
        $this->_data['rows'] = array();

        if (false !== $result) {
            foreach ($result as $row) {
                $data = array();

                /**
                 * converting is neccasary because of the abstract layer which does 
                 * pure array stuff 
                 */
                $props = $row->getPropertyNames();
                foreach ($props as $prop) {
                    $prop = trim($prop);
                    $data[$prop] = $row[$prop];
                }

                $this->_renderRow($data, $excludeColumns);
            }
        }

        return $encode === true ? json_encode($this->_data) : $this->_data;
    }

}