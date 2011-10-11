<?php
/**
 * DoctrineExtensions Paginate
 *
 * LICENSE
 *
 * Copyright (c) 2009-2010, David Abdemoulaie
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:

 * - Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.

 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */

use Doctrine\ORM\Query;

/**
 * Implements the Zend_Paginator_Adapter_Interface for use with Zend_Paginator
 *
 * Allows pagination of Doctrine\ORM\Query objects and DQL strings
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */
class Pike_Grid_DataSource_Doctrine_PaginationAdapter implements \Zend_Paginator_Adapter_Interface
{
    /**
     * The SELECT query to paginate
     *
     * @var Query
     */
    protected $query = null;

    /**
     * Total item count
     *
     * @var integer
     */
    protected $rowCount = null;

    /**
     * Use Array Result
     *
     * @var boolean
     */
    protected $arrayResult = false;

    /**
     * Namespace to use for bound parameters
     * If you use :pgid_# as a parameter, then
     * you must change this.
     *
     * @var string
     */
    protected $namespace = 'pgid';

    /**
     * Constructor
     *
     * @param Query $query
     * @param string $ns Namespace to prevent named parameter conflicts
     */
    public function __construct(Query $query, $ns = 'pgid')
    {
        $this->query = $query;
        $this->namespace = $ns;
    }

    /**
     * Set use array result flag
     *
     * @param boolean $flag True to use array result
     */
    public function useArrayResult($flag = true)
    {
        $this->arrayResult = $flag;
    }

    /**
     * Sets the total row count for this paginator
     *
     * Can be either an integer, or a Doctrine\ORM\Query object
     * which returns the count
     *
     * @param Query|integer $rowCount
     * @return void
     */
    public function setRowCount($rowCount)
    {
        if ($rowCount instanceof Query) {
            $this->rowCount = $rowCount->getSingleScalarResult();
        } else if (is_integer($rowCount)) {
            $this->rowCount = $rowCount;
        } else {
            throw new \InvalidArgumentException("Invalid row count");
        }
    }

    /**
     * Sets the namespace to be used for named parameters
     *
     * Parameters will be in the format 'namespace_1' ... 'namespace_N'
     *
     * @param string $ns
     * @return void
     * @author David Abdemoulaie
     */
    public function setNamespace($ns)
    {
        $this->namespace = $ns;
    }

    /**
     * Gets the current page of items
     *
     * @param string $offset
     * @param string $itemCountPerPage
     * @return void
     * @author David Abdemoulaie
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $ids = $this->createLimitSubquery($offset, $itemCountPerPage)
            ->getScalarResult();

        $ids = array_map(
            function ($e) { return current($e); },
            $ids
        );

        if ($this->arrayResult) {
            return $this->createWhereInQuery($ids)->getArrayResult();
        } else {
            return $this->createWhereInQuery($ids)->getResult();
        }
    }

    /**
     * @param Query $query
     * @return int
     */
    public function count()
    {
        if (is_null($this->rowCount)) {
            $this->setRowCount(
                $this->createCountQuery()
            );
        }
        return $this->rowCount;
    }

    /**
     * @return Query
     */
    protected function createCountQuery()
    {
        return Paginate::createCountQuery($this->query);
    }

    /**
     * @return Query
     */
    protected function createLimitSubquery($offset, $itemCountPerPage)
    {
        return Paginate::createLimitSubQuery($this->query, $offset, $itemCountPerPage);
    }

    /**
     * @return Query
     */
    protected function createWhereInQuery($ids)
    {
        return Paginate::createWhereInQuery($this->query, $ids, $this->namespace);
    }
}