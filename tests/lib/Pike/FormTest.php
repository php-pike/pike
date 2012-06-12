<?php

class FormTestCase extends PHPUnit_Framework_TestCase {

    public function testValidation() {
        $form = new Pike_Form(array('csrfToken' => false));

        $element = new Zend_Form_Element_Text('testfield');
        $element->setLabel('Testfield')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array(
                            'min' => 3,
                            'max' => '5'
                        )));

        $form->addElement($element);

        $actual = $form->isValid(array('testfield' => 'te'));

        $this->assertFalse($actual);
    }

    public function testFormHtml() {
        $form = new Pike_Form(array('csrfToken' => false));

        $element = new Zend_Form_Element_Text('testfield');
        $element->setLabel('Testfield')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array(
                            'min' => 3,
                            'max' => '5'
                        )));

        $form->addElement($element);
        
        $expected = <<<EOS
<form enctype="application/x-www-form-urlencoded" action="" method="post"><div class="form-container">

<div id="form-item-testfield" class="form-item"><label id="label-testfield" class="required" for="form-element-testfield">Testfield:<span class="asterisk">*</span></label><input type="text" name="testfield" id="form-element-testfield" value="" class="form-text"></div></div></form>
EOS;
        //attach view because we aren't in a ZF scope
        $actual = $form->render(new Zend_View());
        
        $this->assertEquals($expected, $actual);
        
    }

}