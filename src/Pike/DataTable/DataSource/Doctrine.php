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
 * Data source for Doctrine queries and entities. You can use this data source with
 * Pike_Grid which will both create all neccasary javascript and JSON for drawing
 * a grid with JQgrid.
 *
 * Dependecies: jqGrid, Doctrine, Zend Framework
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Doctrine extends AbstractDataSource implements DataSourceInterface
{

    /**
     * @var $query \Doctrine\ORM\Query
     */
    protected $query;

    /**
     * Constructor
     *
     * @param mixed $source
     */
    public function __construct($source)
    {
        switch ($source) {
            case ($source instanceof \Doctrine\ORM\QueryBuilder) :
                $this->query = $source->getQuery();
                break;
            case ($source instanceof \Doctrine\ORM\Query) :
                $this->query = $source;
                break;
            case ($source instanceof \Doctrine\ORM\EntityRepository) :
                $this->query = $source->createQueryBuilder('al')->getQuery();
                break;
            default :
                throw new \Pike\Exception('Unknown source given, source must either be an entity, query or querybuilder object.');
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($offset, $limit)
    {
        $hints = $this->getQueryHints();
        $paginateQuery = Doctrine\Paginate::getPaginateQuery(
                        $this->query, $offset, $limit, $hints
        );

        return $paginateQuery->getArrayResult();
    }

    /**
     * @return integer
     */
    public function count()
    {
        return Doctrine\Paginate::getTotalQueryResults($this->query, $this->getQueryHints());
    }

    /**
     * {@inheritdoc}
     *
     * Looks up in the AST what select expression we use and analyses which
     * fields are used.
     *
     * @return array
     */
    public function getFields()
    {
        $fields = array();

        $selectClause = $this->query->getAST()->selectClause;
        if (count($selectClause->selectExpressions) == 0) {
            throw new \Pike\Exception('The query should contain at least one column, none found.');
        }

        /* @var $selExpr Doctrine\ORM\Query\AST\SelectExpression */
        foreach ($selectClause->selectExpressions as $selExpr) {

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

            /* @var $expr \Doctrine\ORM\Query\AST\PathExpression */
            if ($expr instanceof \Doctrine\ORM\Query\AST\PathExpression) {
                // Check if field alias is available
                if (null !== $selExpr->fieldIdentificationVariable) {
                    $field = $selExpr->fieldIdentificationVariable;
                } else {
                    $field = $expr->field;
                }
            } else {
                $field = $selExpr->fieldIdentificationVariable;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Looks if there is default sorting defined in the original query by asking the AST. Defining
     * default sorting is done outside the data source where query or querybuilder object is defined.
     *
     * @return array
     */
    public function getDefaultSorting()
    {
        $sort = array();

        if (null !== $this->query->getAST()->orderByClause) {
            //support for 1 field only
            $orderByClause = $this->query->getAST()->orderByClause;

            /* @var $orderByItem Doctrine\ORM\Query\AST\OrderByItem */
            $orderByItem = $orderByClause->orderByItems[0];

            if ($orderByItem->expression instanceof \Doctrine\ORM\Query\AST\PathExpression) {
                $sort['field'] = $orderByItem->expression->field;
                $sort['direction'] = $orderByItem->type;
            }
        }

        return $sort;
    }

    /**
     * Returns the initialized query hints based on the user-interface parameters
     *
     * @return array Doctrine compatible hints array
     */
    private function getQueryHints()
    {
        $hints = array();

        $sortQueryHints = $this->getSortQueryHints();
        if ($sortQueryHints) {
            $hints = array_merge_recursive($hints, $sortQueryHints);
        }

        $filterQueryHints = $this->getFilterQueryHints();
        if ($filterQueryHints) {
            $hints = array_merge_recursive($hints, $filterQueryHints);
        }

        return $hints;
    }

    /**
     * Returns a Doctrine compatible hints array for the filtering
     *
     * @return array
     */
    protected function getFilterQueryHints()
    {
        $fields = array();

        foreach ($this->filters as $filter) {
            $fieldName = $this->getFieldName($filter['field']);
            if ($fieldName) {
                $fields[] = array('name' => $fieldName, 'data' => $filter['data']);
            }
        }

        if (count($fields)) {
            return array(
                \Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS => array(
                    '\Pike\DataTable\DataSource\Doctrine\WhereLikeWalker'),
                'fields' => $fields
            );
        }

        return null;
    }

    /**
     * Returns a Doctrine compatible hints array for the sorting
     *
     * @todo: multiple sort columns
     * @return array
     */
    protected function getSortQueryHints()
    {
        $sorts = $this->getSorts();

        if (count($sorts) === 0) {
            return null;
        }

        $sort = $sorts[0];

        $fieldName = $this->getFieldName($sort['field']);
        $direction = in_array(strtoupper($sort['direction']), array('ASC', 'DESC')) ? strtoupper($sort['direction']) : 'ASC';

        if ($fieldName && $direction) {
            return array(
                \Doctrine\ORM\Query::HINT_CUSTOM_TREE_WALKERS => array(
                    '\Pike\DataTable\DataSource\Doctrine\OrderByWalker'),
                'fieldName' => $fieldName,
                'direction' => $direction,
            );
        }

        return null;
    }

    /**
     * Returns the actual field name for the specified column name (field alias
     * or actual field name)
     *
     * The field alias is used if available, otherwise the composition
     * of "identification variable" and "field".
     *
     * @param  string         $name
     * @return string|boolean
     */
    public function getFieldName($name)
    {
        if (strpos($name, '.') !== false) {
            return $name;
        }

        $field = false;

        /* @var $selExpr Doctrine\ORM\Query\AST\SelectExpression */
        foreach ($this->query->getAST()->selectClause->selectExpressions as $selExpr) {
            $expr = $selExpr->expression;

            // Skip aggregate fields
            if (!isset($expr->field)) {
                continue;
            }

            // Check if field alias exists
            if ($name == $selExpr->fieldIdentificationVariable) {
                $field = $expr->identificationVariable . '.' . $expr->field;
                break;
            } elseif (isset($expr->field) && $name == $expr->field
                    && '' == $selExpr->fieldIdentificationVariable
            ) {
                $field = $expr->identificationVariable . '.' . $expr->field;
            }
        }

        return $field;
    }

}
