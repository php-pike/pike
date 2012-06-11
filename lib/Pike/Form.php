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
 * Pike form
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Form extends Zend_Form
{
    /**
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * TTL for CSRF token in seconds
     *
     * @var int
     */
    protected $_csrfTimeout = 300;

    /**
     * Submit buttons that must be grouped together
     *
     * @var array
     */
    private $_submitButtons = array();

    /**
     * Initializes the form
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (Zend_Registry::isRegistered('Zend_Translate')) {
            $this->_translate = Zend_Registry::get('Zend_Translate');
        }

        $addCsrfToken = true;
        if (isset($options['csrfToken']) && false === $options['csrfToken']) {
            $addCsrfToken = false;
        }
        unset($options['csrfToken']);

        if (true === $addCsrfToken) {
            $this->_addCsrfToken();
        }

        // Set form decorators (These decorators do NOT apply to form element, only the form itself)
        $this->setDisableLoadDefaultDecorators(true);
        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div', 'class' => 'form-container')),
            'Form'
        ));

        parent::__construct($options);
    }

    /**
     * Adds a form element
     */
    public function addElement($element, $name = null, $options = null)
    {
        parent::addElement($element, $name, $options);

        if (is_string($element)) {
            $element = $this->getElement($name);
        }

        $element->addPrefixPath('Pike_Form_Decorator', 'Pike/Form/Decorator', 'decorator');
        $element->setDecorators(array('Composite'));
    }

    /**
     * Render form
     *
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        $this->prepareRendering();
        return parent::render($view);
    }

    /**
     * Prepares the form rendering
     */
    public function prepareRendering()
    {
        $this->_groupSubmitButtons();
    }

    /**
     * Validates the form
     *
     * @param  array            $data
     * @param  callable|boolean $callback
     * @return boolean
     */
    public function isValid($data, $callback = null)
    {
        $isValid = parent::isValid($data);
        if (!$isValid) {
            if (false !== $callback) {
                if (null !== $callback && is_callable($callback)) {
                    return call_user_func($callback, $isValid);
                } else {
                    $this->_addErrorMessagesToFlashMessenger($this);
                    $this->_getMessagesFromSubForms($this->getSubForms(), $this);
                    $this->_addErrorClassToInvalidElements($this);
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * Adds submit buttons that must be grouped
     *
     * @param array $submitButtons An associative array with Zend_Form_Element objects
     */
    public function addSubmitButtons(array $submitButtons)
    {
        foreach ($submitButtons as $submitButton) {
            $this->addSubmitButton($submitButton);
        }
    }

    /**
     * Adds a submit button that must be grouped
     *
     * @param Zend_Form_Element $submitButton
     */
    public function addSubmitButton(Zend_Form_Element $submitButton)
    {
        $this->addElement($submitButton);
        $this->_submitButtons[] = $submitButton;
    }

    /**
     * Removes the submit button with the specified name
     *
     * @param string $name
     */
    public function removeSubmitButton($name)
    {
        $this->removeElement($name);
        foreach ($this->_submitButtons as $index => $submitButton) {
            if ($name == $submitButton->getName()) {
                unset($this->_submitButtons[$index]);
                break;
            }
        }
    }

    /**
     * Clears all submit buttons for grouping
     */
    public function clearSubmitButtons()
    {
        $this->_submitButtons = array();
    }

    /**
     * Sets values for the specified elements
     *
     * @param array $values An associative array with element name as key
     * @param array $prefix If specified, values are only set for fields that start
     *                      with the specified prefix
     */
    public function setElementValues(array $values, $prefix = null)
    {
        /* @var $element Zend_Form_Element */
        foreach ($this->getElements() as $element) {
            $name = str_replace($prefix, '', $element->getName());
            if (isset($values[$name])) {
                $element->setValue($values[$name]);
            }
        }
    }

    /**
     * Retrieves the messages from sub forms and adds them to the flash messenger
     *
     * @param array     $subForms
     * @param Zend_Form $parentForm
     */
    protected function _getMessagesFromSubForms(array $subForms, Zend_Form $parentForm)
    {
        foreach ($subForms as $subForm) {
            $this->_addErrorMessagesToFlashMessenger($subForm);
            $this->_addErrorClassToInvalidElements($subForm);

            /* @var $subForm Zend_Form_SubForm */
            if (count($subForm->getSubForms()) > 0) {
                $this->_getMessagesFromSubForms($subForm->getSubForms(), $subForm);
            }
        }
    }

    /**
     * Adds form error messages to the flash messenger
     *
     * @param Zend_Form $form
     */
    protected function _addErrorMessagesToFlashMessenger(Zend_Form $form)
    {
        $flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');

        foreach ($form->getElements() as $element) {
            if ($element instanceof Zend_Form_Element) {
                $messages = $element->getMessages();
                foreach ($messages as $message) {
                    $label = $element->getLabel();
                    $label = $label != '' ? sprintf('<strong>%s:</strong> ', $label) : null;
                    $message = $label . $message;

                    $displayGroupLegend = '';
                    $displayGroups = $form->getDisplayGroups();
                    foreach ($displayGroups as $displayGroup) {
                        foreach ($displayGroup->getElements() as $displayGroupElement) {
                            if ($displayGroupElement->name == $element->name) {
                                $displayGroupLegend = $displayGroup->getLegend();
                                break;
                            }
                        }
                    }

                    if ('' !== $displayGroupLegend) {
                        $message = sprintf('%s | ', $displayGroupLegend) . $message;
                    }

                    if (null !== $form->getLegend()) {
                        $message = sprintf('%s | ', $form->getLegend()) . $message;
                    }

                    $flashMessenger->setNamespace('error')->addMessage($message);
                }
            }
        }
    }

    /**
     * Adds an "error" CSS class to invalid form elements
     *
     * @param Zend_Form $form
     */
    protected function _addErrorClassToInvalidElements(Zend_Form $form)
    {
        foreach ($form->getElements() as $element) {
            if ($element instanceof Zend_Form_Element) {
                $errors = $element->getErrors();
                if (count($errors) > 0) {
                    $element->setAttrib('class', trim($element->getAttrib('class') . ' error'));
                    $element->removeDecorator('Errors');
                }
            }
        }
    }

    /**
     * Groups submit buttons
     */
    protected function _groupSubmitButtons()
    {
        $elements = $this->getElements();
        foreach ($elements as $element) {
            if (in_array($element->getType(), array('Zend_Form_Element_Submit', 'Zend_Form_Element_Reset'))) {
                if (!in_array($element->getName, $this->_submitButtons)) {
                    $this->_submitButtons[] = $element->getName();
                }
            }
        }

        if (count($this->_submitButtons) > 0) {
            $this->addDisplayGroup($this->_submitButtons, 'submitButtons', array(
                'decorators' => array(
                    'FormElements',
                )
            ));

            $displayGroup = $this->getDisplayGroup('submitButtons');
            $displayGroup->setOrder(100);
            $displayGroup->addPrefixPath('Pike_Form_Decorator', 'Pike/Form/Decorator', 'decorator');
            $displayGroup->addDecorator('SubmitButtons');
        }
    }

    /**
     * Adds a form element with the CSRF token
     */
    protected function _addCsrfToken()
    {
        $element = new Zend_Form_Element_Hash('csrfToken');
        $element->setSalt(hash('sha256', php_uname() . uniqid(rand(), true)));
        $element->setTimeout($this->_csrfTimeout);
        $element->getValidator('Identical')
                ->setMessage('The form is expired or no CSRF token was provided to match against',
                    Zend_Validate_Identical::MISSING_TOKEN);
        $this->addElement($element);
    }

    /**
     * Sets a CSRF token that causes the form to only expire base on time instead of hops also.
     *
     * Several AJAX actions in the form will otherwise cause various hops which will make the
     * CSRF token disappear and results in a token mismatch.
     *
     * IMPORTANT: If you want to use this method pass the option csrfToken with value FALSE to the
     * constructor of this form.
     *
     * @param string  $namespace
     * @param boolean $regenerate
     */
    public function setExpirationTimeOnlyCsrfToken($namespace = null, $regenerate = false)
    {
        $this->removeElement('csrfToken');

        $request = Zend_Controller_Front::getInstance()->getRequest();

        if (null === $namespace) {
            $namespaceName = 'csrfToken_' . implode('_', array(
                $request->getModuleName(),
                $request->getControllerName(),
                $request->getActionName()
            ));
        } else {
            $namespaceName = $namespace;
        }

        $namespace = new Zend_Session_Namespace($namespaceName);
        $namespace->setExpirationSeconds($this->_csrfTimeout);

        if (true === $regenerate) {
            $namespace->unsetAll();
        }

        $csrfToken = $namespace->csrfToken;
        if (null === $csrfToken) {
            $namespace->csrfToken = hash('sha256', php_uname() . uniqid(rand(), true));
        }

        $element = new Zend_Form_Element_Hidden('csrfToken');
        $element->setValue($namespace->csrfToken)
                ->setRequired(true)
                ->addValidator('Identical', true, array($csrfToken))
                ->getValidator('Identical')->setMessage('The form is expired or no CSRF token was'
                    . ' provided to match against', Zend_Validate_Identical::MISSING_TOKEN);
        $this->addElement($element);
    }

    /**
     * Sets the timeout for the CSRF token in seconds
     *
     * @param integer $seconds
     */
    public function setCsrfTimeout($seconds)
    {
        $this->_csrfTimeout = (int) $seconds;
    }
}