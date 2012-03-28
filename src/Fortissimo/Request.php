<?php
/**
 * @file
 * Fortissimo::Request class.
 */
namespace Fortissimo;

/**
 * A Fortissimo request.
 *
 * This class represents a single request.
 */
class Request implements \IteratorAggregate {

  protected $commandQueue = NULL;
  protected $isCaching = FALSE;
  protected $isExplaining = FALSE;
  protected $requestName;

  public function __construct($requestName, $commands) {
    $this->requestName = $requestName;
    $this->commandQueue = $commands;
  }

  public function getName() {
    return $this->requestName;
  }

  /**
   * Get the array of commands.
   *
   * @return array
   *  An array of commands.
   */
  public function getCommands() {
    return $this->commandQueue;
  }

  /**
   * Set the flag indicating whether or not this is caching.
   */
  public function setCaching($boolean) {
    $this->isCaching = $boolean;
  }

  /**
   * Set explain mode.
   *
   * By default a command is NOT in explain mode.
   * @param boolean $boolean
   *  Set to TRUE to turn on explain mode.
   */
  public function setExplain($boolean) {
    $this->isExplaining = $boolean;
  }

  /**
   * Determine whether this request is in 'explain' mode.
   *
   * When a request is explaining, Fortissimo will output detailed
   * information about each command, such as what parameters it expects
   * and what its purpose is.
   *
   * @return boolean
   *  TRUE if this request is in explain mode, false otherwise.
   */
  public function isExplaining() {
    return $this->isExplaining;
  }

  /**
   * Determine whether this request can be served from cache.
   *
   * Request output can sometimes be cached. This flag indicates
   * whether the given request can be served from a cache instead
   * of requiring the entire request to be executed.
   *
   * @return boolean
   *  Returns TRUE if this can be served from cache, or
   *  FALSE if this should not be served from cache.
   * @see Fortissimo::Cache::Request
   */
  public function isCaching() {
    return $this->isCaching;
  }

  /**
   * Get an iterator of this object.
   *
   * @return Iterator
   */
  public function getIterator() {
    return new ArrayIterator($this->commandQueue);
  }
}
