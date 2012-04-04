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
 * Tests if the specified value is a valid url
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
     * 
     * @const string
     */
    const INVALID_URL = 'invalidUrl';

    /**
     * Error messages
     * 
     * @var array
     */
    protected $_messageTemplates = array(
        self::INVALID_URL => "'%value%' is not a valid URL. It must start with http(s):// and be valid.",
    );

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the $value is a valid url that starts with http(s)://
     * and the hostname is a valid TLD.
     *
     * @param  string $value
     * @throws Zend_Validate_Exception If a fatal error occurs for validation process
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        $this->_setValue($value);
        
        // Get a Zend_Uri_Http object for our URL, this will only accept http(s) schemes
        try {
            $uriHttp = Zend_Uri_Http::fromString($value);
        } catch (Zend_Uri_Exception $e) {
            $this->_error(self::INVALID_URL);
            return false;
        }

        // If we have a valid URI then we check the hostname for valid TLDs, and not local urls
        $hostnameValidator = new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_DNS); //do not allow local hostnames, this is the default

        if (!$hostnameValidator->isValid($uriHttp->getHost())) {
            $this->_error(self::INVALID_URL);
            return false;
        }
        
        return true;
    }
}