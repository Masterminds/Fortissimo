<?php
/**
 * @file
 *
 * Provides functional Fold Left iterator.
 */
namespace Fortissimo\Command\Flow;

/**
 * Fold a list left.
 *
 * This takes an initial start value, a list, and an anonymous function 
 * and folds the lest "left" into the start value.
 *
 * @code
 *
 * $registry->route('fold')
 *   ->does('\Fortissimo\Command\Flow\FoldLeft', 'sum')
 *     ->uses('start', 5)
 *     ->uses('list', array(1, 1, 2))
 *     ->uses('command', function ($start, $head, $cxt) { return $start + $head; })
 *     ;
 * @endcode
 *
 * The function above adds the current value to the first value of the 
 * list and returns. In a fold operation, this function is run in 
 * sequence (left to right) for each item in the list. Since there are 
 * three values in `list`, the callback will be executed three times:
 *
 *- 5 + 1 = 6
 *- 6 + 1 = 7
 *- 7 + 2 = 9
 *
 * Consequently, the value of `$context->get('sum')` will be 9.
 *
 * ## Anonymous Functions
 *
 * Functions are passed three parameters:
 *
 * - start: the start value
 * - head: the head of the list
 * - context: the current Fortissimo::ExecutionContext.
 *
 * ## Commands
 *
 * You can fold using a Command instead of an anonymous function.
 *
 * The command will be given only two parameters:
 * - start: The start value
 * - head: the head value
 *
 * For examples of things you can do with `FoldLeft` you may wish to 
 * read these: 
 *- http://oldfashionedsoftware.com/2009/07/30/lots-and-lots-of-foldleft-examples/
 *- http://oldfashionedsoftware.com/2009/07/10/scala-code-review-foldleft-and-foldright/
 *- https://dibblego.wordpress.com/2008/01/15/scalalistfoldleft-for-java-programmers/
 */
class FoldLeft extends Wrapper {
  public function expects() {
    return $this
      ->description('Fold a list left (from head to tail)')
      ->usesParam('list', 'The list of items to fold')->whichIsRequired()
      ->usesParam('start', 'The initial value that the list will be folded into.')
        ->whichHasDefault(0)
      ->usesParam('command', 'A callable or a command to be run.')->whichIsRequired()
      ;
  }

  public function doCommand() {
    $list  = $this->param('list');
    $start = $this->param('start');
    $fn    = $this->param('command');

    // Get the current fold value.
    $z = $this->get($this->name, $start);

    $z = $this->fold($z, $list, $fn);

    return $z;
  }

  /**
   * Perform a fold.
   *
   * @param mixed $z
   *   The start value.
   * @param array $list
   *   An indexed array.
   * @param callable $fn
   *   The callable.
   * @return mixed
   * @retval mixed
   *   The final result of the fold.
   */
  public function fold($z, $list, $fn) {
    if (is_callable($fn)) {
      return $this->foldByCallback($z, $list, $fn);
    }
    else {
      return $this->foldByCommand($z, $list, $fn);
    }
  }

  /**
   * Fold using a callaback.
   */
  public function foldByCallback($z, $list, $fn) {
    foreach ($list as $head) {
      $z = $fn($z, $head, $this->context);
    }
    return $z;
  }
  /**
   * Fold using a command.
   *
   * This gives the command 'start' and 'head'.
   */
  public function foldByCommand($z, $list, $klass) {
    $tmpName = md5(time());

    // Loop through the list
    foreach ($list as $head) {
      $params = array('start' => $z, 'head' => $head);
      $obj = new $klass($tmpName);
      $cmd->execute($params, $this->context);
      $z = $this->context->get($tmpName, NULL);
    }
    return $z;
  }

}
