#!/usr/bin/env php
<?php
/**
 * This is a demonstration Command Line client for Fortissimo.
 */

/**
 * Point this to the correct Fortissimo.php.
 */
require '../src/Fortissimo.php';

if ($argc <= 1) {
  printf('%s expects at least one parameter. Try --help.'. PHP_EOL, $argv[0]);
  exit(1);
}
elseif ($argv[1] == '--help') {
  printf('This is a command-line Fortissimo command runner.'. PHP_EOL);
  printf('Syntax: %s COMMAND [ARGUMENTS]'. PHP_EOL, $argv[0]);
  exit(0);
}

// Try to find the commands file:

$cwd = getcwd();
$bases = array(
  $cwd,
  $cwd . '/src',
  $cwd . '/../src',
);

$basedir = NULL;
foreach ($bases as $base) {
  if (is_file($base . '/config/commands.php')) {
    //$practicalBase = $base;
    $basedir = $base;// . '/config/commands.php';
    break;
  }
}

if (empty($basedir)) {
  print 'No configuration file found. Quitting.' . PHP_EOL;
  exit(1);
}
chdir($basedir);

/*
 * Build a new Fortissimo server and execute the command. 
 */

$ff = new Fortissimo('config/commands.php');
$ff->handleRequest($argv[1]);