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
 * @license    MIT
 */

/**
 * MultiTranslate
 *
 * By default it's not possible to define multiple translate sources in your application.ini.
 * You'll have to put some code in your bootstrap class or create a custom application resource.
 * The PiKe MultiTranslate application resource makes it possible to define all your translate sources
 * in the application.ini and to define a logger.
 *
 * Example:
 *
 * pluginPaths.Pike_Application_Resource = "Pike/Application/Resource"
 * autoloaderNamespaces[] = "Pike"
 *
 * resources.log.translate.writerName = "Stream"
 * resources.log.translate.writerParams.stream = APPLICATION_PATH "/../data/logs/translate.log"
 * resources.log.translate.writerParams.permission = 660
 * resources.log.translate.writerParams.mode = "a"
 * resources.log.translate.filterName = "Priority"
 * resources.log.translate.filterParams.priority = 7
 * resources.log.translate.formatterName = "Simple"
 * resources.log.translate.formatterParams.format = "%timestamp% %priorityName% (%priority%): %namespace% | %message%" PHP_EOL
 *
 * resources.multiTranslate.logger = "translate"
 * resources.multiTranslate.default.adapter = "gettext"
 * resources.multiTranslate.default.content = APPLICATION_PATH "/../languages"
 * resources.multiTranslate.default.locale = "auto"
 * resources.multiTranslate.default.scan = "directory"
 * resources.multiTranslate.default.logUntranslated = 1
 * resources.multiTranslate.default.disableNotices = 0
 * ; Additional translate source (options are used from the default translate source unless specified)
 * resources.multiTranslate.zendValidate.adapter = "array"
 * resources.multiTranslate.zendValidate.content = APPLICATION_PATH "/../languages/%locale%/Zend_Validate.php"
 * resources.multiTranslate.zendValidate.locale = "auto"
 * resources.multiTranslate.zendValidate.logUntranslated = 1
 * resources.multiTranslate.zendValidate.disableNotices = 0
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Application_Resource_MultiTranslate extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Loggers
     *
     * @var array An indexed array with Zend_Log objects
     */
    protected $_loggers = array();

    /**
     * Init
     *
     * @return Zend_Translate
     */
    public function init()
    {
        $options = $this->getOptions();
        $translateLogger = $this->getLogger('default');

        // Set default translate source
        if (null !== $translateLogger) {
            $options['default']['log'] = $translateLogger;
        }
        $translate = new Zend_Translate($options['default']);

        unset($options['logger']);
        unset($options['default']);

        // Add additional translate sources
        foreach ($options as $translateSourceName => $translateSettings) {
            $translateSettings['content'] = str_replace('%locale%', $translate->getLocale(), $translateSettings['content']);

            if (file_exists($translateSettings['content'])) {
                $translateLogger = $this->getLogger($translateSourceName);
                if (null !== $translateLogger) {
                    $translateSettings['log'] = $translateLogger;
                }

                $sourceTranslate = new Zend_Translate($translateSettings);
                $translate->addTranslation(array('content' => $sourceTranslate));
            }
        }

        Zend_Registry::set('Zend_Translate', $translate);
        return $translate;
    }

    /**
     * Creates and returns a logger
     *
     * @param  string $namespace
     * @return Zend_Log
     */
    public function getLogger($namespace = null)
    {
        if (!array_key_exists($namespace, $this->_loggers)) {
            $this->_loggers[$namespace] = null;

            $options = $this->getOptions();
            if (isset($options['logger']) && '' != $options['logger']) {
                $applicationConfig = $this->getBootstrap()->getApplication()->getOptions();
                $loggerOptions = $applicationConfig['resources']['log'][$options['logger']];

                if (isset($loggerOptions['writerParams']['stream'])
                    && isset($loggerOptions['writerParams']['permission'])
                ) {
                    // Set the correct permissions on the log file
                    @chmod($loggerOptions['writerParams']['stream'],
                        octdec('0'. $loggerOptions['writerParams']['permission']));
                }

                $logger = new Zend_Log();
                $logger->addWriter($loggerOptions);
                $logger->setEventItem('namespace', $namespace);
                $this->_loggers[$namespace] = $logger;
            }
        }

        return $this->_loggers[$namespace];
    }
}