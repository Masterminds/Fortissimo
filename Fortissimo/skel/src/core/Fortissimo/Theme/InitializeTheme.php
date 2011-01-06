<?php
/** @file
 *
 * InitializeTheme is a BaseFortissimoCommand class.
 *
 * Created by Matt Butcher on 2010-12-21.
 * @ingroup Theme
 */

/**
 * Initialize the theme system.
 *
 * This initializes the theme system (Theme::initialize()) and then registers the
 * given classes.
 *
 * You don't need to provide all of your theme classes here; just the basic ones that
 * will be used often. Individual commands may register their own theme classes by
 * calling Theme::register().
 *
 * @author Matt Butcher
 */
class InitializeTheme extends BaseFortissimoCommand {

  public function expects() {
    return $this
      ->description('Initialize the Theme System.')
      ->usesParam('path', 'The path to the current theme. This is used for templates.')
       // ->withFilter('this', 'checkPath')
      ->usesParam('register', 'A list of theme classes to initially register. Classes are expected to be instances of BaseThemePackage.')
      ->usesParam('settings', 'An associative array of settings or global variables that the theme system uses.')
      ->andReturns('Nothing')
    ;
  }
  
  /**
   * Filter callback.
   *
   * @fixme THis needs to have the right return value.
   */
   /*
  public function checkPath($path) {
    if (is_array($path)) {
      foreach ($path as $item) {
        if (!is_dir($item)) {
          $this->context->log(printf("Could not find path %s", $item));
          return FALSE;
        }
      }
      return TRUE;
    }
    else {
      return is_dir($path);
    }
  }
  */

  public function doCommand() {
    $path = $this->param('path', '');
    $settings = $this->param('settings', NULL);
    $register = $this->param('register', NULL);
    
    if (is_null($settings)) {
      $settings = array();
    }
    
    // Since Context is an object, it will be a valid reference
    // to the context for the duration of the request. The only exception
    // would be if one (manually) switched requests without throwing an
    // interrupt.
    Theme::initialize($path, $settings, $this->context);
    
    if (!empty($register)) {
      foreach ($register as $klass) {
        Theme::register($klass);
      }
    }
    return TRUE;
  }
}

