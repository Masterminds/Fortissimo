<?php
/**
 * @file
 * Fortissimo::Explainable class.
 */
namespace Fortissimo;

/**
 * Any class that implements Explainable must return a string that describes,
 * in human readable language, what it does.
 *
 * @see Fortissimo::Command::Base
 */
interface Explainable {
  /**
   * Provides a string explaining what this class does.
   *
   * @return string
   *  A string explaining the role of the class.
   */
  public function explain();
}