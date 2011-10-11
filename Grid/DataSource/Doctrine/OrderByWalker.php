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

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\OrderByItem,
    Doctrine\ORM\Query\AST\OrderByClause,
    Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * This class walks a selectstatement. It will cause a orderby to be replaced
 * with the field the user wants to sort on.
 */
class Pike_Grid_DataSource_Doctrine_OrderByWalker extends TreeWalkerAdapter
{
    /**
     * Walks down a SelectStatement AST node, modify the orderby clause if the user
     * wants to sort his results.
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $sidx = $this->_getQuery()->getHint('sidx');
        $sord = $this->_getQuery()->getHint('sord');

        if(strpos($sidx, '.') !== false) {
            $parts = explode('.', $sidx);
            $sidx = $parts[1];
            $alias = $parts[0];
        } else {
            $alias = null;
        }

        $pathExpression = new PathExpression(
                PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                $alias,
                $sidx
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $orderByItem = new orderByItem($pathExpression);
        $orderByItem->type = $sord;

        $orderByItems = array($orderByItem);

        /**
         * Remove all other orderby items and add Pike_Grid orderfield.
         */
        if(null === $AST->orderByClause) {
            $AST->orderByClause = new OrderByClause($orderByItems);
        } else {
            $AST->orderByClause->orderByItems = $orderByItems;
        }
    }
}
