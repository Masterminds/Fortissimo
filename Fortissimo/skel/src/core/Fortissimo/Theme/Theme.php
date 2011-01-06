<?php
/** @file
 * The theme registry and theme function wrappers.
 *
 * @ingroup Theme
 */

/**
 * @defgroup Theme Theme
 *
 * The theme layer provides templates and theme functions that commands can access to 
 * transform dtata into chunks of marked-up content.
 *
 * How Theming Works:
 *
 * - To theme content, you execute Theme::render($target, $variables), where $target is the name
 *   of the theme you want to execute and $variables is an associative array of data. 
 *   This is conceptually similar to Drupal 7's theme system.
 * - To write themes:
 *   1. Write a simple class that extends BaseThemePackage
 *   2. For theme functions, add a BaseThemePackage::functions() method
 *   3. To add a template, return info in BaseThemePackage::templates().
 * - Templates are just PHP files that render content (as in Drupal)   
 * - Theme functions are functions that theme content (as in Drupal), but they may be any of 
 *   the following (unlike Drupal):
 *   - A function name
 *   - A static method (`Foo::bar()`)
 *   - A closure or lambda function (PHP 5.3 only)
 *   - An array of the form `array($obj, 'function_name')`
 *   - Any callable (See the PHP docs)
 * - Unlike Drupal, Ottawa does not use the $target string to make a callback. $target is simply
 *    a mnemonic (a keyword) identifying a particular theming task. E.g. the $target `foo` can 
 *    reference a template `/path/too/some/file.php` or a function `array($this, 'formatter')`, 
 *    neither of which uses the string `foo` at all. Thus the same template can be re-used for
 *    multiple targets.
 * - Theme classes have to be explicitly registered. (This prevents the giant registry building
 *   operations that occur when the system has to scan for themes.) There are two ways of registering
 *   a theme:
 *   1. By adding the class name to the InitializeTheme command's `register` parameter.
 *   2. By explicitly calling Theme::register().
 *
 * Example of theming:
 *
 * Imagine that we have a theme (a function or template -- it doesn't matter) that takes a list of
 * items and turns them into a list. 
 * @code
 * <?php
 * $variables = array(
 *   'type' => 'ol',
 *   'items' => array(1, 2, 3)
 * );
 * 
 * $out = Theme::render('list', $variables);
 * ?>
 * @endcode
 *
 * The above calls the (fictional) `list` theme, which will then return something like this:
 * @code
 * <ol>
 *  <li>1</li>
 *  <li>2</li>
 *  <li>3</li>
 * </ol>
 * @endcode
 *
 * <strong>Creating your own theme</strong>
 * 
 * So how might we create a list theme? Here's an example that does this with a template, which we 
 * will call `list.php`. For the sake of simplicty, we'll ignore the `type` variable.
 *
 * @code
 *  <ol>
 *    <?php foreach ($items as $item) { ?>
 *     <li><?php print $item; ?></li>
 *    <?php } ?>
 *  </ol>
 * @endcode
 *
 * To register this template, we could then need to write this bit of code:
 *
 * @code
 * <?php
 * class ListTheme extends BaseThemePackage {
 *   public function templates() {
 *    return array('list' => 'list.php');
 *   }
 * }
 * ?>
 * @endcode
 *
 * Finally, any request that wants to use this theme package (the one that contains the `list`
 * definition) would have to register the `ListTheme` class:
 *
 * @code
 * Theme::register('ListTheme');
 * @endcode
 *
 * From that point on, `Theme::render('list', $variables)` can be called.
 *
 * <strong>Best practices</strong>
 * 
 * - In general, you will want to create theme packages that contain many similar theme functions.
 * - By registering theme B AFTER theme A, you can override themes in theme A.
 * - In general, it is better to register theme classes in InitializeTheme for oft-used themes, 
 *  while themes specific to a command should be registered with Theme::register().
 * - Most of the time, you should write templates for Ottawa the same way you write templates for
 *  Drupal.
 */

