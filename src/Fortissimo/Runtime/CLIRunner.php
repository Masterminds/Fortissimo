<?php
/**
 * @file
 *
 * Generic CLI support.
 */

namespace Fortissimo\Runtime;

class CLIRunner extends Runner {
  protected $input;
  protected $output;
  protected $args;


  /**
   * Create a new CLIRunner.
   *
   * Arguments passed in tell the runner where the arguments, input,
   * and output streams can be found. If none is explicitly specified
   * then we use the system's ARGV, STDOUT, and STDIN as sources.
   *
   * @param array $argv
   *   An indexed array of arguments.
   * @param resource $out
   *   The output stream. This must support fwrite() and family.
   * @param restource $in
   *   The input stream. This must support fread() and family.
   */
  public function __construct($args = NULL, $out = NULL, $in = NULL) {

    // Set defaults;
    if (!isset($args)) {
      global $argv;
      $args = $argv;
    }
    if (!isset($out)) {
      $out = STDOUT;
    }
    if (!isset($in)) {
      $in = STDIN;
    }

    $this->args = $args;
    $this->input = $in;
    $this->output = $out;
  }



  public function initialContext() {
    $cxt = parent::initialContext();

    return $cxt;
  }

  /**
   * Print help text.
   * @param array $options
   *  Options array.
   * @param array $examples
   *  Additional examples to display.
   */
  public function showHelp($options, $examples) {
    global $argv;
    printf('This is a command-line Fortissimo command runner.'. PHP_EOL);
    printf('Syntax: %s [OPTIONS] COMMAND [ARGUMENTS]'. PHP_EOL, $argv[0]);
    print 'Options:' . PHP_EOL;

    $ofmt = "\t%s: %s" . PHP_EOL;
    foreach ($options as $opt => $data) {
      printf($ofmt, $opt, $data['help']);
    }

    print 'Examples:' . PHP_EOL;
    foreach ($examples as $cmd => $exp) {
      print "\t";
      printf ($cmd, $argv[0]);
      print ":  " . $exp . PHP_EOL;
    }
  }

}
