<?php
/** @file
 *
 * This file contains commands for parsing options from ARGV or another source.
 *
 * This file was backported from Villain.
 *
 * Created by Matt Butcher on 2011-06-18.
 */

/**
 * Parse options (flags) from ARGV or another source.
 *
 * This command is designed to facilitate subcommands that can be run from the command line, 
 * much like Git or Subversion. It is intended to augment Fort, which provides a standard
 * CLI client for Fortissimo.
 *
 * Example:
 * @code
 * $ fort --no-internals myCommand --a foo --b bar
 * @endcode
 *
 * Fort itself will handle its own options, and will consume --no-internals. Fortissimo will see
 * the ARGV string as this:
 *
 * @code
 * <?php
 * array(
 *  [0] => fort
 *  [1] => myCommand
 *  [2] => --a
 *  [3] => foo
 *  [4] => --b
 *  [5] => bar
 * );
 * ?>
 * @endcode
 *
 * This command is designed to take a specifications array that specifies which options are supported, and then
 * parse out the args, inserting the parsed args into the context.
 *
 * Here's how this command could handle the args above:
 * @code
 * <?php
 * Config::request('@test')
 *   ->doesCommand('arguments')
 *   ->whichInvokes('\Villain\CLI\ParseOptions')
 *   ->withParam('optionSpec')
 *   ->whoseValueIs(array(
 *     '--a' => array(
 *       'help' => 'This is a test option',
 *       'value' => TRUE,
 *     ),
 *     '--b' => array(
 *       'help' => 'This is a test option',
 *       'value' => TRUE,
 *     ),
 *     // Adding this gives us automatic support for the help system.
 *     '--help' => array(
 *       'help' => 'Print help text for this command.',
 *       'value' => FALSE,
 *     ),
 *   ))
 *   ->withParam('offset')->whoseValueIs(1)
 * ?>
 * @endcode
 *
 * In the example above, assuming that both a and b are legitimate flags, this would insert following entries into the 
 * context: 
 * 
 * @code
 * <?php
 * array(
 *   'a' => 'foo',
 *   'b' => 'bar',
 *   'arguments-command' => 'myCommand',
 * );
 * ?>
 * @endcode
 *
 * Note that the command-line's command name (myCommand) is extracted and pushed into the context with the key being
 * a concatenation of 'arguments' from doesCommand('arguments') and the word '-command'. If additional data is found after
 * the options, it is passed back using the command name and '-extra' (e.g. 'myCommand-arguments')
 *
 * It should be possible to recurse, provided that there is a delimiter between flags. `--` will stop this parser from
 * continuing. Unknown flags will generate a \Villain\Exception.
 *
 * <b>The OptionSpec Format</b>
 *
 * An option spec is an associative array that looks like this:
 *
 * @code
 * array(
 *   '--option' => array(
 *      'help' => 'This is the help text for --option.',
 *      'value' => FALSE, // This option does NOT take a value.
 *   ),
 *   '--file' => array(
 *      'help' => 'The file this should process. Requires a file. Example: --file foo.txt',
 *      'value' => TRUE, // This option takes a filename, so value is TRUE.
 *   ),
 * );
 * @endcode
 *
 * @author Matt Butcher
 */
class ParseOptions extends BaseFortissimoCommand {

  public function expects() {
    return $this
      ->description('Parse an option string, typically from ARGV.')
      
      ->usesParam('options', 'The array of values to parse. If none is supplied, ARGV is used.')
      
      ->usesParam('optionSpec', 'An option spec array. See the code documentation for the correct format.')
      ->whichIsRequired()
      
      ->usesParam('offset', 'By default, this assumes the first argument is the command. If this is not the case (e.g. if argv is "fort myCommand --foo") set the offset accordingly (1, in this case)')
      ->whichHasDefault(0)
      
      ->usesParam('help', 'Additional help text that will be printed when --help is specified. Normally, minimal help text is generated automatically when --help is in the optionSpec and in the options.')
      ->withFilter('string')
      ->whichHasDefault('')
      
      ->andReturns('The remaining (unprocessed) values. Any parsed options are placed directly into the context.')
    ;
  }

  public function doCommand() {
    $optionSpec = $this->param('optionSpec');
    $help = $this->param('help');
    $argArray = $this->param('options', NULL);
    $offset = $this->param('offset', 0);
    
    // If no arg array is specified, use ARGV.
    if (!isset($argArray)) {
      global $argv;
      $argArray = $argv;
    }

    // Shift off leading values.
    if ($offset > 0) {
      $argArray = array_slice($argArray, $offset);
    }
    
    if (!empty($argArray)) {
      $options = $this->extractOptions($optionSpec, $argArray);
    }
    
    print_r($options);
    
    // What do we do with leading args?
    //print_r($this->leadingArgs);
    
    if (isset($options['help'])) {
      $this->generateHelp($optionSpec, $options);
    }
    
    $this->context->addAll($options);
    
    return TRUE;
  }
  
  /**
   * Extract options.
   *
   * This takes a specification and an array, and attempts to 
   * extract all of the options from the array, according to the specification.
   *
   * @param array $optionSpec
   *  The specifications array.
   * @param array $args
   *  The arguments to parse.
   * @return
   *  The arguments array without any options in it. Options are placed directly
   *  into the context as name/value pairs. Boolean flag options will have the 
   *  value TRUE.
   */
  public function extractOptions(array $optionSpec, array $args) {
    $keep_going = TRUE;
    $buffer = array();    
    $command = array_shift($args);
    
    // Loop through all of the flags.
    while ($keep_going && $flag = array_shift($args)) {
      if (isset($optionSpec[$flag])) {
        $key = substr($flag, 2);
        if($optionSpec[$flag]['value']) {
          $value = array_shift($args);
          if (!isset($value) || strpos($value, '--') === 0) {
            throw new \Villain\Exception($flag . ' requires a valid value.');
          }
          $buffer[$key] = $value;
        }
        else {
          $buffer[$key] = TRUE;
        }
      }
      elseif($flag == '--') {
        $keep_going = FALSE;
      }
      elseif(strpos($flag, '--') === 0) {
        throw new \Villain\Exception(sprintf("Unrecognized option %s.", $flag));
      }
      else {
        array_unshift($args, $flag);
        $keep_going = FALSE;
      }
    }
    $buffer[$this->name . '-command'] = $command;
    $buffer[$this->name . '-extra'] = $args;
    
    return $buffer;
  }
  
  /**
   * Generate help text.
   *
   * @param array $optionSpec
   *  The option spec array.
   * @param array $options
   *  The array of options, as parsed by extractOptions().
   * @param string $extraHelp
   *  Any help text based as 'help' to this command.
   * @throws \FortissimoInterrupt
   *  Always thrown to stop execution.
   */
  public function generateHelp($optionSpec, $options, $extraHelp = '') {
    $buffer = array();
    $format = "\t%s:  %s" . PHP_EOL;
    
    printf('%s supports the following' . PHP_EOL, $options[$this->name . '-command']);
    foreach ($optionSpec as $flag => $spec) {
      $help = isset($spec['help']) ? $spec['help'] : '(undocumented)';
      printf($format, $flag, $help);
    }
    if (!empty($extraHelp)) {
      print $extraHelp . PHP_EOL;
    }
    print PHP_EOL;
    throw new \FortissimoInterrupt();
  }
}

