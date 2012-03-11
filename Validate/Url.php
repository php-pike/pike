<?php
/**
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */

/** 
 *
 * Test if given value is a valid url
 * 
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @package    Validate
 * @license    MIT
 */
class Pike_Validate_Url extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const INVALID_URL = 'invalidUrl';

    /**
     * Error messages
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID_URL => "'%value%' is not a valid URL. It must start with http(s):// and be valid.",
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the $value is a valid url that starts with http(s)://
     * and the hostname is a valid TLD
     *
     * @param string $value
     * @throws Zend_Validate_Exception if a fatal error occurs for validation process
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        $this->_setValue($value);
        //get a Zend_Uri_Http object for our URL, this will only accept http(s) schemes
        try {
            $uriHttp = Zend_Uri_Http::fromString($value);
        } catch (Zend_Uri_Exception $e) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        //if we have a valid URI then we check the hostname for valid TLDs, and not local urls
        $hostnameValidator = new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_DNS); //do not allow local hostnames, this is the default

        if (!$hostnameValidator->isValid($uriHttp->getHost())) {
            $this->_error(self::INVALID_URL);
            return false;
        }
        return true;
    }

}