<?php
/**
 * @file
 * Put some or all of the context into an array, which is then placed 
 * back into the context.
 */
namespace Fortissimo\Command\Context;
/**
 * Put some or all of a context into an associative array.
 *
 * This makes it possible to build an array based on the values in the
 * context. The resulting array will then be put back into the context.
 *
 * Optionally, you may specify which context items should be placed into
 * the array. This will then return an array with as many items as it
 * can find.
 * @since 2.0.0
 */
class IntoArray extends \Fortissimo\Command\Base {
  public function expects() {
    return $this->description('Put some or all of a context into an array.')
      ->usesParam('names', 'The names of context items that should be put into this array if found.')
      ->andReturns('An associative array of context names/values.')
    ;
  }

  public function doCommand() {
    $names = $this->param('names', NULL);

    if (is_array($names)) {
      $buffer = array();
      foreach ($names as $name) {
        if ($this->context->has($name)) {
          $buffer[$name] = $this->context->get($name);
        }
      }
    }
    else {
      $buffer = $this->context->toArray();
    }

    return $buffer;
  }
}
