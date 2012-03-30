<?php
/** @file
 * A command for redirecting using HTTP 3xx.
 *
 * @author mbutcher
 */
namespace Fortissimo\Command\HTTP;
/**
 * Issue a redirect.
 *
 * This command takes a URL and an (optional) HTTP code and sends a redirect to the client. This 
 * is useful in cases where the client needs to be sent to a different URL. However, if all 
 * you need to do is start a different Fortissimo request, this may be unnecessary overhead, as
 * you can throw a FortissimoForwardRequest and have Fortissimo start a new request.
 *
 * Typically, this is used to perform 301/302 redirects, though you can use it for any define
 * redirect.
 *
 * Parameters: 
 *  - url: A full (absolute) URL, e.g. 'http://example.com'
 *  - redirect_type: One of 301, 302, 303, 304, 305, 307. The default is 301.
 *
 * @ingroup Fortissimo
 */
class Redirect extends \Fortissimo\Command\Base {

  public function expects() {
    return $this
      ->description('Redirect the user agent using an HTTP 3xx')
      ->usesParam('redirect_type', 'The type of redirect (301, 302, 303, 304, 305, 307) to issue.')
      ->whichHasDefault('301')
      ->withFilter('validate_regexp', array('options' => array('regexp' => '/^30([1-5]?|7)$/')))
      //->withFilter('int', array('min_range' => 301, 'max_range' => 307))
      ->usesParam('url', 'The URL to which we should redirect.')
      ->withFilter('url')
      ->whichIsRequired()
      ->andReturns('Nothing; It throws an Interrupt, which terminates the request.')
      ;
  }

  public function doCommand() {
    $code = $this->param('redirect_type');
    $url = $this->param('url');

    // Set the location HTTP header.
    header('Location:' . $url, TRUE, $code);

    // Stop processing of this request.
    throw new \Fortissimo\Interrupt('Redirect to ' . $url . ' using HTTP ' . $code);
  }
}
