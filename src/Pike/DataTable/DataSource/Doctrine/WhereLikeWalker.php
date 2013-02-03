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

namespace Pike\DataTable\DataSource\Doctrine;

use Doctrine\ORM\Query\TreeWalkerAdapter,
    Doctrine\ORM\Query\AST\SelectStatement,
    Doctrine\ORM\Query\AST\PathExpression,
    Doctrine\ORM\Query\AST\LikeExpression,
    Doctrine\ORM\Query\AST\ComparisonExpression,
    Doctrine\ORM\Query\AST\Literal,
    Doctrine\ORM\Query\AST\InputParameter,
    Doctrine\ORM\Query\AST\ConditionalPrimary,
    Doctrine\ORM\Query\AST\ConditionalTerm,
    Doctrine\ORM\Query\AST\ConditionalExpression,
    Doctrine\ORM\Query\AST\ConditionalFactor,
    Doctrine\ORM\Query\AST\Node,
    Doctrine\ORM\Query\AST\WhereClause;

/**
 * Adds LIKE parts to the original query to search the database for rows on the
 * given phrases.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class WhereLikeWalker extends TreeWalkerAdapter
{
    /**
     * Adds WHERE like to the query for search operations
     *
     * @param  SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $fields = $this->_getQuery()->getHint('fields');
        $operator = $this->_getQuery()->getHint('groupOp');

        foreach ($fields as $field) {
            $fieldIdentifier = null;
            $fieldName = $field->field;

            if (false !== strpos($field->field, '.')) {
                $fieldParts = explode('.',$field->field);
                $fieldName = $fieldParts[1];
                $fieldIdentifier = $fieldParts[0];
            }

            $pathExpression = new PathExpression(PathExpression::TYPE_STATE_FIELD, $fieldIdentifier, $fieldName);
            $pathExpression->type = PathExpression::TYPE_STATE_FIELD;

            $conditionalExpression = $this->_getConditionalExpression($pathExpression, $field);

            $conditionalPrimary = new ConditionalPrimary();
            $conditionalPrimary->simpleConditionalExpression = $conditionalExpression;

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

    /**
     * Returns a conditional expression
     *
     * @param  PathExpression $pathExpression
     * @param  stdClass       $field
     * @return Node           Expression
     */
    protected function _getConditionalExpression(PathExpression $pathExpression, $field)
    {
        // Default operator "begins with"
        $conditionalExpression = new LikeExpression($pathExpression, $field->data . '%');

        if (isset($field->op)) {
            switch ($field->op) {
                case 'eq':
                case 'equal':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '=', $field->data)
                    );
                    break;
                case 'ne':
                case 'not equal':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '!=', $field->data)
                    );
                    break;
                case 'lt':
                case 'less':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '<', $field->data)
                    );
                    break;
                case 'le':
                case 'less or equal':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '<=', $field->data)
                    );
                    break;
                case 'gt':
                case 'greater':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '>', $field->data)
                    );
                    break;
                case 'ge':
                case 'greater or equal':
                    $conditionalExpression = new ComparisonExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '>=', $field->data)
                    );
                    break;
                case 'bw':
                case 'begins with':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, $field->data . '%')
                    );
                    break;
                case 'bn':
                case 'does not begin with':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, $field->data . '%')
                    );
                    $conditionalExpression->not = true;
                    break;
                case 'in':
                case 'is in':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data . '%')
                    );
                    break;
                case 'ni':
                case 'is not in':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data . '%')
                    );
                    $conditionalExpression->not = true;
                    break;
                case 'ew':
                case 'ends with':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data)
                    );
                    break;
                case 'en':
                case 'does not end with':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data)
                    );
                    $conditionalExpression->not = true;
                    break;
                case 'cn':
                case 'contains':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data . '%')
                    );
                    break;
                case 'nc':
                case 'does not contain':
                    $conditionalExpression = new LikeExpression(
                            $pathExpression, 
                            new Literal(Literal::STRING, '%' . $field->data . '%')
                    );
                    $conditionalExpression->not = true;
                    break;
            }
        }

        return $conditionalExpression;
    }
}