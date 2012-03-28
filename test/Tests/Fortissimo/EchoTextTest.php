<?php
namespace Fortissimo\Tests;
require 'TestCase.php';

class EchoTextTest extends TestCase {

  public function testDoCommand() {
    $reg = $this->registry(__CLASS__);
    $reg->route('default')->does('\Fortissimo\Command\EchoText', 'echo')->using('text', 'Echo');

    $runner = $this->runner($reg);

    ob_start();
    $runner->run('default');
    $c = ob_get_contents();
    ob_end_clean();
    $c = trim($c);
    $this->assertEquals('Echo', $c);
  }
}
