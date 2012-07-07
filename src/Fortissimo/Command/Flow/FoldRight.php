<?php
/**
 * @file
 * FoldRight.
 */
namespace Fortissimo\Command\Flow;
/**
 * Fold a list to the right.
 *
 * This starts at the tail of a list, and folds each tail item into
 * the start until the list is empty.
 *
 * See Fortissimo::Command::Flow::FoldLeft for examples.
 */
class FoldRight extends FoldLeft {
  protected function fold($z, $list, $fn) {
    $rev = array_reverse($list);
    return parent::fold($z, $rev, $fn);
  }
}
