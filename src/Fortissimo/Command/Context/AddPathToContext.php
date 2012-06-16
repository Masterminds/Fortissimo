<?php
/**
 * @file
 * Parse a path and add the components to the context.
 */
namespace Fortissimo\Command\Context;
/**
 * Parse a path and add its path components to the context.
 * @since 2.0.0
 *
 * For example, this can take  foo/bar/123 and a description of 
 * the parameters it expects, and parse it into an
 * associative array:
 *
 * @code
 * <?php
 *
 * $registry->route('someRoute')
 *   ->does('\Fortissimo\Command\Context\AddPathToContext', 'c')
 *   ->using('path', 'foo/bar/123')
 *   ->using('template', '%s/%s/%d')
 *   ->using('names', array('name', 'type', 'record_id'))
 * ;
 * ?>
 *
 * Names are applied in order of match. So 'name' above will be assigned 
 * to the match of the first '%s', which will be 'foo'.
 *
 * Running the route will add the following into the context:
 *
 * @code
 * <?php
 *  array(
 *    'name' => 'foo',
 *    'type' => 'bar',
 *    'record_id' => 123,
 *  );
 * ?>
 * @endcode
 *
 * This also supports more advanced scans. Here is an example from the
 * unit tests:
 *
 *
 * @code
 * <?php
 *  $path = 'start-foo-end/bar/id-123';
 *  $template = 'start-%[a-z]-end/%s/id-%d';
 * ?>
 * @endcode
 *
 * When the above template is applied to the path, it will pick out:
 *
 *- foo
 *- bar
 *- 123
 *
 * For more information on that style of C++ scanning, see
 * the stdio reference:
 * http://www.cplusplus.com/reference/clibrary/cstdio/sscanf/
 */
class AddPathToContext extends \Fortissimo\Command\Base {
  const RSEP = "\t";
  public function expects() {
    return $this->description('Parse a path and store the path parts as named context items.')
      ->usesParam('path', 'The path to parse')->whichIsRequired()
      ->usesParam('template', 'A template of what the entry is expected to look like')
        ->whichIsRequired()
      ->usesParam('names', 'An array of names to which these values will be assigned.')
    ;
  }

  public function doCommand() {
    $path = $this->param('path');
    $template = $this->param('template');
    $names = $this->param('names');

    $res = $this->scan($path, $template, $names);

    $this->context->addAll($res);
  }

  public function scan($path, $template, $names) {

    // To make sscanf see a slash as a separator,
    // we replace the slash with a whitespace
    // character.
    $path = strtr($path, '/', self::RSEP);
    $template = strtr($template, '/', self::RSEP);

    $matches = sscanf($path, $template);

    // array_combine is not robust enough to handle
    // sscanf failures.
    $count = count($matches);
    $add = array();
    for ($i = 0; $i < $count; ++$i) {
      $add[$names[$i]] = $matches[$i];
    }

    return $add;

  }
}
