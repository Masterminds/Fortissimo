<?php
/**
 * @file
 * The abstract command wrapper.
 */
namespace Fortissimo\Command\Flow;
/**
 * The abstract command wrapper.
 *
 * A command wrapper provides a layer if flow control around a
 * specific command. In doing so, it makes it possible to,
 * for instance, do minimal flow logic before executing a
 * command.
 *
 * The crucial technical capability of a command wrapper
 * is its ability to proxy data from the real chain of commands
 * into the wrapped command, while still being able to
 * interact on its own with the chain of command.
 *
 * For example, a wrapper will likely have its own params,
 * which it needs in order to determine how to interact.
 * But it also needs to be able to pass parameters on to 
 * the called class.
 *
 * Expected Wrapper Behavior:
 *
 * - A wrapper SHOULD pass its subcommand(s) the real
 *   ExecutionContext.
 * - A wrapper SHOULD hand datasources and loggers
 *   to the subcommand.
 * - A wrapper MUST re-fetch params from the context
 *   each time it executes a subcommand. This way
 *   changes in the context are propogated correctly.
 *
 * Internally, this clais overrides the parameter handling to allow
 * additional parameters to be passed through to the subcommand.
 */
abstract class Wrapper extends \Fortissimo\Command\Base {

  protected $childParams = array();
  protected $ff = NULL;

  /*
  abstract public function doCommand();
  abstract public function expects();
   */

  protected function prepareParameters($params) {
    parent::prepareParameters($params);

    $myParams = array_keys($this->parameters);

    foreach ($myParams as $name) {
      unset($params[$name]);
    }

    //fwrite(STDOUT, implode('===', array_keys($params)) . PHP_EOL);

    $this->childParams = $params;
  }

  /**
   * Parameters that should be passed through to the wrapped command.
   *
   * These are params that are not used by the wrapper, and so should
   * be passed through (where applicable) to the inner wrapped command.
   *
   * @return array
   * @retval array
   *   The parameters to be passed through to the underlying command.
   *
   */
  protected function passthruParams() {
    return $this->childParams;
  }

  /**
   * Given an alias map, rename certain given parameters.
   *
   * This takes a mapping of aliases to names, and renames
   * any parameters from the alias name to the real name.
   *
   * This is used for passing parameters into the wrapped command
   * in cases where both the wrapper and the command declare
   * parameters with the same name.
   *
   * @param array $aliases
   *   A mapping of alias to real name.
   */
  protected function parameterAliases($aliases) {
    foreach ($aliases as $alias => $real) {
      if (isset($this->childParams[$alias])) {
        $this->childParams[$real] = $this->childParams[$alias];
        unset($this->childParams[$alias]);
      }
    }
  }

}
