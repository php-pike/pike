<?php

class SubFormTestCase extends PHPUnit_Framework_TestCase
{

    public function testSubFormHtml()
    {
        $form = new Pike_Form(array('csrfToken' => false));
        
        $element = new Zend_Form_Element_Text('test');
        $element->setRequired(true);
        $element->setLabel('Test field');
        
        $subForm = new Pike_Form_SubForm();
        $subForm->addElement($element);

        $form->addSubForm($subForm, 'Subform');
        
        $actual = $form->render(new Zend_View());
        $expected = <<<EOS
<form enctype="application/x-www-form-urlencoded" action="" method="post"><div class="form-container">
<fieldset id="fieldset-Subform"><div class="form-container">

<div id="form-item-Subform-test" class="form-item"><label id="label-Subform-test" class="required" for="form-element-Subform-test">Test field:<span class="asterisk">*</span></label><input type="text" name="Subform[test]" id="form-element-Subform-test" value="" class="form-text"></div></div></fieldset></div></form>
EOS;
        
        $this->assertEquals($expected, $actual);
        
    }

}