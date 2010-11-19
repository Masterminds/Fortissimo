<?php
/**
 * A command for using the Twig template engine from within Fortissimo.
 *
 * @ingroup Fortissimo
 */
 
/**
 * This uses the Twig autoloader for loading templates and the Twig engine.
 * Typically, Twig is installed via Pear, and is automatically added to the 
 * PHP library path.
 */
require_once 'Twig/Autoloader.php'; 
 
/**
 * This command provides Twig template engine support.
 *
 * It loads templates from the file system and then renders the supplied data through
 * the template. It returns a string, so that the results can be cached (or 
 * otherwise manipulated) before being sent to the client.
 *
 * The command accepts a large number of parameters, most of which are passed
 * directly to the Twig template engine. You may find it useful to 
 * browse the Twig documentation (http://www.twig-project.org).
 *
 * <b>Why not send data directly to the client?</b>
 * This command returns a string instead of writing directly to the client. It
 * does this for one very important reason: It makes it possible for other 
 * commands to intercept the output and manipulate it before passing it on.
 * This is highly desirable when apps need to cache output.
 * 
 * @see http://www.twig-project.org
 *
 * @ingroup Fortissimo
 */
class FortissimoTemplate extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
    ->description('Renders variables into a Twig template.')
    
    ->usesParam('variables', 'Variables to be passed to the template. These should be an associative array or a FortissimoExecutionContext (i.e. a context).')
    
    ->usesParam('template', 'The template to use. This name is appended to the template directory (if supplied) and then loaded from the file system.')
    ->withFilter('string')
    ->whichIsRequired()
    
    ->usesParam('templateDir', 'The main directory for templates. Paths to templates are created by adding base path to (optional) template dir, and then appending the template. No leading or trailing slashes! To search multiple directories, separate the directories by a comma, and order from most to least important.')
    ->withFilter('string')
    
    ->usesParam('templateCache', 'The location for cached compiled templates. Must be writable by application.')
    
    ->usesParam('disableCache', 'If this is true, the template cache will be disabled.')
    ->withFilter('boolean')
    
    ->usesParam('debug', 'A flag indicating whether debugging output should be enabled.')
    ->withFilter('boolean')
    
    ->usesParam('trim_blocks', 'Mimicks the behavior of PHP by removing the newline that follows instructions if present (default to false).')
    ->withFilter('boolean')
    
    ->usesParam('base_template_class', 'Base class for templates (Default: Twig_Template)')
    
    ->usesParam('charset', 'Character set to use. Default: utf-8')
    ->withFilter('string')
    
    ->usesParam('auto_reload', 'If this is true, templates will be automatically rebuilt each time the source code is updated.')
    ->withFilter('boolean')
    
    ->andReturns('A string containing the content as rendered through a template.')
    ;
  }
  
  public function doCommand() {
    
    $baseDir = $this->param('templateDir', '');
    $template = $this->param('template');
    $variables = $this->param('variables', $this->context);
    $cache = $this->param('templateCache', './cache');
    
    if (!is_array($variables)) {
      if ($variables instanceof FortissimoExecutionContext) {
        $variables = $variables->toArray();
      }
      else {
        throw new FortissimoInterruptException('Variable data was not passed to the command.');
      }
    }
    
    // Twig supports multiple base template directories.
    $templateDir = explode(',', $baseDir);
    
    // Set up the Twig configuration array.
    $cache_val = $this->param('disableCache', FALSE) ? FALSE : $this->param('templateCache', NULL);
    $twigConfig = array(
      'debug' => $this->param('debug', FALSE),
      'charset' => $this->param('charset', 'utf-8'),
      'base_template_class' => $this->param('base_template_class', 'Twig_Template'),
      'cache' => $cache_val,
      'auto_reload' => $this->param('auto_reload', FALSE),
      'trim_blocks'  => $this->param('trim_blocks', FALSE),
    );
    
    // Render the template into a buffer
    $buffer = $this->renderTemplate($template, $variables, $templateDir, $twigConfig);
    
    // Return the buffer.
    return $buffer;
  }
  
  /**
   * Inject the variables into the template and render the results.
   *
   * This method controls interaction with the Twig engine. It registers Twig
   * classes, loads the Filesystem Loader, sets up the environment, loads
   * the template, and then renders the data into the template -- all a half-dozen
   * lines of code.
   * 
   * @param string $template
   *   The filename of the template.
   * @param array $variables
   *   An associative array of variables.
   * @param mixed $templateDir
   *   A string directory name or an array of directory names.
   * @param array $twigConfig
   *   An associative array of twig configuration directives.
   * @return string
   *   The rendered content.
   */
  public function renderTemplate($template, $variables, $templateDir,  $twigConfig) {
    
    Twig_Autoloader::register();

    $loader = new Twig_Loader_Filesystem($templateDir);
    $twig = new Twig_Environment($loader, $twigConfig);
    $tpl = $twig->loadTemplate($template);
    
    return $tpl->render($variables);
    
  }
}