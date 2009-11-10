#!/usr/bin/env php
<?php
/**
 * fff.php is the front end to the Fortissimo framework.
 */
 
/* This is from phing.php */
if (getenv('PHP_CLASSPATH')) {
  if (!defined('PHP_CLASSPATH')) { define('PHP_CLASSPATH',  getenv('PHP_CLASSPATH') . PATH_SEPARATOR . get_include_path()); }
  ini_set('include_path', PHP_CLASSPATH);
} else {
  if (!defined('PHP_CLASSPATH')) { define('PHP_CLASSPATH',  get_include_path()); }
}

require 'FortissimoFramework.php'; 
require 'phing/Phing.php';

$buildfile = Fortissimo::getBuildXML();
$origindir = Fortissimo::getLibraryPath();

$args = isset($argv) ? $argv : $_SERVER['argv'];

if (count($argv) < 2 || $argv[1] == '-h' || $argv[1] == '--help') {
  echo "${argv[0]} projectName
  
Create a new Fortissimo project. This command will build out all of
the necessary directories and files for begining a new project.";
  exit(1);
}

array_shift($args);
$build_target = 'newProject';

$phing_args = array(
  '-Dproject=' . array_pop($args),
  '-Dorigindir=' . $origindir,
  '-f',
  $buildfile,
  $build_target,
);

/*
 * Bootstrap and run Phing.
 * See phing.php for documentation on what is happening here.
 */
try {
 
  Phing::startup();
  Phing::setProperty('phing.home', getenv('PHING_HOME'));
  Phing::fire($phing_args);
  Phing::shutdown();
 
} 
catch (ConfigurationException $x) {
  Phing::printMessage($x);
  exit(-1);
}
catch (Exception $x) {
  exit(1);
}
print "Project is now created." . PHP_EOL;
?>