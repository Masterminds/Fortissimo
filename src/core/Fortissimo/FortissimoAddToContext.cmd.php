<?php
/**
 * Command to add arbitrary parameters to the {@link FortissimoExecutionContext}
 * of the current request.
 * @ingroup Fortissimo
 */

/**
 * Command to add arbitrary data to the context.
 *
 * This reads all parameters and adds them to the current context.
 */
class FortissimoAddToContext implements FortissimoCommand, Explainable {
  
  protected $name, $caching;
  
  public function __construct($name, $caching = FALSE) {
    $this->name = $name;
    $this->caching = $caching;
  }
  
  public function isCacheable () {
    return TRUE;
  }
  
  public function execute($params, FortissimoExecutionContext $cxt) {
    foreach ($params as $name => $value) {
      $cxt->add($name, $value);
    }
  }
  
  public function explain() {
    $klass = new ReflectionClass($this);
    $desc = 'Add all parameters to the context.';
    $cmdFilter = "CMD: %s (%s): %s\n\tRETURNS: Nothing" . PHP_EOL . PHP_EOL;
    return sprintf($cmdFilter, $this->name, $klass->name, $desc);
  }
  
}