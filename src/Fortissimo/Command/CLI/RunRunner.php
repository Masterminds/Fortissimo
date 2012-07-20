<?php
namespace Fortissimo\Command\CLI;

class RunRunner extends \Fortissimo\Command\Base {

  public function expects() {
    return $this
      -> description('Runs a CLI runner inside of a runner.')
      -> usesParam('route', 'The route to run')->whichIsRequired()
      -> usesParam('registry', 'A Registry.')
      -> usesParam('args', 'An array of arguments, like argv.')
      // -> usesParam('runner', 'The classname of the runner to run.')->whichIsRequired()
      -> andReturns('Nothing, but the context will be modified.')
      ;

  }
  public function doCommand() {

    //$runnerName = $this->param('runner');
    $registry = $this->param('registry', NULL);
    $route = $this->param('route');
    $args = $this->param('args', array());

    // We use a special runner for this.
    $runner = new _InnerCLIRunner($args);

    $this->initializeRegistry($registry, $runner);

    $runner->setContext($this->context);

    $runner->run($route);
  }

  protected function initializeRegistry($reg, $runner) {
    if (empty($reg)) {
      $registry = $this->context->registry();
    }
    /*
    elseif (is_string($reg)) {
      $registry = new Registry('internal');
      // Some use $register.
      $register =& $registry;
      require_once $reg;
    }
     */
    else {
      $registry = $reg;
    }

    $runner->useRegistry($registry);
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
