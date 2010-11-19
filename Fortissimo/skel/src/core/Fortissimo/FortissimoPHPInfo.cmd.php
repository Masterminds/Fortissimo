<?php
/** @file
 * Provides PHP info.
 */

/**
 * A simple command for printing PHP information.
 *
 * This command provides a quick way of outputting {@link phpinfo()} information.
 *
 * @ingroup Fortissimo
 */
class FortissimoPHPInfo extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
      ->description('Provides debugging information about PHP.')
      ->usesParam('category', 'One of: general, credits, configuration, modules, environment, variables, license, or all.')
      ->withFilter('string')
      // Can't use as a validator because it may return a legitimate 0.
      //->withFilter('callback', array($this, 'getCategoryCode'))
      ->whichHasDefault('all')
      ->andReturns('Nothing. Prints data straight to output.');
  }
    
  public function doCommand() {
    $categoryName = $this->param('category');
    $category = $this->getCategoryCode($categoryName);
    
    phpinfo($category);
  }
  
  /**
   * Get the category ID, given a string name.
   *
   * @param string $category
   *  The name of the category. One of general, credits, configuration, modules, 
   *  environment, variables, license, or all.
   * @return int
   *  The associated code, defaulting to -1 (INFO_ALL).
   * @see phpinfo()
   */
  protected function getCategoryCode($category) {
    switch($category) {
      case 'general':
        return INFO_GENERAL;
      case 'credits':
        return INFO_CREDITS;
      case 'configuration':
        return INFO_CONFIGURATION;
      case 'modules':
        return INFO_MODULES;
      case 'environment':
        return INFO_ENVIRONMENT;
      case 'variables':
        return INFO_VARIABLES;
      case 'license':
        return INFO_LICENSE;
      case 'all':
      default:
        return INFO_ALL;
    }
  }
}