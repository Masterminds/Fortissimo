<?php
/**
 * @file
 * Add INI file data to context.
 */
namespace Fortissimo\Command\Context;
/**
 * Add the contents of an INI file into the context.
 * @since 2.0.0
 */
class AddINI extends \Fortissimo\Command\Base {
  public function expects() {
    return $this->description('Load the conents of an INI file into the context.')
      ->usesParam('file', 'The path to the INI file.')->whichIsRequired()
      ->andReturns('Nothing. All ini directives are placed into the context.')
    ;
  }

  public function doCommand() {
    $file = $this->param('file');
    $ini = parse_ini_file($file);
    $this->context->addAll($ini);
  }
}

