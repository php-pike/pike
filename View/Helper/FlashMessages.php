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
 * FlashMessages view helper
 *
 * (Based on Zend Framework 1.11+)
 * This helper renders the flash messages.
 *
 * Add to application.ini:
 * resources.view.helperPath.Pike_View_Helper = APPLICATION_PATH "/../library/Pike/View/Helper"
 * autoloaderNamespaces[] = "Pike"
 *
 * Add to layout.phtml:
 * <?= $this->flashMessages() ?>
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_FlashMessages extends Zend_View_Helper_Abstract
{
    /**
     * FlashMessenger
     *
     * @var Zend_Controller_Action_Helper_FlashMessenger
     */
    protected $_flashMessenger;

    /**
     * Translator
     *
     * @var Zend_Translate
     */
    protected $_translator;

    /**
     * Formats flash messages for the specified namespaces
     *
     * Common namespaces are "default" (success), "warning" and "error".
     *
     * Input:
     *     $this->_flashMessenger->addMessage('Success message #1');
     *     $this->_flashMessenger->setNamespace('warning')->addMessage('Warning message #1');
     *     $this->_flashMessenger->setNamespace('error')->addMessage('Error message #1');
     *     $this->_flashMessenger->setNamespace('default')->addMessage('Success message #2');
     *     $this->_flashMessenger->addMessage('Success message #3');
     *
     * Output:
     *    <div id="message-container">
     *        <div class="namespace namespace-default">
     *            <ul class="list">
     *                <li class="message default">Success message #1</li>
     *                <li class="message default">Success message #2</li>
     *                <li class="message default">Success message #3</li>
     *            </ul>
     *        </div>
     *        <div class="namespace namespace-warning message warning">Warning message #1</div>
     *        <div class="namespace namespace-error message error">Error message #1</div>
     *    </div>
     *
     * @param  array          $containerAttributes
     * @param  Zend_Translate $translator
     * @param  array          $namespaces
     * @return string HTML of output messages
     */
    public function flashMessages(array $containerAttributes = array(), $translator = null,
        $namespaces = array('default', 'warning', 'error'))
    {
        $this->_flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
        $this->_translator = $translator;

        $output = null;

        // Get messages for the specified namespaces
        foreach ($namespaces as $namespace) {
            $messages = $this->_flashMessenger->setNamespace($namespace)->getMessages();
            $messages = array_merge($messages, $this->_flashMessenger->setNamespace($namespace)->getCurrentMessages());
            $this->_flashMessenger->setNamespace($namespace)->clearCurrentMessages();

            if (count($messages) > 0) {

                if (count($messages) == 1) {
                    $output .= sprintf('<div class="namespace namespace-%s message %s">%s</div>',
                        $namespace, $namespace, $this->_translate($messages[0])) . "\n";
                } else {
                    // Create listing with multiple messages
                    $list = '<ul class="list">' . "\n";
                    foreach ($messages as $message) {
                        $list .= sprintf('<li class="message %s">%s</li>', $namespace, $this->_translate($message)) ."\n";
                    }
                    $list .= '</ul>' . "\n";

                    $output .= '<div class="namespace namespace-'. $namespace .'">'. "\n" . $list . '</div>' . "\n";
                }
            }
        }

        $this->_flashMessenger->resetNamespace();

        if ($output !== null) {
            $containerAttributes = array_merge(
                array('id' => 'message-container'),
                $containerAttributes
            );

            $attributesString = '';
            foreach ($containerAttributes as $key => $value) {
                $attributesString .= sprintf(' %s="%s"', $key, $value);
            }

            $output = sprintf('<div%s>', $attributesString) . "\n" . $output . '</div>';
        }

        return $output;
    }

    /**
     * Translates a string with the specified translator
     *
     * @param  string $string
     * @return string
     */
    protected function _translate($string)
    {
        if ($this->_translator instanceof Zend_Translator) {
            return $this->_translator->_($string);
        } else {
            return $string;
        }
    }
}