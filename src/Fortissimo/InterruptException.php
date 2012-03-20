<?php
/**
 * @file
 */
namespace Fortissimo;
/**
 * Indicates that a fatal error has occured.
 *
 * This is the Fortissimo exception with the strongest implications. It indicates
 * that not only has an error occured, but it is of such a magnitude that it
 * precludes the ability to continue processing. These should be used sparingly,
 * as they prevent the chain of commands from completing.
 *
 * Examples:
 * - A fatal error has occurred, and a 500-level error should be returned to the user.
 * - Access is denied to the user.
 * - A request name cannot be found.
 */
class InterruptException extends Exception {}
