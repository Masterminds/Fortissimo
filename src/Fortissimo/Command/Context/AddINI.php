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
      ->usesParam('optional', 'Whether the INI file is optional (TRUE) or required (FALSE)')
          ->whichHasDefault(FALSE)
          ->withFilter('boolean')
      ->andReturns('Nothing. All ini directives are placed into the context.')
    ;
  }

  public function doCommand() {
    $file = $this->param('file');
    $optional = $this->param('optional', FALSE);
    if ($optional && !is_readable($file)) {
      return;
    }
    $ini = parse_ini_file($file);
    $this->context->addAll($ini);
  }
}

