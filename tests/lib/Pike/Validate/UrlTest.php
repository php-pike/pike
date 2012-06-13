<?php

class UrlTestCase extends PHPUnit_Framework_TestCase {

    public function testUrlIsValid() {
        $validator = new Pike_Validate_Url();
        $compare = $validator->isValid('http://pike-project.org');

        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());
    }
    
    public function testUrlIsNotValid() {
        $validator = new Pike_Validate_Url();
        $compare = $validator->isValid('http:\\\\//pike-project.org');

        $this->assertFalse($compare);
        $this->assertEquals(array(0 => 'invalidUrl'), $validator->getErrors());
    }

}