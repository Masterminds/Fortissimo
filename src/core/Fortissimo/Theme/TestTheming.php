<?php

/**
 * Example of a BaseThemePackage.
 *
 * @deprecated This should go away.
 * @ingroup Theme
 */
class TestTheming extends BaseThemePackage {
  
  public function templates() {
    return array('main' => 'test.php');
  }
  
  public function functions() {
    return array(
      'link' => array($this, 'doLink'), 
    );
  }
  
  /**
   * Make a link.
   *
   * Takes two arguments:
   * - url: The URL to link to
   * - text: The text of the link.
   */
  public function doLink(&$v) {
    return '<a href="' . $v['url'] . '">' . $v['text'] . '</a>';
  }
  
}
