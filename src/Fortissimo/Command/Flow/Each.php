<?php
/**
 * @file
 * The Each command.
 */
namespace Fortissimo\Command\Flow;
/**
 * The Each command.
 *
 * Provides each-based looping for flow control.
 *
 * If 'command' is a callback (such as a closure), the function will be called like this:
 *
 * @code
 * <?php
 *   $fn($context, $commandName, $params);
 * ?>
 * @endcode
 */
class Each extends Wrapper {
  public function expects() {
    return $this
      -> description('Loop through a list, running a route for each item in the list.')
      -> usesParam('list', 'An array through which this will loop.')->whichIsRequired()
      -> usesParam('command', 'A command to execute. The command will be run repeatedly and its results acculated.')->whichIsRequired()
      -> usesParam('commandName', 'The name of the command.')
      -> usesParam('aliases', 'Provide aliases for parameters that need to be passed through.')
      ;
  }
  public function doCommand() {
    $list = $this->param('list');
    $command = $this->param('command');
    $aliases = $this->param('aliases');
    $name = $this->param('commandName');

    if (empty($name)) {
      $name = md5(rand());
    }

    if (!empty($aliases)) {
      $this->parameterAliases($aliases);
    }

    if (is_callable($command)) {
      $result = $this->processCallbackLoop($list, $command, $name);
    }
    else {
      $result = $this->processCommandLoop($list, $command, $name);
    }

    return $result;
  }

  protected function processCallbackLoop($list, $fn, $name) {
    $results = array();
    foreach ($list as $k => $v) {
      // Add the list item to the context.
      $this->context->add($this->name . '_key', $k);
      $this->context->add($this->name . '_value', $v);
      $res = $fn($this->context, $name, $this->passthruParams());

      $this->context->add($name, $res);
      $results[] = $res;
    }
    return $results;
  }

  protected function processCommandLoop($list, $command, $name) {
    //$this->ff = $this->context->fortissimo();
    $results = array();
    foreach ($list as $k => $v) {
      $params = $this->passthruParams();
      $params = $this->replaceAdHocFrom($params, $k, $v);

      $results[] = $this->fireInnerCommand($command, $name, $params);
    }

    return $results;

  }

  protected function replaceAdHocFrom($params, $key, $value) {
    $prefix = $this->name . ':';
    $prefix_len = strlen($prefix);
    foreach ($params as $pname => $pval) {
      if (strpos($pval, $prefix) === 0) {
        $replace = substr($pval, $prefix_len);
        if ($replace == 'key') {
          $params[$pname] = $key;
        }
        elseif ($replace == 'value') {
          $params[$pname] = $value;
        }
      }
    }
    return $params;
  }

  protected function fireInnerCommand($klass, $name, $params) {
    $cmd = new $klass($name);
    $cmd->execute($params, $this->context);

    return $this->context->get($name, NULL);
  }

}
