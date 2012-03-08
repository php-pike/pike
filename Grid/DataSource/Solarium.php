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
 * Data source for Solarium queries. You can use this data source with
 * Pike_Grid which will both create all neccasary javascript and JSON for drawing
 * a grid with JQgrid.
 *
 * Dependecies: jqGrid, Zend Framework, Solarium 2.3+
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Grid_DataSource_Solarium
    extends Pike_Grid_DataSource_Abstract
    implements Pike_Grid_DataSource_Interface
{
    /**
     * @var Solarium_Query_Select
     */
    protected $_query;

    /**
     * @var Solarium_Client
     */
    protected $_client;

    /**
     * Multi value field delimiter
     *
     * @var string
     */
    public $multiValueFieldDelimiter = ', ';

    /**
     * Constructor
     *
     * @param Solarium_Client
     * @param Solarium_Query
     */
    public function __construct(Solarium_Client $client, Solarium_Query_Select $query)
    {
        parent::__construct();

        $this->_client = $client;
        $this->_query = $query;

        $this->_setColumns();
        $this->_initEvents();
    }

    /**
     * Returns the Solarium_Query instance
     *
     * @return Solarium_Query
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
        $onOrder = function(array $params, Pike_Grid_DataSource_Solarium $dataSource) {
            $sidx = $params['sidx'];

            switch (strtoupper($params['sord'])) {
                case 'DESC':
                    $sord = Solarium_Query_Select::SORT_DESC;
                    break;
                default:
                    $sord = Solarium_Query_Select::SORT_ASC;
                    break;
            }

            $query = $dataSource->getQuery();
            $query->clearSorts();
            $query->addSort($sidx, $sord);
        };

        $this->_onOrder = $onOrder;

        $onFilter = function(array $params, Pike_Grid_DataSource_Solarium $dataSource) {
            $solariumQueryHelper = new Solarium_Query_Helper();
            $filters = json_decode($params['filters']);

            $query = $dataSource->getQuery();
            $queryString = '';

            foreach ($filters->rules as $field) {
                if ('' != $field->data) {
                    $queryString .= $field->field . ':'
                        . $solariumQueryHelper->escapeTerm($field->data) . ' AND ';
                }
            }

            if ('' != $queryString) {
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
    }

    /**
     * @return Pike_Grid_DataSource_Solarium
     */
    public function setEventSort(Closure $function)
    {
        $this->_onOrder = $function;

        return $this;
    }

    /**
     * @return Pike_Grid_DataSource_Solarium
     */
    public function setEventFilter(Closure $function)
    {
        $this->_onFilter = $function;

        return $this;
    }

    /**
     * Returns the default sorting if defined in the original query. Defining default sorting
     * is done outside the data source where the query object is initialized.
     *
     * @return array|null
     */
    public function getDefaultSorting()
    {
        $sort = null;

        $sorts = $this->_query->getSorts();
        if (null !== $sorts) {
            foreach ($sorts as $index => $direction) {
                $sort = array('index' => $index, 'direction' => $direction);

                // Ignore multiple sorts because jqGrid only supports single column sorting
                break;
            }
        }

        return $sort;
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
     * @param boolean $debug          If true, debug information is displayed. If "request", only
     *                                request information as URI and parameters is displayed.
     * @return string JSON data
     */
    public function getJson($encode = true, array $excludeColumns = array(), $debug = false)
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

        if (true === $debug) {
            /* @var $debugComponent Solarium_Query_Select_Component_Debug */
            $debugComponent = $this->_query->getDebug();
        }

        /* @var $result Solarium_Result_Select */
        $result = $this->_client->select($this->_query);

        if (false !== $debug) {
            $requestOnly = 'request' === $debug ? true : false;
            echo self::debug($this->_client, $result, $requestOnly);
            return;
        }

        $count = $result->getNumFound();

        $this->_data = array();
        $this->_data['page'] = (int) $this->_params['page'];
        $this->_data['total'] = ceil($count / $this->_limitPerPage);
        $this->_data['records'] = $count;
        $this->_data['queryTime'] = $result->getQueryTime(); // mili seconds
        $this->_data['queryTimeSec'] = round($result->getQueryTime() / 1000, 4); // seconds
        $this->_data['rows'] = array();

        $this->_renderResult($result, $excludeColumns);

        return $encode === true ? json_encode($this->_data) : $this->_data;
    }

    /**
     * Renders the result
     *
     * @param Solarium_Result_Select $result
     * @param array                  $excludecolumns
     */
    protected function _renderResult(Solarium_Result_Select $result, array $excludeColumns = array())
    {
        if ($result) {
            /**
             * Solr only returns fields for each document that are not empty unlike a database.
             * To still get the database behaviour we take the specified columns as base and fill
             * it with values that the document contains.
             */
            $columnNames = array_keys($this->columns->getAll());
            $columns = array();
            foreach ($columnNames as $columnName) {
                $columns[$columnName] = null;
            }

            foreach ($result as $document) {
                $data = $columns;

                foreach ($document as $field => $value) {
                    // Convert multi value fields to a string with the specified delimiter
                    if (is_array($value)) {
                        $value = implode($this->multiValueFieldDelimiter, $value);
                    }

                    $data[$field] = $value;
                }

                $this->_renderRow($data, $excludeColumns);
            }
        }
    }

    /**
     * Shows debug information about a returned result for a query
     *
     * @param $client      Solarium_Client
     * @param $result      Solarium_Result_Select
     * @param $requestOnly If true, only request information is displayed
     */
    public static function debug(Solarium_Client $client, Solarium_Result_Select $result,
        $requestOnly = false)
    {
        $output = '';
        $output .= '<h1>Debug data</h1>';

        $requestOutput = '';
        $request = $client->createRequest($result->getQuery());

        $requestOutput .= '<h2>Request</h2>';
        $requestOutput .= '<h3>URI</h3>';
        $requestOutput .= $request->getUri() . '<br /><br />';
        $requestOutput .= '<h3>URI (decoded)</h3>';
        $requestOutput .= urldecode($request->getUri()) . '<br /><br />';
        $requestOutput .= '<h3>Params</h3>';
        $requestOutput .= Pike_Debug::dump($request->getParams(), 10, null, false) . '<br /><br />';
        $requestOutput .= '<h3>Raw POST data</h3>';
        $requestOutput .= $request->getRawData() . '<br /><br />';

        if (true === $requestOnly) {
            return $requestOutput;
        }

        $debugResult = $result->getDebug();
        if (null === $debugResult) {
            throw new Pike_Exception('No debug result available');
        }

        $output .= $requestOutput;

        $output .= '<hr />';

        $output .= 'Querystring: ' . $debugResult->getQueryString() . '<br />';
        $output .= 'Parsed query: ' . $debugResult->getParsedQuery() . '<br />';
        $output .= 'Query parser: ' . $debugResult->getQueryParser() . '<br />';
        $output .= 'Other query: ' . $debugResult->getOtherQuery() . '<br />';

        $output .= '<br /><br />';

        $output .= '<h2>Explain data</h2>';
        foreach ($debugResult->getExplain() as $key => $explanation) {
            $output .= '<h3>Document key: ' . $key . '</h3>';
            $output .= 'Value: ' . $explanation->getValue() . '<br />';
            $output .= 'Match: ' . (($explanation->getMatch() == true) ? 'true' : 'false') . '<br />';
            $output .= 'Description: ' . $explanation->getDescription() . '<br />';
            $output .= '<h4>Details</h4>';
            foreach ($explanation AS $detail) {
                $output .= 'Value: ' . $detail->getValue() . '<br />';
                $output .= 'Match: ' . (($detail->getMatch() == true) ? 'true' : 'false') . '<br />';
                $output .= 'Description: ' . $detail->getDescription() . '<br />';
                $output .= '<hr />';
            }
        }

        $output .= '<br /><br />';

        $output .= '<h2>ExplainOther data</h2>';
        foreach ($debugResult->getExplainOther() as $key => $explanation) {
            $output .= '<h3>Document key: ' . $key . '</h3>';
            $output .= 'Value: ' . $explanation->getValue() . '<br />';
            $output .= 'Match: ' . (($explanation->getMatch() == true) ? 'true' : 'false') . '<br />';
            $output .= 'Description: ' . $explanation->getDescription() . '<br />';
            $output .= '<h4>Details</h4>';
            foreach ($explanation AS $detail) {
                $output .= 'Value: ' . $detail->getValue() . '<br />';
                $output .= 'Match: ' . (($detail->getMatch() == true) ? 'true' : 'false') . '<br />';
                $output .= 'Description: ' . $detail->getDescription() . '<br />';
                $output .= '<hr />';
            }
        }

        $output .= '<br /><br />';

        $output .= '<h2>Timings (in ms)</h2>';
        $output .= 'Total time: ' . $debugResult->getTiming()->getTime() . '<br />';
        $output .= '<h3>Phases</h3>';
        foreach ($debugResult->getTiming()->getPhases() as $phaseName => $phaseData) {
            $output .= '<h4>' . $phaseName . '</h4>';
            foreach ($phaseData as $class => $time) {
                $output .= $class . ': ' . $time . '<br />';
            }
            $output .= '<hr />';
        }

        return $output;
    }
}