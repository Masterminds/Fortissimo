<?php
/**
 * The top-level Fortissimo server.
 *
 * This handles the HTTP/HTTPS service for Fortissimo.
 *
 * It expects a GET string with the query 'ff=someRequestName', which 
 * will be translated to a request section in the commands.php file. That 
 * request will then be executed, one command after another, until the end of
 * the command chain is reached.
 * 
 * See the command.php file, Fortissimo, and BaseFortissimoCommand for more info.
 * 
 * @ingroup Fortissimo Core
 * @author M Butcher <matt@aleph-null.tv>
 * @license http://opensource.org/licenses/mit.php An MIT-style License (See LICENSE.txt)
 * @see Fortissimo
 * @see Fortissimo.php
 * @copyright Copyright (c) 2010, Matt Butcher.
 * @version %UNSTABLE-
 */
if(version_compare(phpversion(), '5.2', '>') === FALSE) {
  print 'PHP 5.2 or greater is required.';
  exit;
}

// Idiotic things you just have to do...
if (get_magic_quotes_gpc()) {
  print '"Magic quotes", a deprecated PHP feature, is enabled. Please turn it off.';
  exit;
}
if (get_magic_quotes_runtime()) {
  set_magic_quotes_runtime(FALSE);
}

/**
 * Import the main library.
 */
require 'Fortissimo.php';

$cmd = filter_input(INPUT_GET, 'ff', FILTER_SANITIZE_STRING);
if (empty($cmd)) {
  $cmd = 'default';
}

$base = dirname(__FILE__);
$ff = new Fortissimo($base . '/config/commands.php');
$ff->handleRequest($cmd);