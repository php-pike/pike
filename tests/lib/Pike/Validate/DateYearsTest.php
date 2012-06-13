<?php

class DateYearsTestCase extends PHPUnit_Framework_TestCase {

    public function testIsGreaterThen() {
        $validator = new Pike_Validate_DateYears(1, '>');
        $compare = $validator->isValid(strftime('%m-%d-%Y', strtotime('+2 year')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsGreaterOrEqualThen() {
        $validator = new Pike_Validate_DateYears(1, '>=');
        $compare = $validator->isValid(strftime('%m-%d-%Y', strtotime('+1 year')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());        
    }
    
    public function testIsLessThen() {
        $validator = new Pike_Validate_DateYears(1, '<');
        $compare = $validator->isValid(strftime('%m-%d-%Y', strtotime('-2 year')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());
    }
    
    public function testIsLessOrEqualThen() {
        $validator = new Pike_Validate_DateYears(1, '<=');
        $compare = $validator->isValid(strftime('%m-%d-%Y', strtotime('-1 year')));
        
        $this->assertTrue($compare);
        $this->assertEquals(array(), $validator->getErrors());
    }
    
}