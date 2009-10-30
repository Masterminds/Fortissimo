<?php
/**
 * The top-level Fortissimo server.
 *
 * This handles the HTTP/HTTPS service for Fortissimo.
 */
if(version_compare(phpversion(), '5.2', '>') === TRUE) {
  print 'PHP 5.2 or greater is required.';
  exit;
}

// Idiotic things you just have to do...
if (get_magic_quotes_gpc()) {
  print 'Magic quotes, a deprecated PHP feature, are enabled. Please turn them off.';
  exit;
}
if (get_magic_quotes_runtime()) {
  set_magic_quotes_runtime(FALSE);
}

/**
 * Import the main library.
 */
require 'Fortissimo.php';

$base = dirname(__FILE__);
$ff = new Fortissimo($base . '/config/commands.xml');
$ff->handleRequest($_GET['c']);