/**
 * The main theme class.
 * @ingroup Theme
 */
class Theme {
  
  private static $inst;
  
  /**
   * Initialize the theme.
   *
   * Each time this is done, it completely re-initializes the theme, which means 
   * destroying the existing registry. Typically, it should only be done once at the
   * beginning of a request. (Note that if you forward requests, the request forward
   * to may itself initialize the theme.)
   *
   * @param string $path
   *  The path to the current theme.
   * @param array $settings
   *  An associative array of (free-form) settings. This can be accessed by theme
   *  functions.
   * @param FortissimoExecutionContext $context
   *  The context.
   * @param string $impl
   *  ADVANCED: If you want to use an extension of ThemeImpl, supply the class
   *  name here.
   */
  public static function initialize($path, $settings, $context, $impl = 'ThemeImpl') {
    self::$inst = new $impl($path, $settings, $context);
  }
  
  /**
   * Register a theme class.
   *
   * Classes that declare templates or theme functions should be registered using this. Any 
   * class passed in is expected to extend BaseThemePackage.
   *
   * @param string $klass
   *  The class to register.
   */
  public static function register($klass) {
    return self::$inst->register($klass);
  }
  
  /**
   * Test whether the given theme target is defined.
   *
   * These returns TRUE if there is a function or template that answers to the given target
   * name. Otherwise, it returns FALSE.
   *
   * @param string $target
   *  The name of the target to look for.
   * @return boolean
   *  TRUE if the target is registered, FALSE otherwise.
   */
  public static function isRegistered($target) {
    return self::$inst->has($target);
  }
  
  /**
   * Render content.
   *
   * In a nutshell, this renders theme content.
   *
   * This will look up the $target, which is expected to correspond to a template or function,
   * and then attempt to execute that target, passing in the $variables. The results are then
   * returned.
   */
  public static function render($target, &$variables) {
    $result = self::$inst->exec($target, $variables);
    return $result;
  }
  
  /**
   * Render a template.
   *
   * This takes a full path to a template file and renders it, passing it an extracted
   * version of the $variables (e.g. `array('a' => 'b')` becomes `$a`, and `$a === 'b'`).
   *
   * @param string $path
   *  The full path to a template file.
   * @param array $variables
   *  An associative array of variables.
   * @return string
   *  The rendered template.
   */
  public static function template($path, &$variables) {
    extract($variables, EXTR_SKIP);
    ob_start();
    include $path;
    $c = ob_get_clean();
    
    return $c;
  }
}

/**
 * The underlying theme implementation.
 *
 * The Theme class builds an instance of this class, which serves as a registry.
 *
 * @ingroup Theme
 */
class ThemeImpl {
  
  // Instances of template packages
  protected $instances;
  
  // The base template path
  protected $path;
  
  // Reserved for future use.
  protected $settings;
  protected $context;
  
  // Registries
  protected $registry = array();
  protected $preprocessors = array();
  protected $postprocessors = array();
  
  /**
   * Construct a new ThemeImpl.
   *
   * Typically, the Theme class is a factory for this, so there is no need to 
   * directly instantiate it.
   *
   * @param string $path
   *   The path to the current theme.
   * @param string $settings
   *   An associative array of settings.
   * @param FortissimoExecutionContext $context
   *   The context for this request. Context may be used for a wide variety of reasons,
   *   as determined by the theme layer.
   */
  public function __construct($path, array $settings, FortissimoExecutionContext $context) {
    $this->path = $path;
    $this->settings = $settings;
    $this->context = $context;
    
    $this->instances = new SplObjectStorage();
  }
  
