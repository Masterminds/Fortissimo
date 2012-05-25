<?php
/**
 * @file
 * Help text generation command.
 */
namespace Fortissimo\Command\CLI;

/**
 * Show help text.
 */
class ShowHelp extends \Fortissimo\Command\Base {
  public function expects() {
    return $this->description('Generate help text listing all of the commands.')
      ->andReturns('Nothing.')
      ;
  }

  public function doCommand() {
    global $argv;
    $ff = $this->context->fortissimo();
    $config = $ff->getRequestPaths();
    //var_dump($config['requests']);


    $buffer = array();
    $longest = 4;
    foreach ($config['requests'] as $name => $params) {
      $buffer[] = array($name, $config['help']['requests'][$name]);
      $width = strlen($name);
      if ($width > $longest) {
        $longest = $width;
      }

    }

    printf("\n  %s COMMAND [--help | --OPTIONS [..]] [ARGS]\n\n  Available commands:\n\n", $argv[0]);
    $filter = "\t%-" . ($longest + 2) .'s%s' . PHP_EOL;
    foreach ($buffer as $line) {
      printf($filter, $line[0], $line[1]);

    }
    print PHP_EOL;
  }
}
