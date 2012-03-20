<?php
/**
 * @file
 *
 * The Interrupt throwable.
 */
namespace Fortissimo;
/**
 * Indicates that a condition has been met that necessitates interrupting the command execution chain.
 *
 * This exception is not necessarily intended to indicate that something went
 * wrong, but only htat a condition has been satisfied that warrants the interrupting
 * of the current chain of execution.
 *
 * Note that commands that throw this exception are responsible for responding
 * to the user agent. Otherwise, no output will be generated.
 *
 * Examples of cases where this might be desirable:
 * - Application should redirect (302, 304, etc.) user to another page.
 * - User needs to be prompted to log in, using HTTP auth, before continuing.
 */
class Interrupt extends \Exception {}
