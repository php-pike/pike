<?php
/**
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */

/**
 * Compares two dates
 *
 * Usage:
 * $element->addValidator(new Buza_Validate_DateCompare('startdate')); // exact match
 * $element->addValidator(new Buza_Validate_DateCompare('startdate', 'enddate')); // between dates
 * $element->addValidator(new Buza_Validate_DateCompare('startdate', '<')); // not later
 * $element->addValidator(new Buza_Validate_DateCompare('startdate', '>')); // not earlier
 * $element->addValidator(new Buza_Validate_DateCompare('startdate', true, 'm-d-Y')); // not later
 *     and specified element has value in date format m-d-Y
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
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
    const NOT_SAME      = 'notSame';
    const MISSING_TOKEN = 'missingToken';
    const NOT_LATER     = 'notLater';
    const NOT_EARLIER   = 'notEarlier';
    const NOT_BETWEEN   = 'notBetween';
    const INVALID_VALUE = 'invalidValue';
    const INVALID_TOKEN = 'invalidToken';

    /**
     * Error messages
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::NOT_SAME      => "The date '%value%' does not match the given '%token%'",
        self::NOT_BETWEEN   => "The date '%value%' is not in the valid range",
        self::NOT_LATER     => "The date '%value%' is not later than '%token%'",
        self::NOT_EARLIER   => "The date '%value%' is not earlier than '%token%'",
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
    public function __construct($token = null, $compare = true, $inputDateFormat = null)
    {
        if (null !== $token) {
            $this->setToken($token);
            $this->setCompare($compare);
            $this->setInputDateFormat($inputDateFormat);
        }
    }

    /**
     * Sets token against which to compare
     *
     * @param  mixed $token
     * @return Buza_Validate_DateCompare
     */
    public function setToken($token)
    {
        $this->_tokenValue = (string) $token->getValue();
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
     * @return Buza_Validate_DateCompare
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
     * @return Buza_Validate_DateCompare
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
     * @return Buza_Validate_DateCompare
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

        if ($token === null) {
            $this->_error(self::MISSING_TOKEN);
            return false;
        } else {
            $this->setTokenValue($token->getValue());
        }

        $valueSystemDate = DateTime::createFromFormat($this->getInputDateFormat(), $value);
        if ($valueSystemDate instanceof DateTime) {
            $valueSystemDate = $valueSystemDate->format('Y-m-d H:i:s');
        } else {
            $this->_error(self::INVALID_VALUE);
            return false;
        }

        $tokenSystemDate = DateTime::createFromFormat($this->getInputDateFormat(), $token->getValue());
        if ($tokenSystemDate instanceof DateTime) {
            $tokenSystemDate = $tokenSystemDate->format('Y-m-d H:i:s');
        } else {
            $this->_error(self::INVALID_TOKEN);
            return false;
        }

        $valueSystemTime = strtotime($valueSystemDate);
        $tokenSystemTime = strtotime($tokenSystemDate);

        // If one of the two is invalid and result in FALSE, return silently. The date validator
        // of the field in question will handle the invalid date first
        if ($valueSystemTime === false || $tokenSystemTime === false) {
            return false;
        }

        $date1 = new Zend_Date($valueSystemTime);
        $date2 = new Zend_Date($tokenSystemTime);

        if ($this->getCompare() === '<') {
            if ($date1->compare($date2) < 0 || $date1->equals($date2)) {
                $this->_error(self::NOT_LATER);
                return false;
            }
        } else if ($this->getCompare() === '>') {
            if ($date1->compare($date2) > 0 || $date1->equals($date2)) {
                $this->_error(self::NOT_EARLIER);
                return false;
            }
        } else if ($this->getCompare() === null) {
            if (!$date1->equals($date2)) {
                $this->_error(self::NOT_SAME);
                return false;
            }
        } else {
            $date3 = new Zend_Date($this->getCompare());
            if ($date1->compare($date2) < 0 || $date1->compare($date3) > 0) {
                $this->_error(self::NOT_BETWEEN);
                return false;
            }
        }

        return true;
    }
}

