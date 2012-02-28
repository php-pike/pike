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
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @author     Nico Vogelaar
 * @license    MIT
 */
class Pike_Paginator_Adapter_Doctrine implements Zend_Paginator_Adapter_Interface
{

    /**
     * Doctrine Query
     *
     * @var Doctrine\ORM\Query
     */
    protected $_query = null;

    /**
     * Doctrine Paginator
     *
     * @var Doctrine\ORM\Tools\Pagination\Paginator
     */
    protected $_paginator = null;

    /**
     * Item count
     *
     * @var integer
     */
    protected $_count = null;

    /**
     * Constructor.
     *
     * @param Doctrine\ORM\Query $query Query to paginate
     */
    public function __construct(Doctrine\ORM\Query $query)
    {
        $this->_query = $query;
        $this->_paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($this->_query);
        $this->_count = count($this->_paginator);
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return \ArrayIterator
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_query
            ->setFirstResult($offset)
            ->setMaxResults($itemCountPerPage);
        return $this->_paginator->getIterator();
    }

    /**
     * Returns the total number of rows.
     *
     * @return integer
     */
    public function count()
    {
        return $this->_count;
    }
}