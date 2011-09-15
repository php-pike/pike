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
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Overrides the default zend view stream wrapper
 *
 * Make sure that you add "php_flag short_open_tag off" to your .htaccess file. And the
 * php.ini for CLI has short_open_tag "Off". There is unfortunately no way to change this value in
 * PHP, ini_set() for example doesn't work. This is related to issue
 * http://framework.zend.com/issues/browse/ZF-11007
 *
 * Add to application.ini:
 * - pluginPaths.Pike_Application_Resource = "Pike/Application/Resource"
 * - resources.stream.streamWrapper = "Pike_View_Stream" (BEFORE resources.layout and resources.view)
 *
 * If you have any bootstrap _init* method that bootstraps the view, make sure you add a line to
 * ->bootstrap('stream') first!
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Application_Resource_Stream extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $options = $this->getOptions();
        if (isset($options['streamWrapper'])) {
            if (!in_array('zend.view', stream_get_wrappers())) {                
                stream_wrapper_register('zend.view', $options['streamWrapper']); // Pike_View_Stream
            }

            $this->getBootstrap()->bootstrap('view');
            $view = $this->getBootstrap()->getResource('view');
            $view->setUseStreamWrapper(true);
        } else {
            throw new Pike_Exception('Option "streamWrapper" needs te be set in order to use this resource.');
        }
    }
}