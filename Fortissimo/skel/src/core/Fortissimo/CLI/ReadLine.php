<?php
/** @file
 *
 * ReadLine is a BaseFortissimoCommand class.
 *
 * Backported to Fortissimo from Villain.
 *
 * Created by Matt Butcher on 2011-07-01.
 */

/**
 * Prompts the user for input and waits for a response.
 *
 * The responses are then stored in the context.
 *
 * The prompts format is similar to the options format in \Villain\CLI\ParseOptions.
 *
 * @code
 * array(
 *   'name' => array(
 *      'help' => 'Enter your name',
 *      'default' => 'anonymous',
 *   ),
 *   'favorite_color' => array(
 *      'help' => 'Enter your favorite color, or hit return for no color',
 *      'default' => NULL,
 *   ),
 * );
 * @endcode
 * @author Matt Butcher
 */
class ReadLine extends BaseFortissimoCommand {
  
  protected $cursor;

  public function expects() {
    return $this
      ->description('Provides a command-line prompt, and stores answers in the context.')

      ->usesParam('prompts', 'An array of prompts that are then read in from the command line and stored in the context.')

      ->usesParam('promptFormat', 'The format of the command prompt. %s will be replaced with the name value of the prompts')
      ->whichHasDefault('%s> ')

      ->andReturns('Nothing. Values are inserted directly into the context.')
    ;
  }

  public function doCommand() {
    $this->cursor = $this->param('promptFormat');
    
    $prompts = $this->param('prompts', array());
    
    $this->doPromptSequence($prompts);
  }
  
  public function doPromptSequence($prompts) {
    foreach ($prompts as $prompt => $desc) {
      $help = isset($desc['help']) ? $desc['help'] : '';
      $default = isset($desc['default']) ? $desc['default'] : NULL; // Avoid E_STRICT violation
      
      $value = $this->doPrompt($prompt, $help, $default);
      
      $this->context->add($prompt, $value);
    }
  }
  
  public function doPrompt($name, $help, $default) {
    print $help . PHP_EOL;
    if (function_exists('readline')) {
      $value = readline(sprintf($this->cursor, $name));
    }
    else {
      printf($this->cursor, $name);
      $value = rtrim(fgets(STDIN));
    }
    return empty($value) ? $default : $value;
  }
}

