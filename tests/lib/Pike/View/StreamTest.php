<?php
class StreamTestCase extends PHPUnit_Framework_TestCase {
    public function testAutoEscape() {
        $data = <<<EOS
<?php
    echo \$somevar;
    ?>
    
<?=\$anotherVar?>

EOS;
        
$expected = <<<EOS
<?php echo \$this->escape((string)\$somevar); ?>
    
<?php echo \$this->escape((string)\$anotherVar); ?>

EOS;
        
        $viewStream = new Pike_View_Stream();
        $actual = $viewStream->autoEscape($data);
        
        $this->assertEquals($expected, $actual);
    }
    
    public function testStreamOpen() {
        
        $path = __DIR__ . '/../../../assets/viewstreamview.phtml';
        
        $viewStream = new Pike_View_Stream();
        $result = $viewStream->stream_open($path, null, null, $path);
        
        $actual = $viewStream->stream_read(3000);
        
        $expected = <<<EOS
<div>
    <?php echo \$this->escape((string)'hello world'); ?>
</div>
<div>
    <?php echo \$this->escape((string)\$var); ?>
</div>
EOS;
        
        $this->assertEquals($expected, $actual);
        $this->assertTrue($result);
    }
}