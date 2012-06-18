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
      ->usesParam('section', 'The INI section to use. If this is set, only values from this section are added to the context.')
      ->usesParam('process_sections', 'Whether or not to process sections. If "section" is set, this is automatically enabled.')
        ->whichHasDefault(FALSE)
          ->withFilter('boolean')
      ->andReturns('Nothing. All ini directives are placed into the context.')
    ;
  }

  public function doCommand() {
    $file = $this->param('file');
    $section = $this->param('section');
    $process_section = $this->param('process_sections') || strlen($section) > 0;
    $optional = $this->param('optional', FALSE);

    if ($optional && !is_readable($file)) {
      return;
    }
    $ini = parse_ini_file($file, $process_section);

    // If we only return a section, use this.
    if (!empty($section)) {
      if (isset($ini[$section])) {
        $this->context->addAll($ini[$section]);
      }
      return;
    }


    // Return the entire INI if we get here.
    $this->context->addAll($ini);
  }
}

