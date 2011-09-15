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
 * Sets the PHP error handler and logs PHP errors
 *
 * To bootstrap this application resource add the following to your application.ini:
 *
 * pluginPaths.Pike_Application_Resource = "Pike/Application/Resource"
 * autoloaderNamespaces[] = "Pike"
 * resources.error.errorHandler =
 *
 * If you want to log PHP errors add the following to your application.ini:
 *
 * resources.log.php.writerName = "Stream"
 * resources.log.php.writerParams.stream = APPLICATION_PATH "/../data/logs/php_errors.log"
 * resources.log.php.writerParams.mode = "a"
 * resources.log.php.writerParams.permission = "666"
 * resources.log.php.filterName = "Priority"
 * resources.log.php.filterParams.priority = 7
 * resources.log.php.formatterName = "Simple"
 * resources.log.php.formatterParams.format = "%timestamp% %priorityName% (%priority%): %message% on line %line% in file %file%" PHP_EOL
 *
 * If you want to convert PHP errors to exceptions set a value for "resources.error.errorHandler"
 * in your application.ini:
 *
 * resources.error.errorHandler = "Pike_ErrorHandler"
 *
 * @link       http://php.net/manual/en/function.set-error-handler.php
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Application_Resource_Error extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $configErrorHandler = Zend_Registry::get('config')->resources->error->errorHandler;
        if (isset($configErrorHandler) && $configErrorHandler != '') {
            $options = $this->getOptions();
            $handler = isset($options['handler']) ? $options['handler'] : 'Pike_ErrorHandler';
            set_error_handler(array($handler, 'dispatch'));
        }

        if (isset(Zend_Registry::get('config')->resources->log->php)) {
            $config = Zend_Registry::get('config')->resources->log->php->toArray();

            if (isset($config['writerParams']['permission'])) {
                // Set the correct permissions on the log file
                @chmod($config['writerParams']['stream'], octdec('0'. $config['writerParams']['permission']));
            }

            $logger = new Zend_Log();
            $logger->addWriter($config);
            $logger->registerErrorHandler();
        }
    }
}