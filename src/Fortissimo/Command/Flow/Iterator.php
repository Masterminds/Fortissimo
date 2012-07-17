<?php
namespace Fortissimo\Command\Flow;

class Iterator extends \Fortissimo\Command\Base {


  public function expects() {
    return $this
      ->description('Create an iterator.')
      ->usesParam('array', 'The array to convert to an iterator.')->whichIsRequired()
      ->andReturns('An Iterable.')
      ;
  }

  public function doCommand() {
    $array = $this->param('array');
    return new \ArrayIterator($array);
  }
}
