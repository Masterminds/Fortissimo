<?php
/**
 * @file
 * Provides the functional "head" operation.
 */
namespace Fortissimo\Command\Util;
/**
 * Retrieve the HEAD item of a list.
 *
 * In functional programming, the head is the first item in a list. This 
 * puts the first item in a list into the context.
 *
 */
class Head extends \Fortissimo\Command\Base {
  public function expects() {
    return $this
      ->description('Put the first (head) value in the list and move the pointer to the next. This does not modify the list.')
      ->usesParam('list', 'An iterable or an array.')
      ->andReturns('The first item in the iterable.');
  }
  public function doCommand() {
    $list = $this->param('list', array());
    // XXX: Should this use a foreach? Not sure if a plain Iterable can 
    // do current().
    $ret = current($list);
    next($list);
    return $ret;
  }
}
