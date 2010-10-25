<?php
/** @file
 * A command for redirecting using HTTP 3xx.
 */

/**
 * Issue a redirect.
 *
 * Typically, this is used to perform 301/302 redirects, though you can use it for any define
 * redirect.
 */
class FortissimoRedirect extends BaseFortissimoCommand {
  
  public function expects() {
    return $this
      ->description('Redirect the user agent using an HTTP 3xx')
      ->usesParam('redirect_type', 'The type of redirect (301, 302, 303, 304, 305, 307) to issue.')
      ->whichHasDefault('301')
      //->withFilter('validate_regex', '^30([1-5]?|7)$')
      ->usesParam('url', 'The URL to which we should redirect.')
      ->withFilter('url')
      ->whichIsRequired()
      ->andReturns('Nothing; It throws an Interrupt, which terminates the request.')
      ;
  }
  
  public function doCommand() {
    $code = $this->param('redirect_type');
    $url = $this->param('url');
    
    header('Location:' . $url, TRUE, $code);
    throw new FortissimoInterrupt('Redirect to ' . $url . ' using HTTP ' . $code);
  }
}