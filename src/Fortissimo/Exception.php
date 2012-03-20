<?php
/**
 * @file
 */
namespace Fortissimo;

/**
 * General Fortissimo exception.
 *
 * This should be thrown when Fortissimo encounters an exception that should be
 * logged and stored, but should not interrupt the execution of a command.
 */
class Exception extends \Exception {}
