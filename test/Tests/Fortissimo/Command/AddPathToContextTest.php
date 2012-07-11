<?php
namespace Fortissimo\Tests;
$base = dirname(__DIR__);
require_once $base . '/TestCase.php';

/**
 * @group command
 */
class AddPathToContext extends TestCase {
  public function testDoCommand() {
    $reg = $this->registry();

    $reg->route('parsepath')
      ->does('\Fortissimo\Command\Context\AddPathToContext', 'i')
      ->using('path')->from('g:path')
      ->using('template')->from('g:temp')// '%s/%s/%d')
      ->using('names', array('name', 'type', 'record_id'))
      ;

    $_GET['path'] = 'foo/bar/123';
    $_GET['temp'] = '%s/%s/%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEquals('bar', $cxt->get('type'));
    $this->assertEquals(123, $cxt->get('record_id'));

    $_GET['path'] = 'start-foo/bar/id-123';
    $_GET['temp'] = 'start-%s/%s/id-%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEquals('bar', $cxt->get('type'));
    $this->assertEquals(123, $cxt->get('record_id'));

    // Use character ranges
    $_GET['path'] = 'start-foo-end/bar/id-123';
    $_GET['temp'] = 'start-%[a-z]-end/%s/id-%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEquals('bar', $cxt->get('type'));
    $this->assertEquals(123, $cxt->get('record_id'));

    // Skip an argument, test signed int
    $_GET['path'] = 'foo/skip/bar/-123';
    $_GET['temp'] = '%s/%*s/%s/%i';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEquals('bar', $cxt->get('type'));
    $this->assertEquals(-123, $cxt->get('record_id'));

    // Skip an argument, test signed int
    $_GET['path'] = 'foo/skip/b/123';
    $_GET['temp'] = '%2so/%*s/%c/%2d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('fo', $cxt->get('name'));
    $this->assertEquals('b', $cxt->get('type'));
    $this->assertEquals(12, $cxt->get('record_id'));

    // Test partial matches
    $_GET['path'] = 'foo/bar/123';
    $_GET['temp'] = '%s/%d/%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEmpty($cxt->get('type'));
    $this->assertEmpty($cxt->get('record_id'));

    $_GET['path'] = 'foo/bar';
    $_GET['temp'] = '%s/%s/%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEquals('bar', $cxt->get('type'));
    $this->assertEmpty($cxt->get('record_id'));

    $_GET['path'] = 'foo/';
    $_GET['temp'] = '%s/%d/%d';

    $runner = $this->runner($reg);
    $cxt = $runner->run('parsepath');

    $this->assertEquals('foo', $cxt->get('name'));
    $this->assertEmpty($cxt->get('type'));
    $this->assertEmpty($cxt->get('record_id'));
  }
}
