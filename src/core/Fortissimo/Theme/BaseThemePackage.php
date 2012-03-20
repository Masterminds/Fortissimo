<?php
/** @file
 * Classes supporting the basic theme package structure.
 *
 * The main class for this package is the BaseThemePackage.
 */

/**
 * Provides infrastructure for defining theme packages.
 *
 * A theme package is an abritrary bundle of theme tools. The package tells the
 * theme system what tools it contains, and the theme system can then proxy 
 * requests to the correct theme package.
 *
 * Theme packages can provide both theme functions and references to template files.
 * Typically, a theme package will implement one or both of the following methods:
 * 
 * - BaseThemePackage::templates()
 * - BaseThemePackage::functions()
 *
 * Here's an example:
 *
 * @code
 * <?php
 * class MyTheme extends BaseThemePackage {
 *   public function templates() {
 *     // Define one template named 'main' that is in the file 'main.php'
 *     return array('main' => 'main.php');
 *   }
 *
 *   public function functions() {
 *     // Register this object's helloWorld() function under the name 'hello'
 *     return array('hello' => array($this, 'helloWorld'));
 *   }
 *
 *   // The helloWorld function
 *   public function helloWorld(&$variables) {
 *     return "Hello World!";
 *   }
 * }
 * ?>
 * @endcode
 *
 * When the above is registered, we could call either of these theme targets:
 *
 * @code
 * <?php
 * $variables = array(); // Some theme variables.
 * Theme::render('hello', $variables);
 * Theme::render('main', $variables);
 * ?>
 * @endcode
 */
class BaseThemePackage {
  
  /**
   * Provides a list of all of the template targets that this package supports.
   *
   * A package can declare theme functions and theme templates. This returns information
   * about which theme templates the class declares. The expected format of the returned
   * data is path. If the path is absolute (starts with a slash), an absolute path will be 
   * used. If the path is relative (no leading slash), the theme path will be prepended.
   *
   * See the example in BaseThemePackage.
   *
   * @return array
   *  An associative array of the form `array( 'target' => '/path/to/template.php' )`.
   */
  public function templates() {
    return array();
  }
  
  /**
   * Provides a list of all of the function targets that this package supports.
   *
   * A package can declare theme functions and theme templates. This returns information
   * about which theme functions the class provides. The expected format of the returned
   * data is an associative array of targets (names) to callbacks, where a "callback" is
   * any valid PHP callback, including closures and lambdas in PHP 5.3.
   *
   * See the example in BaseThemePackage.
   *
   * @return array
   *  An associative array of the form `array( 'target' => $callback )`.
   *
   * @see http://us2.php.net/manual/en/language.pseudo-types.php#language.types.callback Callbacks
   */
  public function functions() {
    return array();
  }
  
  /**
   * Register preprocessors.
   *
   * Advanced data processing in the theme layer.
   *
   * Preprocessors are run before the main renderer. This provides an opportunity to 
   * "hook into" the data before the page is rendered. As long as $variables is passed
   * by reference, you can modify the data in place.
   *
   * More than one preprocessor can be run against $variables. They are run in the order
   * in which they are registered.
   *
   * Example:
   * @code
   * <?php
   * class Foo extends BaseThemePackage {
   *   public function preprocessors() {
   *     return array(
   *       'menu' => array($this, 'menuPreprocessor'),
   *     );
   *   }
   *   
   *   public function menuPreprocessor(&$variables) {
   *     // $variables are the variables
   *   }
   * }
   * ?>
   * @endcode
   * 
   * @return array
   *  An associative array of $target names to a preprocessor callback.
   */
  public function preprocessors() {
    return array();
  }
  
  /**
   * Register postprocessors.
   *
   * Advanced processing in the theme layer.
   *
   * See preprocessors() for an example.
   *
   * Postprocessors are executed after the target's main render function is called.
   * While only one renderer (template or function) can be called per item, multiple
   * pre- and postprocessors can be called. They are executed in sequence.
   *
   * Example:
   * @code
   * <?php
   * class Foo extends BaseThemePackage {
   *   public function postprocessors() {
   *     return array(
   *       'menu' => array($this, 'menuPostprocessor'),
   *     );
   *   }
   *   
   *   public function menuPostprocessor(&$content, &$variables) {
   *     // $content is the rendered content
   *     // $variables are the ariables
   *   }
   * }
   * ?>
   * @endcode
   * 
   * @return array
   *  An associative array of $target names to a postprocessor callback.
   */
  public function postprocessors() {
    return array();
  }
}