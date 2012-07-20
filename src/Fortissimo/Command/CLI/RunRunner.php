<?php
namespace Fortissimo\Command\CLI;

class RunRunner extends \Fortissimo\Command\Base {

  public function expects() {
    return $this
      -> description('Runs a CLI runner inside of a runner.')
      -> usesParam('registry', 'A Registry.')->whichIsRequired()
      -> usesParam('args', 'An array of arguments, like argv.')
      // -> usesParam('runner', 'The classname of the runner to run.')->whichIsRequired()
      -> andReturns('Nothing, but the context will be modified.')
      ;

  }
  public function doCommand() {

    //$runnerName = $this->param('runner');
    $registry = $this->param('registry');
    $args = $this->param('args', array());

    $registry = $this->initializeRegistry($registry);

    $runner = new _InnerCLIRunner($args, $in, $out);
    $runner->useRegistry($registry);
    $runner->setContext($this->context);

    $runner->run($cmd);
  }

  protected function initializeRegistry($reg) {
    if (is_string($reg)) {
      $registry = new Registry('internal');
      // Some use $register.
      $register =& $registry;
      require_once $reg;
    }
    else {
      $registry = $reg;
    }

    return $registry;
  }

}

/**
 * A special-purpose runtime.
 *
 * This is not for general use.
 *
 */
class _InnerCLIRunner extends \Fortissimo\Runtime\CLIRunner {

  protected $_context;

  public function setContext($cxt) {
    $this->_context = $cxt;

  }

  public function initialContext() {
    return $this->_context;
  }
}
