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
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\InExpression,
    Doctrine\ORM\Query\AST\InputParameter,
    Doctrine\ORM\Query\AST\ConditionalPrimary,
    Doctrine\ORM\Query\AST\ConditionalTerm,
    Doctrine\ORM\Query\AST\ConditionalExpression,
    Doctrine\ORM\Query\AST\ConditionalFactor,
    Doctrine\ORM\Query\AST\WhereClause;

/**
 * Replaces the whereClause of the AST with a WHERE id IN (:foo_1, :foo_2) equivalent
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Paginate
 * @author      David Abdemoulaie <dave@hobodave.com>
 * @copyright   Copyright (c) 2010 David Abdemoulaie (http://hobodave.com/)
 * @license     http://hobodave.com/license.txt New BSD License
 */
class Pike_Grid_DataSource_Doctrine_WhereInWalker extends TreeWalkerAdapter
{
    /**
     * Replaces the whereClause in the AST
     *
     * Generates a clause equivalent to WHERE IN (:pgid_1, :pgid_2, ...)
     *
     * The parameter namespace (pgid) is retrieved from the pg.ns query hint
     * The total number of parameters is retrieved from the id.count query hint
     *
     * @param  SelectStatement $AST
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
                        PathExpression::TYPE_STATE_FIELD, $parentName, $parent['metadata']->getSingleIdentifierFieldName()
        );
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
        $inExpression = new InExpression($pathExpression);
        $ns = $this->_getQuery()->getHint('pg.ns');
        $count = $this->_getQuery()->getHint('id.count');
        for ($i = 1; $i <= $count; $i++) {
            $inExpression->literals[] = new InputParameter(":{$ns}_$i");
        }
        $conditionalPrimary = new ConditionalPrimary;
        $conditionalPrimary->simpleConditionalExpression = $inExpression;

        // if no existing whereClause
        if ($AST->whereClause === null) {
            $AST->whereClause = new WhereClause(
                            new ConditionalExpression(array(
                                new ConditionalTerm(array(
                                    new ConditionalFactor($conditionalPrimary)
                                ))
                            ))
            );
        } else { // add to the existing using AND
            // existing AND clause
            if ($AST->whereClause->conditionalExpression instanceof ConditionalTerm) {
                $AST->whereClause->conditionalExpression->conditionalFactors[] = $conditionalPrimary;
            }
            // single clause where
            elseif ($AST->whereClause->conditionalExpression instanceof ConditionalPrimary) {
                $AST->whereClause->conditionalExpression = new ConditionalExpression(
                                array(
                                    new ConditionalTerm(
                                            array(
                                                $AST->whereClause->conditionalExpression,
                                                $conditionalPrimary
                                            )
                                    )
                                )
                );
            }
            // an OR clause
            elseif ($AST->whereClause->conditionalExpression instanceof ConditionalExpression) {
                $tmpPrimary = new ConditionalPrimary;
                $tmpPrimary->conditionalExpression = $AST->whereClause->conditionalExpression;
                $AST->whereClause->conditionalExpression = new ConditionalTerm(
                                array(
                                    $tmpPrimary,
                                    $conditionalPrimary,
                                )
                );
            } else {
                // error check to provide a more verbose error on failure
                throw \Exception("Unknown conditionalExpression in WhereInWalker");
            }
        }
    }

}
