<?php

class FormRadioTestCase extends PHPUnit_Framework_TestCase
{

    public function testformRadio()
    {
        $helper = new Pike_View_Helper_FormRadio();
        $helper->setView(new Zend_View());
        $actual = $helper->formRadio(
                'test', 'option2', array(
            'id' => 'PikeRadioThing',
            'class' => 'GiveMeSomeClassFriend'
                ), array(
            'option1' => 'option1_label',
            'option2' => 'option2_label',
            'option3' => 'option3_label'
                ), "seperation"
        );

        $expected = <<<EOS
<label id="label-test-option1" for="PikeRadioThing-option1"><input type="radio" name="test" id="PikeRadioThing-option1" value="option1" class="GiveMeSomeClassFriend">option1_label</label>seperation<label id="label-test-option2" for="PikeRadioThing-option2"><input type="radio" name="test" id="PikeRadioThing-option2" value="option2" checked="checked" class="GiveMeSomeClassFriend">option2_label</label>seperation<label id="label-test-option3" for="PikeRadioThing-option3"><input type="radio" name="test" id="PikeRadioThing-option3" value="option3" class="GiveMeSomeClassFriend">option3_label</label>
EOS;
        $this->assertEquals($expected, $actual);
    }

    public function testAllDisabled()
    {
        $helper = new Pike_View_Helper_FormRadio();
        $helper->setView(new Zend_View());
        $actual = $helper->formRadio(
            'test', 'option2', array(
                'id' => 'PikeRadioThing',
                'class' => 'GiveMeSomeClassFriend',
                'disable' => true,
            ), array(
                'option1' => 'option1_label',
                'option2' => 'option2_label',
                'option3' => 'option3_label'
            ), "seperation"
        );

        $expected = <<<EOS
<label id="label-test-option1" for="PikeRadioThing-option1"><input type="radio" name="test" id="PikeRadioThing-option1" value="option1" disabled="disabled" class="GiveMeSomeClassFriend">option1_label</label>seperation<label id="label-test-option2" for="PikeRadioThing-option2"><input type="radio" name="test" id="PikeRadioThing-option2" value="option2" checked="checked" disabled="disabled" class="GiveMeSomeClassFriend">option2_label</label>seperation<label id="label-test-option3" for="PikeRadioThing-option3"><input type="radio" name="test" id="PikeRadioThing-option3" value="option3" disabled="disabled" class="GiveMeSomeClassFriend">option3_label</label>
EOS;

        $this->assertEquals($expected, $actual);
    }

    public function testSomeDisabled()
    {
        $helper = new Pike_View_Helper_FormRadio();
        $helper->setView(new Zend_View());
        $actual = $helper->formRadio(
            'test', 'option2', array(
                'id' => 'PikeRadioThing',
                'class' => 'GiveMeSomeClassFriend',
                'disable' => array('option1', 'option2'),
            ), array(
                'option1' => 'option1_label',
                'option2' => 'option2_label',
                'option3' => 'option3_label'
            ), "seperation"
        );
        
        $expected = <<<EOS
<label id="label-test-option1" for="PikeRadioThing-option1"><input type="radio" name="test" id="PikeRadioThing-option1" value="option1" disabled="disabled" class="GiveMeSomeClassFriend">option1_label</label>seperation<label id="label-test-option2" for="PikeRadioThing-option2"><input type="radio" name="test" id="PikeRadioThing-option2" value="option2" checked="checked" disabled="disabled" class="GiveMeSomeClassFriend">option2_label</label>seperation<label id="label-test-option3" for="PikeRadioThing-option3"><input type="radio" name="test" id="PikeRadioThing-option3" value="option3" class="GiveMeSomeClassFriend">option3_label</label>
EOS;
        
        
        $this->assertEquals($expected, $actual);
    }

}