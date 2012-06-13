<?php

class DateCompareTestCase extends PHPUnit_Framework_TestCase {

    public function testNotExactMatch() {        
        $token = new DateTime('yesterday');
        
        $validator = new Pike_Validate_DateCompare($token, '=', 'Y-m-d');
        $compare = $validator->isValid(date('Y-m-d'));
        
        $this->assertFalse($compare);
        $this->assertEquals(array(0 => 'notSame'), $validator->getErrors());
    }
    
    public function testIsGreaterThen() {
        $token = new DateTime('+1 day');
        
        $validator = new Pike_Validate_DateCompare($token, '>', 'Y-m-d');
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('-1 day')));

        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsLessThen() {
        $token = new DateTime('-1 day');
        
        $validator = new Pike_Validate_DateCompare($token, '<', 'Y-m-d');
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('+1 day')));

        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsNotLessThen() {
        $token = new DateTime('+1 day');
        
        $validator = new Pike_Validate_DateCompare($token, '<', 'Y-m-d');
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('-1 day')));

        $this->assertFalse($compare);
        $this->assertEquals(array(0 => 'notLater'), $validator->getErrors());        
    }
    
    public function testIsLessOrEqualThen() {
        $token = new DateTime('+1 day');
        
        $validator = new Pike_Validate_DateCompare($token, '<=', 'Y-m-d');
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('+1 day')));

        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsGreaterOrEqualThen() {
        $token = new DateTime('+1 day');
        
        $validator = new Pike_Validate_DateCompare($token, '>=', 'Y-m-d');
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('+1 day')));

        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
}