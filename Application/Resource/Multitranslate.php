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
 * MultiTranslate
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Application_Resource_MultiTranslate extends Zend_Application_Resource_ResourceAbstract
{
    public function init()
    {
        $config = Zend_Registry::get('config');
        $options = $this->getOptions();
        $translate = new Zend_Translate($options['default']);

        /**
         * Add translate logger
         */
        if (isset($options['logger'])) {
            $loggerOptions = $config->resources->log->$options['logger']->toArray();
            $logger = new Zend_Log();
            $logger->addWriter($loggerOptions);

            $path = $loggerOptions['writerParams']['stream'];
            if (isset($loggerOptions['writerParams']['permission'])) {
                // Set the correct permissions on the log file
                @chmod($path, octdec('0'. $loggerOptions['writerParams']['permission']));
            }

            $translate->setOptions(array('log' => $logger));
        }

        unset($options['logger']);
        unset($options['default']);

        // Add additional translate sources
        foreach ($options as $translateSourceName => $translateSettings) {
            $translateSettings['content'] = str_replace('%locale%', $translate->getLocale(), $translateSettings['content']);

            if (file_exists($translateSettings['content'])) {
                $sourceTranslate = new Zend_Translate($translateSettings);
                $translate->addTranslation(array('content' => $sourceTranslate));
                unset($sourceTranslate);
            }
        }

        Zend_Registry::set('Zend_Translate', $translate);
        return $translate;
    }

}
