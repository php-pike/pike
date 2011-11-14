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
 * Date range sub form
 *
 * Makes it possible to render a daterange field with two input elements where
 * in every element is a date expected.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Form_SubForm_DateRange extends Pike_Form_SubForm
{
    /**
     * Constructor
     *
     * @param string $label
     * @param array  $options
     */
    public function __construct($label, $options = array())
    {
        parent::__construct($options);

        $this->setDecorators(array(
            'FormElements',
            array('HtmlTag', array('tag' => 'div', 'class' => 'form-daterange form-item')),
        ));

        $element = new Zend_Form_Element_Text('start');
        $element->setLabel($label);
        $element->setAttrib('class', 'datepicker');
        $element->addValidator(new Zend_Validate_Date(array('format' => 'dd-MM-YYYY')), true);
        $this->addElement($element);

        $decorator = $element->getDecorator('composite');
        $decorator->addContainerClass('daterange-start');

        $element = new Zend_Form_Element_Text('end');
        $element->setLabel($label);
        $element->setAttrib('class', 'datepicker');
        $element->addValidator(new Zend_Validate_Date(array('format' => 'dd-MM-YYYY')), true);
        $element->addValidator(new Pike_Validate_DateCompare($this->getElement('start'), '<=', 'd-m-Y'));
        $this->addElement($element);

        $decorator = $element->getDecorator('composite');
        $decorator->addContainerClass('daterange-end');
        $decorator->setRenderLabel(false);
    }
}