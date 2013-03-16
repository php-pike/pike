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

namespace Pike\DataTable\DataSource\Doctrine;

use Doctrine\ORM\Query;

/**
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Paginate
{

    /**
     * @param  Query $query
     * @return Query
     */
    protected static function cloneQuery(Query $query)
    {
        /* @var $countQuery Query */
        $countQuery = clone $query;
        $params = $query->getParameters();

        $countQuery->setParameters($params);

        return $countQuery;
    }

    /**
     * Return the total amount of results
     *
     * Test if the query has a having clause, if so then execute a count query with the original
     * query wrapped in the from component. Both situations give back the total number of records
     * found.
     *
     * @param  Query   $query
     * @return integer
     */
    public static function getTotalQueryResults(Query $query,
            array $hints = array())
    {
        /* @var $countQuery Query */
        $countQuery = self::cloneQuery($query);

        if (null === $countQuery->getAST()->havingClause) {
            $hints = array_merge_recursive($hints, array(
                Query::HINT_CUSTOM_TREE_WALKERS => array('Pike\DataTable\DataSource\Doctrine\CountWalker')
                    ));
        }

        foreach ($hints as $name => $value) {
            $countQuery->setHint($name, $value);
        }

        $countQuery->setFirstResult(null)->setMaxResults(null);
        $countQuery->setParameters($query->getParameters());

        if (null !== $countQuery->getAST()->groupByClause
                || null !== $countQuery->getAST()->havingClause
        ) {
            $walker = function($node, &$keys, $walker) {
                        foreach ($node as $key => $value) {
                            if (is_array($value) || $value instanceof \Doctrine\ORM\Query\AST\Node) {
                                $walker($value, $keys, $walker);
                            }
                            if ($value instanceof \Doctrine\ORM\Query\AST\InputParameter && $value->isNamed) {
                                $keys[] = $value->name;
                            }
                        }
                    };

            $ast = $countQuery->getAST();

            $keys = array();
            foreach (array('fromClause', 'whereClause', 'havingClause') as $clause) {
                if (isset($ast->$clause)) {
                    $clause = $walker($ast->$clause, $keys, $walker);
                }
            }

            $parameters = $countQuery->getParameters();

            $values = array();
            foreach ($keys as $key) {
                if (isset($parameters[$key])) {
                    $values[] = $parameters[$key];
                }
            }

            $sql = 'SELECT COUNT(*) FROM (' . $countQuery->getSQL() . ') results';

            $stmt = $countQuery->getEntityManager()->getConnection()
                    ->executeQuery($sql, $values);

            $count = $stmt->fetchColumn();
        } else {
            $count = current($countQuery->getSingleResult());
        }

        return $count;
    }

    /**
     * Given the Query it returns a new query that is a paginatable query
     * using a modified subselect.
     *
     * @param  Query $query
     * @return Query
     */
    public static function getPaginateQuery(Query $query, $offset,
            $itemCountPerPage, array $hints = array()
    ) {
        foreach ($hints as $name => $hint) {
            $query->setHint($name, $hint);
        }

        return $query->setFirstResult($offset)->setMaxResults($itemCountPerPage);
    }

}
