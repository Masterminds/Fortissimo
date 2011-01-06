<?php
/** @file
 *
 * RenderTheme is a BaseFortissimoCommand class.
 *
 * Created by Matt Butcher on 2010-12-23.
 */

/**
 * A Fortissimo command.
 *
 * @author Matt Butcher
 */
class RenderTheme extends BaseFortissimoCommand {

  public function expects() {
    return $this
      ->description('Render the given variables through the given theme.')
      ->usesParam('variables', 'The theme variables')
      ->whichIsRequired()
      ->usesParam('theme', 'The theme target')
      ->whichIsRequired()
      ->andReturns('A themed string.')
    ;
  }

  public function doCommand() {
    $target = $this->param('theme');
    $variables = $this->param('variables');
    return Theme::render($target, $variables);
  }
}

