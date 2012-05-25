<?php
/**
 * @file
 * Increment command.
 */
namespace Fortissimo\Command\Util;
/**
 * Increment an integer by N.
 *
 * A simple incrementor. This is useful for functional
 * looping.
 *
 *
 */
class Increment extends \Fortissimo\Command\Base {
  public function expects() {
    return $this
      ->description('Increment a number')
      ->usesParam('startWith', 'The starting value')
        ->whichHasDefault(0)
      ->usesParam('incrementBy', 'The integer number that should be added to the base value each time.')
        ->whichHasDefault(1)
      ->andReturns('The incremented value.');
  }
  public function doCommand() {
    $startWith= $this->param('startWith', 0);
    $incrementBy = $this->param('incrementBy', 1);
    fprintf(STDOUT, "Start With %d and increment by %d\n", $startWith, $incrementBy);
    return $startWith + $incrementBy;
  }

}
