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
 *
 */

use Doctrine\ORM\Query;

class Pike_Grid_DataSource_Doctrine_Paginate
{
    /**
     * @param Query $query
     * @return Query
     */
    static protected function cloneQuery(Query $query)
    {
        $reflector = new ReflectionClass($query);
        $attribute = $reflector->getProperty('_paramTypes');
        $attribute->setAccessible(true);
        $paramTypes = $attribute->getValue($query);

        /* @var $countQuery Query */
        $countQuery = clone $query;
        $params = $query->getParameters();

        $countQuery->setParameters($params, $paramTypes);

        return $countQuery;
    }

    /**
     * @param Query $query
     * @return int
     */
    static public function count(Query $query)
    {
        return self::createCountQuery($query)->getSingleScalarResult();
    }

    /**
     * @param Query $query
     * @return int
     */
    static public function getTotalQueryResults(Query $query, array $hints = array())
    {
        return self::createCountQuery($query, $hints)->getSingleScalarResult();
    }

    /**
     * Given the Query it returns a new query that is a paginatable query using a modified subselect.
     *
     * @param Query $query
     * @return Query
     */
    static public function getPaginateQuery(Query $query, $offset, $itemCountPerPage, array $hints = array())
    {
        $ids = array_map('current', self::createLimitSubQuery($query, $offset, $itemCountPerPage, $hints)->getScalarResult());

        return self::createWhereInQuery($query, $ids, 'pgid', $hints);
    }

    /**
     * @param Query $query
     * @return Query
     */
    static public function createCountQuery(Query $query, array $hints = array())
    {
        /* @var $countQuery Query */
        $countQuery = self::cloneQuery($query);

//        $hints = array_merge_recursive($hints, array(
//            Query::HINT_CUSTOM_TREE_WALKERS => array('Pike_Grid_DataSource_Doctrine_HavingWalker')
//        ));

        $hints = array_merge_recursive($hints, array(
            Query::HINT_CUSTOM_TREE_WALKERS => array('Pike_Grid_DataSource_Doctrine_CountWalker')
        ));

        foreach ($hints as $name => $value) {
            $countQuery->setHint($name, $value);
        }

        $countQuery->setFirstResult(null)->setMaxResults(null);
        $countQuery->setParameters($query->getParameters());

        return $countQuery;
    }

    /**
     * @param Query $query
     * @param int $offset
     * @param int $itemCountPerPage
     * @return Query
     */
    static public function createLimitSubQuery(Query $query, $offset, $itemCountPerPage, array $phints = array())
    {
        $subQuery = self::cloneQuery($query);

        $hints = array();
        $hints[Query::HINT_CUSTOM_TREE_WALKERS] = array('Pike_Grid_DataSource_Doctrine_LimitSubqueryWalker');
        $hints = array_merge_recursive($phints, $hints);

        foreach ($hints as $name => $hint) {
            $subQuery->setHint($name, $hint);
        }

        $subQuery->setParameters($query->getParameters());

        if ($itemCountPerPage >= 0) {
            $subQuery->setFirstResult($offset)->setMaxResults($itemCountPerPage);
        }

        return $subQuery;
    }

    /**
     * @param Query $query
     * @param array $ids
     * @param string $namespace
     * @return Query
     */
    static public function createWhereInQuery(Query $query, array $ids, $namespace = 'pgid', array $phints = array())
    {
        $whereInQuery = clone $query;

        $whereInQuery->setParameters($query->getParameters());

        $hints = array();
        if (count($ids) > 0) {
            $hints[Query::HINT_CUSTOM_TREE_WALKERS] = array('Pike_Grid_DataSource_Doctrine_WhereInWalker');
            $hints['id.count'] = count($ids);
            $hints['pg.ns'] = $namespace;
        }

        $hints = array_merge_recursive($phints, $hints);

        foreach ($hints as $name => $hint) {
            $whereInQuery->setHint($name, $hint);
        }

        $whereInQuery->setFirstResult(null)->setMaxResults(null);

        if (count($ids) > 0) {
            foreach ($ids as $i => $id) {
                $i = $i+1;
                $whereInQuery->setParameter("{$namespace}_{$i}", $id);
            }
        }

        return $whereInQuery;
    }
}