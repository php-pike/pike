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
 * Compares two dates
 *
 * Usage:
 * $element->addValidator(new Pike_Validate_DateCompare('startdate')); // exact match
 * $element->addValidator(new Pike_Validate_DateCompare('startdate', '<')); // not later
 * $element->addValidator(new Pike_Validate_DateCompare('startdate', '>')); // not earlier
 *     and specified element has value in date format m-d-Y
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */
class Pike_Validate_DateCompare extends Zend_Validate_Abstract
{
    /**
     * Error codes
     *
     * @const string
     */
    const NOT_SAME = 'notSame';
    const MISSING_TOKEN = 'missingToken';
    const NOT_LATER = 'notLater';
    const NOT_LATER_OR_EQUALS = 'notLaterOrEquals';
    const NOT_EARLIER = 'notEarlier';
    const NOT_EARLIER_OR_EQUALS = 'notEarlierOrEquals';
    const NOT_BETWEEN = 'notBetween';
    const INVALID_VALUE = 'invalidValue';
    const INVALID_TOKEN = 'invalidToken';

    /**
     * Error messages
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_SAME => "The date '%value%' does not match the given '%token%'",
        self::NOT_BETWEEN => "The date '%value%' is not in the valid range",
        self::NOT_LATER => "The date '%value%' is not later than '%token%'",
        self::NOT_LATER_OR_EQUALS => "The date '%value%' is not later or equals than '%token%'",
        self::NOT_EARLIER => "The date '%value%' is not earlier than '%token%'",
        self::NOT_EARLIER_OR_EQUALS => "The date '%value%' is not earlier or equals than '%token%'",
        self::MISSING_TOKEN => 'No date was provided to match against',
        self::INVALID_VALUE => "The date '%value%' is not valid",
        self::INVALID_TOKEN => "The date '%token%' is not valid"
    );
    
    /**
     * @var array
     */
    protected $_messageVariables = array('token' => '_tokenValue');
    
    /**
     * Token to validate against
     *
     * @var string
     */
    protected $_tokenValue;
    protected $_token;
    protected $_compare;

    /**
     * Sets validator options
     *
     * @param  mixed   $token
     * @param  boolean $compare
     * @param  string  $inputDateFormat
     */
    public function __construct($token = null, $compare = null, $inputDateFormat = null)
    {
        if (null !== $token) {
            $this->setInputDateFormat($inputDateFormat);
            $this->setToken($token);
            $this->setCompare($compare);
        }
    }

    /**
     * Sets token against which to compare
     *
     * @param  mixed $token
     * @return Pike_Validate_DateCompare
     */
    public function setToken($token)
    {
        if($token instanceof Zend_Form_Element) {
            $this->_tokenValue = (string) $token->getValue();
        } elseif($token instanceof Zend_Date) {
            $this->_tokenValue = $token->toString($this->getInputDateFormat());
        } elseif($token instanceof DateTime) {
            $this->_tokenValue = $token->format($this->getInputDateFormat());
        } else {
            $this->_tokenValue = $token;
        }
        
        $this->_token = $token;
        
        return $this;
    }

    /**
     * Returns token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Sets token value
     *
     * @param  mixed $value
     * @return Pike_Validate_DateCompare
     */
    public function setTokenValue($value)
    {
        $this->_tokenValue = $value;
        return $this;
    }

    /**
     * Sets compare against which to compare
     *
     * @param  mixed $compare
     * @return Pike_Validate_DateCompare
     */
    public function setCompare($compare)
    {
        $this->_compareString = (string) $compare;
        $this->_compare = $compare;
        return $this;
    }

    /**
     * Returns compare
     *
     * @return string
     */
    public function getCompare()
    {
        return $this->_compare;
    }

    /**
     * Set input date format
     *
     * @param  string $format
     * @return Pike_Validate_DateCompare
     */
    public function setInputDateFormat($format)
    {
        $this->_inputDateFormat = (string) $format;
        return $this;
    }

    /**
     * Returns input date format
     *
     * @return string
     */
    public function getInputDateFormat()
    {
        return $this->_inputDateFormat;
    }

    /**
     * Returns true if and only if a token has been set and the provided value matches that token.
     *
     * Defined by Zend_Validate_Interface
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $this->_setValue((string) $value);
        $token = $this->getToken();

        if ($token instanceof Zend_Form_Element) {
            if (!$token->isRequired() && strlen($token->getValue()) == 0) {
                return true;
            }
        }

        if ($token === null) {
            $this->_error(self::MISSING_TOKEN);
            return false;
        }

        $valueSystemDate = DateTime::createFromFormat($this->getInputDateFormat(), $value);

        if ($valueSystemDate instanceof DateTime) {
            $valueSystemDate->setTime(0, 0, 0);
        } else {
            $this->_error(self::INVALID_VALUE);
            return false;
        }

        $tokenSystemDate = DateTime::createFromFormat($this->getInputDateFormat(), $this->_tokenValue);
        if ($tokenSystemDate instanceof DateTime) {
            $tokenSystemDate->setTime(0, 0, 0);
        } else {
            $this->_error(self::INVALID_TOKEN);
            return false;
        }

        $date1 = new Zend_Date($valueSystemDate->getTimestamp());
        $date2 = new Zend_Date($tokenSystemDate->getTimestamp());

        switch ($this->getCompare()) {
            case '<' :
                if ($date1->compare($date2) < 0 || $date1->equals($date2)) {
                    $this->_error(self::NOT_LATER);
                    return false;
                }
                break;
            case '<=' :
                if ($date1->compare($date2) < 0 && !$date1->equals($date2)) {
                    $this->_error(self::NOT_LATER_OR_EQUALS);
                    return false;
                }
                break;
            case '>' :
                if ($date1->compare($date2) > 0 || $date1->equals($date2)) {
                    $this->_error(self::NOT_EARLIER);
                    return false;
                }
                break;
            case '>=' :
                if ($date1->compare($date2) > 0 || !$date1->equals($date2)) {
                    $this->_error(self::NOT_EARLIER_OR_EQUALS);
                    return false;
                }
                break;
            case '=' :
                if (!$date1->equals($date2)) {
                    $this->_error(self::NOT_SAME);
                    return false;
                }
                break;
        }

        return true;
    }
}