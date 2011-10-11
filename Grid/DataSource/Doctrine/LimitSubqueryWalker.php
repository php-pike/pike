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

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\SimpleSelectExpression,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\AggregateExpression;

/**
 * Replaces the selectClause of the AST with a SELECT DISTINCT root.id equivalent
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */
class Pike_Grid_DataSource_Doctrine_LimitSubqueryWalker extends TreeWalkerAdapter
{
    /**
     * Walks down a SelectStatement AST node, modifying it to retrieve DISTINCT ids
     * of the root Entity
     *
     * @param SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $parent = null;
        $parentName = null;
        foreach ($this->_getQueryComponents() AS $dqlAlias => $qComp) {

            // skip mixed data in query
            if (isset($qComp['resultVariable'])) {
                continue;
            }

            if ($qComp['parent'] === null && $qComp['nestingLevel'] == 0) {
                $parent = $qComp;
                $parentName = $dqlAlias;
                break;
            }
        }

        $pathExpression = new PathExpression(
                        PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION, $parentName,
                        $parent['metadata']->getSingleIdentifierFieldName()
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

        $AST->selectClause->selectExpressions = array(
            new SimpleSelectExpression($pathExpression)
        );

        if (isset($AST->orderByClause)) {
            foreach ($AST->orderByClause->orderByItems as $item) {
                $pathExpression = new PathExpression(
                                PathExpression::TYPE_STATE_FIELD | PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
                                $item->expression->identificationVariable,
                                $item->expression->field
                );
                $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
                $AST->selectClause->selectExpressions[] = new SimpleSelectExpression($pathExpression);
            }
        }

        $AST->selectClause->isDistinct = true;
    }

}
