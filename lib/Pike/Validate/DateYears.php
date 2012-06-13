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
 * @package    Validate
 * @license    MIT
 */

/**
 * Test if a date is more or less than the specified amount of years from now
 *
 * Usage:
 * $element->addValidator(new Pike_Validate_DateYears(21, '>=');
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */
class Pike_Validate_DateYears extends Zend_Validate_Abstract
{
    const NOT_MORE = 'notMore';
    const NOT_MORE_OR_EQUALS = 'notMoreOrEquals';
    const NOT_LESS = 'notLess';
    const NOT_LESS_OR_EQUALS = 'notLessOrEquals';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_MORE => 'Date "%value%" must be more than %years% years from now',
        self::NOT_MORE_OR_EQUALS => 'Date "%value%" must be at least %years% years from now',
        self::NOT_LESS => 'Date "%value%" must be less than %years% years from now',
        self::NOT_LESS_OR_EQUALS => 'Date "%value%" must be at most %years% years from now',
    );

    /**
     * @var array
     */
    protected $_messageVariables = array('years' => '_years');

    /**
     * The amount of years from now
     *
     * @var integer
     */
    protected $_years;

    /**
     * @var string
     */
    protected $_operator;

    /**
     * @var string
     */
    protected $_inputDateFormat;

    /**
     * Sets validator options
     *
     * @param  integer $years
     * @param  string  $operator
     * @param  string  $inputDateFormat Date in format as described at http://php.net/date
     */
    public function __construct($years, $operator = '>=', $inputDateFormat = 'm-d-Y')
    {
        $this->_years = $years;
        $this->_operator = $operator;
        $this->_inputDateFormat = $inputDateFormat;
    }

    /**
     * Returns true if and only if mission defined in $value doesn't exist
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if ($value instanceof \DateTime) {
            $inputDate = $value;
        } else {
            $inputDate = \DateTime::createFromFormat($this->_inputDateFormat, $value);
        }

        $now = new DateTime();
        $interval = $now->diff($inputDate);
        $years = $interval->format('%y');

        switch ($this->_operator) {
            case '>':
                if ($years <= $this->_years) {
                    $this->_error(self::NOT_MORE);
                    return false;
                }
                break;
            case '>=':
                if ($years < $this->_years) {
                    $this->_error(self::NOT_MORE_OR_EQUALS);
                    return false;
                }
                break;
            case '<';                
                if ($years <= $this->_years) {
                    $this->_error(self::NOT_LESS);
                    return false;
                }
                break;
            case '<=':
                if ($years < $this->_years) {
                    $this->_error(self::NOT_LESS_OR_EQUALS);
                    return false;
                }
                break;
            default:
                break;
        }

        return true;
    }
}