<?php
/**
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
                if ($years >= $this->_years) {
                    $this->_error(self::NOT_LESS);
                    return false;
                }
                break;
            case '<=':
                if ($years > $this->_years) {
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