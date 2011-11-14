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
 * Pike form composite decorator
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Form_Decorator_Composite
    extends Zend_Form_Decorator_Abstract
    implements Zend_Form_Decorator_Interface
{
    /**
     * @var array
     */
    protected $_containerClasses = array();

    /**
     * If the label of the form element must be rendered
     * @var boolean
     */
    protected $_renderLabel = true;

    /**
     * @var Zend_Form_Element
     */
    protected $_element;

    /**
     * Adds the specified container class
     *
     * @param  string $class
     * @return boolean
     */
    public function addContainerClass($class)
    {
        if (!in_array($class, $this->_containerClasses)) {
            $this->_containerClasses[] = $class;
        }
    }

    /**
     * Defines wether to render a label or not. Even when you don't
     * render one, it's still possible a element has a label for the error messages for instance.
     *
     * @param boolean $toggle
     */
    public function setRenderLabel($toggle)
    {
        $this->_renderLabel = (bool) $toggle;
    }

    /**
     * Builds the form element label
     *
     * @return string
     */
    public function buildLabel()
    {
        $element = $this->_element;
        $label = $element->getLabel();
        if (null === $label || !$this->_renderLabel) {
            return null;
        }
        $translator = $element->getTranslator();
        if ($translator) {
            $label = $translator->translate($label);
        }
        $label = $element->getView()->escape($label);

        if ((string) $element->getBelongsTo() != '') {
            $belongsToPrefix = str_replace(']', '', str_replace('[', '_', (string) $element->getBelongsTo())) . '-';
        } else {
            $belongsToPrefix = '';
        }

        if ($element->isRequired()) {
            $labelTag = sprintf('<label id="label-%s" class="required" for="form-element-%s">%s:'
                . '<span class="asterisk">*</span></label>',
                $belongsToPrefix . $element->getName(), $belongsToPrefix . $element->getName(), $label);
        } else {
            $labelTag = sprintf('<label id="label-%s" for="form-element-%s">%s:</label>',
                $belongsToPrefix . $element->getName(), $belongsToPrefix . $element->getName(), $label);
        }

        return $labelTag;
    }

    /**
     * Builds the form element input
     *
     * @return string
     */
    public function buildInput()
    {
        $element = $this->_element;
        $helper  = $element->helper;
        $attributes = $element->getAttribs();
        unset($attributes['helper']);

        if ((string) $element->getBelongsTo() != '') {
            $belongsToPrefix = str_replace(']', '', str_replace('[', '_', (string) $element->getBelongsTo())) . '-';
        } else {
            $belongsToPrefix = '';
        }

        $attributes['id'] = sprintf('form-element-%s', $belongsToPrefix . $element->getName());

        $inflector = new Zend_Filter_Inflector(':string');
        $inflector->addRules(array(':string' => array('Word_CamelCaseToDash', 'StringToLower')));

        if (isset($attributes['class'])) {
            $attributes['class'] .= ' ' . $inflector->filter(array('string' => $helper));
        } else {
            $attributes['class'] = $inflector->filter(array('string' => $helper));
        }

        if (in_array($element->getType(), array('Zend_Form_Element_Submit', 'Zend_Form_Element_Button', 'Zend_Form_Element_Reset'))) {
            $value = $element->getLabel();
            $attributes['class'] .= ' ui-button ui-widget ui-state-default ui-corner-all';
            if ($element->getType() == 'Zend_Form_Element_Button') {
                $attributes['class'] .= ' ui-button-text-only';
            }
        } else {
            $value = $element->getValue();
        }

        if ($element->getBelongsTo() != '') {
            $name = sprintf('%s[%s]', $element->getBelongsTo(), $element->getName());
        } else {
            $name = $element->getName();
        }

        $separator = null;
        if (in_array($element->getType(), array('Zend_Form_Element_Radio'))) {
            $separator = $element->getSeparator();
        }

        return $element->getView()->$helper(
            $name,
            $value,
            $attributes,
            $element->options,
            $separator
        );
    }

    /**
     * Builds the form element errors
     *
     * @return string
     */
    public function buildErrors()
    {
        $element  = $this->_element;
        $messages = $element->getMessages();
        if (empty($messages)) {
            return '';
        }
        return sprintf('<div class="errors">%s</div>', $element->getView()->formErrors($messages));
    }

    /**
     * Builds the form element description
     *
     * @return string
     */
    public function buildDescription()
    {
        $element     = $this->_element;
        $description = $element->getDescription();
        if (empty($description)) {
            return '';
        }
        return sprintf('<div class="description">%s</div>', $description);
    }

    /**
     * Builds the form element container
     *
     * @param  string $part    Possible values "all", "before", "after"
     * @param  string $content Optional content that can be specified to append to the container
     * @return string
     */
    public function buildContainer($part = 'all', $content = null)
    {
        $output = '';

        $element = $this->_element;
        if ((string) $element->getBelongsTo() != '') {
            $belongsToPrefix = str_replace(']', '', str_replace('[', '_', (string) $element->getBelongsTo())) . '-';
        } else {
            $belongsToPrefix = '';
        }

        if ('all' == $part || 'before' == $part) {
            $output .= sprintf('<div id="form-item-%s" class="form-item%s">',
                $belongsToPrefix . $element->getName(),
                count($this->_containerClasses) > 0 ? ' ' . implode(' ', $this->_containerClasses) : ''
            );
        }

        // Add optionally specified content
        $output .= $content;

        if ('all' == $part || 'after' == $part) {
            $output .= '</div>';
        }

        return $output;
    }

    /**
     * Renders the form element
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        if (!$this->_setElement($element)) {
            return false;
        }

        $seperator   = $this->getSeparator();
        $placement   = $this->getPlacement();
        $label       = $this->buildLabel();
        $input       = $this->buildInput();
        $errors      = $this->buildErrors();
        $description = $this->buildDescription();

        if (in_array($element->helper, array('formSubmit', 'formButton', 'formHidden', 'formReset'))) {
            $output = $input;
        } else {
            $output = $this->buildContainer('before')
                . $label . $input . $description
                . $this->buildContainer('after');
        }

        switch ($placement) {
            case self::PREPEND:
                return $output . $seperator . $content;
            case self::APPEND:
            default:
                return $content . $seperator . $output;
        }
    }

    /**
     * Renders the container for the specified element
     *
     * @param  Zend_Form_Element $element
     * @param  string $part Possible values "all", "before", "after"
     * @param  string $content
     * @return string
     */
    public function renderContainer(Zend_Form_Element $element, $part = 'all', $content = null)
    {
        if (!$this->_setElement($element)) {
            return false;
        }

        return $this->buildContainer($part, $content);
    }

    /**
     * Renders the label for the specified element
     *
     * @param  Zend_Form_Element $element
     * @return string
     */
    public function renderLabel(Zend_Form_Element $element)
    {
        if (!$this->_setElement($element)) {
            return false;
        }

        return $this->buildLabel();
    }

    /**
     * Renders the view helper for the specified element
     *
     * @param  Zend_Form_Element $element
     * @return string
     */
    public function renderViewHelper(Zend_Form_Element $element)
    {
        if (!$this->_setElement($element)) {
            return false;
        }

        return $this->buildInput();
    }

    /**
     * Renders the description for the specified element
     *
     * @param  Zend_Form_Element $element
     * @return string
     */
    public function renderDescription(Zend_Form_Element $element)
    {
        if (!$this->_setElement($element)) {
            return false;
        }

        return $this->buildDescription();
    }

    /**
     * Renders the errors of the specified element
     *
     * @param  Zend_Form_Element $element
     * @return string
     */
    public function renderErrors(Zend_Form_Element $element)
    {
        if (!$this->_setElement($element)) {
            return false;
        }

        return $this->buildErrors();
    }

    /**
     * Sets the current form element
     *
     * @return boolean TRUE on success and FALSE on failure
     */
    protected function _setElement($element)
    {
        if (!$element instanceof Zend_Form_Element) {
            return false;
        }
        if (null === $element->getView()) {
            return false;
        }

        $this->_element = $element;
        return true;
    }
}