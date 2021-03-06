<?php
namespace Fortissimo\Tests;
$base = dirname(__DIR__);
require_once $base . '/TestCase.php';

/**
 * @group command
 */
class ShowPHPInfoTest extends TestCase {

  public function testDoCommand() {
    $reg = $this->registry();
    $reg->route('test')->does('\Fortissimo\Command\Util\ShowPHPInfo', 'info');

    $runner = $this->runner($reg);

    ob_flush();
    ob_start();
    $cxt = $runner->run('test');
    $res = ob_get_clean();

    $this->assertRegExp('/PHP License/', $res);
  }
}
