<?php

class DateRangeTestCase extends PHPUnit_Framework_TestCase {

    public function testIsBetween() {
        $start = strftime('%Y-%m-%d', strtotime('-10 day'));
        $end = strftime('%Y-%m-%d', strtotime('+10 day'));
        
        $validator = new Pike_Validate_DateRange(array('lt' => $start, 'gt' => $end));
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('now')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsGreaterThen() {
        $validator = new Pike_Validate_DateRange(array('gt' => strftime('%Y-%m-%d', strtotime('-1 day'))));
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('now')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }

    public function testIsGreaterOrEqualsThen() {
        $validator = new Pike_Validate_DateRange(array('gt' => strftime('%Y-%m-%d', strtotime('now')), 'eq' => true));
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('now')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }    
    
    public function testIsLessThen() {
        $validator = new Pike_Validate_DateRange(array('lt' => strftime('%Y-%m-%d', strtotime('+1 day'))));
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('now')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsLessOrEqualsThen() {
        $validator = new Pike_Validate_DateRange(array('lt' => strftime('%Y-%m-%d', strtotime('now')), 'eq' => true));
        $compare = $validator->isValid(strftime('%Y-%m-%d', strtotime('now')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }

}