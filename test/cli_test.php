<?php
require_once '../Fortissimo/skel/src/Fortissimo.php';

class AbstractCommandMock implements FortissimoCommand {
  protected $name = NULL;
  protected $cxt = NULL;
  protected $params = NULL;
  
  public function __construct($name) {
    $this->name = $name;
  }
  
  public function execute($paramArray, FortissimoExecutionContext $cxt) {
    if ($paramArray['retval'])
      $cxt->put($this->name, $paramArray['retval']);
  }
}

class CommandMockOne extends AbstractCommandMock {
}
class CommandMockTwo extends AbstractCommandMock {
}
class CommandMockThree extends AbstractCommandMock {
}

$requestName = 'dummy';
$cfg = './test_commands.xml';
$fc = new FortissimoConfig($cfg);

$req = $fc->getRequest($requestName);

var_dump($req);