  /**
   * Register the given class.
   *
   * The given class is instantiated and then queried for information about
   * what targets it provides. Target info is then cached.
   *
   * @param mixed $klass
   *  If this is a string, it is assumed to be a class name, and a new class is constructed.
   *  Otherwise, $klass is treated as an already-initialized instance.
   */
  public function register($klass) {
    $instance = is_string($klass) ? new $klass() : $klass;
    
    $this->instances->attach($instance);
    
    $this->inspectInstance($instance);
  }
  
  /**
   * This re-inspects all passed-in instances and rebuilds the registry.
   *
   * This doesn't change which classes provide theme functions. Rather, it 
   * rescans all of those classes and rebuilds its tables accordingly. In that
   * way, classes can dynamically declare functions and templates -- but at the 
   * cost of rebuild overhead.
   */
  public function rebuild() {
    foreach ($this->instances as $inst) {
      $this->inspectInstance($inst);
    }
  }
  
  /**
   * Check whether this object has a target matching the given name.
   *
   * Note that this does NOT check pre/post-processor functions.
   *
   * @param string $target
   *  The name of the target to look for.
   * @return boolean
   *  TRUE if the target is registered, FALSE otherwise.
   */
  public function has($target) {
    return isset($this->registry['target']);
  }
  
  /**
   * Render a target.
   *
   * Look up the target, find the appropriate renderer (template or function), and render the 
   * content through that renderer.
   *
   * This is incredibly conservative right now. If a renderer is not found, an empty string is
   * returned. To find out whether a renderer exists, use has().
   *
   * @param string $target
   *  The target (name of template or function) to execute.
   * @param array $variables
   *  The variables to pass in.
   */
  public function exec($target, &$variables) {
    $buffer = '';
    
    // Preprocess
    if (!empty($this->preprocessors[$target])) {
      foreach ($this->preprocessors[$target] as $preprocess) {
        call_user_func($preprocess, $variables);
      }
    }
    
    // Render
    if (isset($this->registry[$target])) {
      $hollaback = $this->registry[$target];
      $buffer = call_user_func($hollaback, $variables);
    }
    
    // Postprocess
    if (!empty($this->postprocessors[$target])) {
      foreach ($this->postprocessors[$target] as $postprocess) {
        call_user_func($postprocess, $buffer, $variables);
      }
    }
        
    // Return the rendered content.
    return $buffer;
  }
  
  /**
   * Extract information from a BaseThemePackage, and register it.
   */
  protected function inspectInstance(BaseThemePackage $instance) {
    // Find out what it supports.
    
    // Add theme functions.
    foreach ($instance->functions() as $target => $callback) {
      $this->registry[$target] = $callback;
    }
    
    // Add templates. Note that we do this by wrapping each 
    // template in a simple renderer object.
    foreach ($instance->templates() as $target => $template) {
      
      // Prefix templates when necesary. 
      if (!empty($this->path) && strpos($template, '/') !== 0 ) {
        $template = $this->path . '/' . $template;
      }
      
      // With PHP 5.3, replace this with a closure.
      $renderer = new TemplateRenderer($template);
      $this->registry[$target] = array($renderer, 'render');
    }
    
    // Add preprocessors. Note that pre- and postprocessors can have more
    // than one function per target.
    foreach ($instance->preprocessors() as $target => $callback) {
      $this->preprocessors[$target][] = $callback;
    }
    
    // Add postprocessors.
    foreach ($instance->postprocessors() as $target => $callback) {
      $this->postprocessors[$target][] = $callback;
    }
  }
  
}

/**
 * A simple class for rendering a template.
 *
 * This is intended as a PHP 5.2 stop-gap. It will be replaced by a closure in later
 * versions.
 *
 * @ingroup Theme
 */
class TemplateRenderer {
  protected $tpl;
  
  /**
   * Create a new renderer with a template.
   *
   * @param string $template
   *   The path to the template file.
   */
  public function __construct($template) {
    $this->tpl = $template;
  }
  
  public function render(&$variables) {
    return Theme::template($this->tpl, $variables);
  }
}