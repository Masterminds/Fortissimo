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
  printf('%s expects at least one parameter. Try --help.', $argv[0]);
  exit(1);
}
elseif ($argv[1] == '--help') {
  printf('This is a command-line Fortissimo command runner.') . PHP_EOL;
  printf('Syntax: %s COMMAND [ARGUMENTS]', $argv[0]) . PHP_EOL;
  exit(0);
}

// Try to find the commands file:

$cwd = getcwd();
$bases = array(
  $cwd,
  $cwd . '/src',
  $cwd . '../src',
);

$config = NULL;
foreach ($bases as $base) {
  if (is_file($base . '/config/commands.php')) {
    $practicalBase = $base;
    $config = $base . '/config/commands.php';
    break;
  }
}

if (empty($config)) {
  print 'No configuration file found. Quitting.' . PHP_EOL;
  exit(1);
}

/*
 * Build a new Fortissimo server and execute the command. 
 */

$ff = new Fortissimo($config);
$ff->handleRequest($argc[1]);