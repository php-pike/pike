<?php

class DebugTestCase extends PHPUnit_Framework_TestCase {

    public function testDump() {
        $dump = Pike_Debug::dump(array(
                    'test' => 'test123',
                    0 => new stdClass()
                        ), 1, 'some test', false);

        $shouldBe = <<<EOS

some test 
Array
(
    [test] => 'test123'
    [0] => stdClass(...)
)

EOS;

        $this->assertEquals($shouldBe, $dump);
    }

    public function testDepth() {
        $dump = Pike_Debug::dump(array(
                    'test' => array(
                        'level1' => array(
                            'level2' => array(
                                'level3' => array(
                                    'level4' => 'yay!'
                                )
                            )
                    )),
                    'someLevel1' => 'Is it so?',
                    'TakeItDeeper' => array(
                        'Yeah, we are one 2!' => 'smooth',
                        'someValue'
                        )), 2, null, false);

        $shouldBe = <<<EOS


Array
(
    [test] => Array
    (
        [level1] => Array(...)
    )
    [someLevel1] => 'Is it so?'
    [TakeItDeeper] => Array
    (
        [Yeah, we are one 2!] => 'smooth'
        [0] => 'someValue'
    )
)

EOS;

        $this->assertEquals($shouldBe, $dump);
    }

    /**
     * Pike_Debug should print $dump variable directly to php://out unless    
     * a third parameter false is given.
     */
    public function testOutputBuffer() {
        ob_start();
        
        $dump = Pike_Debug::dump(array(
                    'test' => 'test123',
                    0 => new stdClass()
                        ), 1, 'dump test');

        $shouldBe = <<<EOS

dump test 
Array
(
    [test] => 'test123'
    [0] => stdClass(...)
)

EOS;
        $dump = ob_get_clean();
        
        $this->assertEquals($shouldBe, $dump);
    }

}