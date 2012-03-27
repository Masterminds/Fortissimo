<?php

print __FILE__ . ' is untested' . PHP_EOL;
/**
 * The version number for this release of QueryPath.
 *
 * Stable versions are numbered in standard x.y.z format, where x is the major
 * version, y is the minor version, and z is the bug-fix/patch level.
 *
 * Developer releases are stamped dev-DATE, where DATE is the ISO-formatted date
 * of the build. You should not use these versions on production systems, nor
 * as a platform for application development, since they are considered unfrozen,
 * and hence subject to change.
 *
 * The special flag @ UNSTABLE @ means that a non-built version of the application
 * is being used. This should occur only for developers who are actively developing
 * Fortissimo. No production release should ever have this tag.
 */
define('FORTISSIMO_VERSION', '@UNSTABLE@');

// Set the include path to include Fortissimo directories.
$basePath = dirname(__FILE__);
$paths[] = get_include_path();
$paths[] = $basePath . '/includes';
$paths[] = $basePath . '/core';
$paths[] = $basePath . '/core/Fortissimo';
$paths[] = $basePath . '/phar';
$path = implode(PATH_SEPARATOR, $paths);
set_include_path($path);

// Prepare the autoloader.
spl_autoload_extensions('.php,.cmd.php,.inc');


// For performance, use the default loader.
// XXX: This does not work well because the default autoloader
// downcases all classnames before checking the FS. Thus, FooBar
// becomes foobar.
//spl_autoload_register();

// Keep this in global scope to allow modifications.
global $loader;
$loader = new FortissimoAutoloader();
spl_autoload_register(array($loader, 'load'));

/**
 * A broad autoloader that should load data from expected places.
 *
 * This autoloader is designed to load classes within the includes, core, and phar
 * directories inside of Fortissimo. Its include path can be augmented using the
 * {@link addIncludePaths()} member function. Internally, {@link Fortissimo} does this
 * as it is parsing the commands.xml file (See {@link Fortissimo::addIncludePaths}).
 *
 * This loader does the following:
 *  - Uses the class name for the base file name.
 *  - Checks in includes/, core/, and phar/ for the named file
 *  - Tests using three different extensions: .php. .cmd.php, and .inc
 *
 * So to load class Foo_Bar, it will check the following (in order):
 *  - includes/Foo_Bar.php
 *  - includes/Foo_Bar.cmd.php
 *  - includes/Foo_Bar.inc
 *  - core/Foo_Bar.php
 *  - core/Foo_Bar.cmd.php
 *  - core/Foo_Bar.inc
 *  - core/Fortissimo/Foo_Bar.php
 *  - core/Fortissimo/Foo_Bar.cmd.php
 *  - core/Fortissimo/Foo_Bar.inc
 *  - phar/Foo_Bar.php
 *  - phar/Foo_Bar.cmd.php
 *  - phar/Foo_Bar.inc
 *
 * Then it will search any other included paths using the same
 * algorithm as exhibited above. (We search includes/ first because
 * that is where implementors are supposed to put their classes! That means
 * that with a little trickery, you can override Fortissimo base commands simply
 * by putting your own copy in includes/)
 *
 * <b>Note that phar is experimental, and may be removed in future releases.</b>
 */
class FortissimoAutoloader {

  protected $extensions = array('.php', '.cmd.php', '.inc');
  protected $include_paths = array();

  public function __construct() {
    //$full_path = get_include_path();
    //$include_paths = explode(PATH_SEPARATOR, $full_path);
    $basePath = dirname(__FILE__);
    $this->include_paths[] = $basePath . '/includes';
    $this->include_paths[] = $basePath . '/core';
    $this->include_paths[] = $basePath . '/core/Fortissimo';
    $this->include_paths[] = $basePath . '/core/Fortissimo/Theme';
    $this->include_paths[] = $basePath . '/phar';
  }

  /**
   * Add an array of paths to the include path used by the autoloader.
   *
   * @param array $paths
   *  Indexed array of paths.
   */
  public function addIncludePaths($paths) {
    $this->include_paths = array_merge($this->include_paths, $paths);
  }

  /**
   * Attempt to load the file containing the given class.
   *
   * @param string $class
   *  The name of the class to load.
   * @see spl_autoload_register()
   */
  public function load($class) {

    // Micro-optimization for Twig, which supplies
    // its own classloader.
    if (strpos($class, 'Twig_') === 0) return;

    // Namespace translation:
    $class = str_replace('\\', '/', $class);

    foreach ($this->include_paths as $dir) {
      $path = $dir . DIRECTORY_SEPARATOR . $class;
      foreach ($this->extensions as $ext) {
        if (file_exists($path . $ext)) {
          //print 'Found ' . $path . $ext . '<br/>';
          require $path . $ext;
          return;
        }
      }
    }
  }

}
