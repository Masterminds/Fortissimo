<?php
/**
 * This is the main class for the Fortissimo framework generator.
 */
class Fortissimo {
  public static function getBuildXML() {
    return dirname(__FILE__) . '/Fortissimo/fortissimo.xml';
  }
  
  public static function getLibraryPath() {
    return dirname(__FILE__);
  }
}