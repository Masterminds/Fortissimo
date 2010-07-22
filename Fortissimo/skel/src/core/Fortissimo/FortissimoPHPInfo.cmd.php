<?php
/**
 * Provides PHP info.
 */

class FortissimoPHPInfo extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
      ->description('Provides debugging information about PHP.')
      ->usesParam('category', 'One of: general, credits, configuration, modules, environment, variables, license, or all.')
      ->withFilter('string')
      ->whichHasDefault('all')
      ->andReturns('Nothing. Prints data straight to output.');
  }
  
  public function doCommand() {
    $categoryName = $this->param('category');
    $category = $this->getCategoryCode($categoryName);
    
    phpinfo($category);
  }
  
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