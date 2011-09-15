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
 * Datasource for Doctrine queries and entities. You can use this datasource with
 * Pike_Grid which will both create all neccasary javascript and JSON for drawing
 * a grid with JQgrid.
 *
 * Dependecies: jqGrid, Doctrine, Zend Framework
 *
 */
class Pike_Grid_Datasource_Doctrine extends Pike_Grid_Datasource_Abstract implements Pike_Grid_Datasource_Interface
{
    /**
     *
     * @var $_query Doctrine\ORM\Query
     */
    protected $_query;
    
    /**
     * When this column is set it tells jqGrid how to identify each row
     * 
     * @var array
     */
    protected $_identifierColumn;

    public function __construct($source)
    {
        switch($source) {
            case ($source instanceof Doctrine\ORM\QueryBuilder) :
                $this->_query = $source->getQuery();
                break;
            case ($source instanceof Doctrine\ORM\Query) :
                 $this->_query = $source;
                break;
            case ($source instanceof \Doctrine\ORM\EntityRepository) :
                $this->_query = $source->createQueryBuilder('al')->getQuery();
                break;
            default :
                throw new Pike_Exception('Unknown source given, source must either be an entity, query or querybuilder object.');
                break;
        }

        $this->_setColumns();
        $this->_initEvents();

    }
    
    /**
     * Initializes default behavior for sorting, filtering, etc.
     * 
     * @return void
     */
    private function _initEvents() {
        $onOrder = function(array $params, Pike_Grid_Datasource_Interface $datasource) {
            $columns = $datasource->columns->getColumns();
            $sidx = $params['sidx'];
            $sord = (in_array(strtoupper($params['sord']), array('ASC','DESC')) ? strtoupper($params['sord']) : 'ASC');

            return array(
                \Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS => array('Pike_Grid_Datasource_Doctrine_OrderByWalker'),
                'sidx' => $sidx,
                'sord' => $sord,
            );
        };

        $this->onOrder = $onOrder;

        $onFilter = function(array $params, Pike_Grid_Datasource_Interface $datasource) {
            $filters = json_decode($params['filters']);

            return array(
                \Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS => array('Pike_Grid_Datasource_Doctrine_WhereLikeWalker'),
                'operator' => $filters->groupOp,
                'fields' => $filters->rules,
            );
        };

        $this->onFilter = $onFilter;
    }
    
    /**
     *
     * Looks up in the AST what select expression we use and analyses which
     * fields are used. This is passed thru the datagrid for displaying fieldnames.
     *
     *
     * @return array
     */
    private function _setColumns()
    {
        $this->columns = new Pike_Grid_Datasource_Columns();

        $selectClause = $this->_query->getAST()->selectClause;
        if(count($selectClause->selectExpressions) == 0) {
            throw new Pike_Exception('The grid query should contain at least one column, none found.');
        }

        /* @var $selExpr Doctrine\ORM\Query\AST\SelectExpression */
        foreach($selectClause->selectExpressions as $selExpr) {

            /**
             * Some magic needs to happen here. If $selExpr isn't a instanceof
             * Doctrine\ORM\Query\AST\PathExpression then it might be a custom
             * expression like a vendor specific function. We could use the 
             * $selExpr->fieldIdentificationVariable member which is the alias
             * given to the special expression but sorting at this field
             * could cause strange effects.
             * 
             * For instance: SELECT DATE_FORMAT(datefield,"%Y") AS alias FROM sometable
             * 
             * When you say: ORDER BY alias DESC
             * 
             * You could get other results when you do:
             * 
             * ORDER BY sometable.datefield DESC
             * 
             * My idea is to rely on the alias field by default because it would 
             * suite for most queries. If someone would like to retrieve this expression
             * specific info they can add a field filter. $ds->addFieldFilter(new DateFormat_Field_Filter(), 'expression classname');
             * 
             * And info of the field would be extracted from this class, something like that.
             */
            
            $expr = $selExpr->expression;
            
            /* @var $expr Doctrine\ORM\Query\AST\PathExpression */
            if($expr instanceof Doctrine\ORM\Query\AST\PathExpression) {
                $alias = $expr->identificationVariable;
                $name = ($selExpr->fieldIdentificationVariable === null) ? $expr->field : $selExpr->fieldIdentificationVariable;
                $label = ($selExpr->fieldIdentificationVariable === null) ? $name : $selExpr->fieldIdentificationVariable;
                $index = (strlen($alias) > 0 ? ($alias . '.') : '') . $expr->field;
            } else {
                $name = $selExpr->fieldIdentificationVariable;
                $label = $name;
                $index = null;
            }
            
            $this->columns->add($name, $label, $index);
        }
        
    }
    
    /**
     *
     * Sets a column name which identifies every row in the grid.
     * 
     * @param string $column 
     */
    public function setIdentifierColumn($column) {
        if(isset($this->columns[$column])) {
            $this->_identifierColumn = $this->columns[$column];
        } else {
            throw new Pike_Exception('Cannot set identifier to a unknown column ('.$column.')');
        }
    }

    /**
     * Defines what happends when the grid is sorted by the server. Must return a array
     * with query hints!
     *
     * @return array
     */
    public function setEventSort(Closure $function) {
        $this->onOrder = $function;
    }

    /**
     * Defines what happends when the user filters data with jqGrid and send to the server. Must
     * return an array with query hints!
     *
     * @return array
     */
    public function setEventFilter(Closure $function) {
        $this->onFilter = $function;
    }    
    
    /**
     *
     * Look if there is default sorting defined in the original query by asking the AST. Defining
     * default sorting is done outside the datasource where query or querybuilder object is defined.
     *
     * @return array
     */
    public function getDefaultSorting()
    {
        if(null !== $this->_query->getAST()->orderByClause) {
           //support for 1 field only
            $orderByClause = $this->_query->getAST()->orderByClause;

            /* @var $orderByItem Doctrine\ORM\Query\AST\OrderByItem */
            $orderByItem = $orderByClause->orderByItems[0];

            if($orderByItem->expression instanceof \Doctrine\ORM\Query\AST\PathExpression) {
                $alias = $orderByItem->expression->identificationVariable;
                $field = $orderByItem->expression->field;

                $data['index'] = (strlen($alias) > 0 ? $alias . '.' : '') . $field;
                $data['direction'] = $orderByItem->type;

                return $data;
            }
        }

        return null;
    }

    /**
     *
     * Renders a single row and adds it to the internal data array
     *
     * @param array $row
     * @return void
     */
    private function _renderRow($row) {
        foreach($this->columns as $index=>$column) {

            if(array_key_exists('data', $column)) {
                if(is_callable($column['data'])) {
                    $row[$index] = $column['data']($row);
                } else {
                    array_walk($row, function($value, $key) use (&$column) {
                        $column['data'] = str_replace('{' . strtolower($key) . '}', $value, $column['data']);
                    });

                    $row[$index] = $column['data'];
                }
            } elseif(array_key_exists($index, $row)) {
                continue;
            } else {
                throw new Pike_Exception('Cannot render data for column '.$index);
            }
        }

        /**
         * Sort the row data specified by the column positions
         */
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
        $record['cell'] = array_values($row);

        if(null !== $this->_identifierColumn) {                
            $record['id'] = $this->_identifierColumn['data']($row);
        }

        $this->_data['rows'][] = $record;
    }    
    
    /**
     *
     * Get the initialized query hints based on the user-interface jqgrid parameters.
     *
     * @return array Doctrine compatible hints array
     */
    private function _getQueryHints() {
        $hints = array();

        /**
         * Sorting if defined
         */
        if(array_key_exists('sidx', $this->_params) && strlen($this->_params['sidx']) > 0) {
            $onSort = $this->onOrder;
            $hints = array_merge_recursive($hints, $onSort($this->_params, $this));
        }

        if(array_key_exists('filters', $this->_params) && (array_key_exists('_search', $this->_params) && $this->_params['_search'] == true)) {
            $onFilter = $this->onFilter;
            $hints = array_merge_recursive($hints, $onFilter($this->_params, $this));
        }

        return $hints;
    }    
    
    /**
     *
     * Returns a JSON string useable for JQuery Grid. This grids interprets this
     * data and is able to draw a grid.
     *
     * @return string JSON data
     */
    public function getJSON()
    {
        $offset = $this->_limitPerPage * ($this->_params['page'] - 1);
        $hints = $this->_getQueryHints();

        $count = Pike_Grid_Datasource_Doctrine_Paginate::getTotalQueryResults($this->_query, $hints);
        
        $paginateQuery = Pike_Grid_Datasource_Doctrine_Paginate::getPaginateQuery(
                $this->_query,
                $offset,
                $this->_limitPerPage,
                $hints
        );

        $result = $paginateQuery->getResult();

        $this->_data = array();
        $this->_data['page'] = (int)$this->_params['page'];
        $this->_data['total'] = ceil($count / $this->_limitPerPage);
        $this->_data['records'] = $count;
        $this->_data['rows'] = array();

        foreach($result as $row) {            
            $this->_renderRow($row);           
        }

        return json_encode($this->_data);
    }

}