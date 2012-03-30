<?php
/**
 * This command provides support for loading a file from the $_FILES array.
 *
 * @ingroup Fortissimo
 */
namespace Fortissimo\Command\IO;
/**
 * Deal with an uploaded file.
 *
 * This provides basic support for handling uploaded files.
 *
 * Typically, HTML forms upload files via HTTP POST requests. PHP then puts these files into 
 * a special data structure, the $_FILES superglobal augmented by a temp file. This command allows
 * developers to conveniently work with files without needing to do standard checking and validation,
 * and without needing to write large amounts of boilerplate code.
 *
 * Notes on a few parameters:
 *  - inputName: The name of the param that points to the file data. Typically, this will be 'file:inputElementName'
 *    in the commands.xml
 *  - types: A list of MIME types that this should accept from the client.
 *  - extensions: A list of extensions that should be accepted as part of the submitted file name.
 *  - rewriteExtensions: A list of extensions that should be rewritten for security reasons (e.g. 
 *    rewriting index.php to index._php before writing it to the file system)
 *  - rewriteExtensionRule: A simple rule for how the above rewriting should take place.
 *  - moveTo: If this is set and points to a directory, then the file will me moved from its temp
 *    location to the specified directory. Note that if moveTo is NOT specified, the file will
 *    remain in the temp directory, and will be removed by the PHP engine at request's end.
 *
 * This will inject the FULL PATH to the file back into the context. You can then use standard file
 * functions to work with it.
 */
class UploadFile extends \Fortissimo\Command\Base {

  public function expects() {
    return $this
      ->description('Retrieve a posted file and make it available for accessing using a file function.')
      ->usesParam('inputName', 'The name of item to retrieve. Typically, the commands.xml uses "file:someName" for this.')
      ->usesParam('types', 'Specify a list of MIME types as an array or a comma-separated list.')
      ->usesParam('extensions', 'Specify a list (comma-separated string or an array) of extensions that are allowed. It is STRONGLY SUGGESTED that you use this. Example: "png,gif,jpg,jpeg"')
      ->usesParam('rewriteExtensions', 'Specify a list (comma-separated string or an array) of extensions that should be rewritten for security reasons')
      ->usesParam('rewriteExtensionRule', 'The rule to be used for rewriting the extension. The first occurrence of %s will be replaced by the original extension.')
        ->whichHasDefault('_%s')
      ->usesParam('moveTo', 'Specify a directory where this file should be permanently relocated. If none is specified, then the file will be kept in the designated temp space, and will be unlinked automatically.')
        ->withFilter('string')
      ->andReturns('A filename on the file system.');

  }

  public function doCommand() {

    // Get parameters.
    $fileData = $this->param('inputName');
    $acceptedTypes = $this->param('types', NULL);
    $moveTo = $this->param('moveTo', NULL);
    $extensions = $this->param('extensions', NULL);
    $rewriteExtensions = $this->param('rewriteExtensions', NULL);
    $rewriteRule = $this->param('rewriteExtensionRule');

    // Check to see if file data was supplied.
    if (empty($fileData)) {
      throw new \Fortissimo\Exception('No file uploaded.');
    }

    // Check for errors.
    if (!empty($fileData['error'])) {
      throw new \Fortissimo\Exception('Upload failed: ' . $this->getUploadError($fileData['error']));
    }

    // Check that FILE array wasn't hijacked.
    if (!is_uploaded_file($fileData['tmp_name'])) {
      throw new \Fortissimo\Exception('Upload failed: File failed security check.');
    }

    // Check that MIME type is allowed.
    if (!empty($acceptedTypes) && !$this->checkMIMEType($fileData['type'], $acceptedTypes)) {
      throw new \Fortissimo\Exception('Upload failed: Incorrect file type.');
    }

    // Check that extension is allowed.
    if (!empty($extensions) && !$this->checkExtension($fileData['name'], $extensions)) {
      throw new \Fortissimo\Exception('Upload failed: Incorrect file extension.');
    }

    // Rewrite extensions if necessary.
    if (!empty($rewriteExtensions)) {
      $fileData['name'] = $this->replaceExtension($fileData['name'], $rewriteExtensions, $rewriteRule);
    }

    // Check to see if we need to move this file.
    if (!empty($moveTo)) {
      $filename = $this->relocateFile($fileData, $moveTo);
    }
    else {
      $filename = $fileData['tmp_name'];
    }

    return $filename;
  }

  /**
   * Move a file to a new location.
   *
   * @param array $fileData
   *  The array of file data from $_FILES.
   * @param string $destination
   *  A directory on the file system. This must be writable.
   * @throws \Fortissimo\Exception
   *  Throws an exception when the file cannot be written.
   * @return string
   *  Returns the filename (path and all) of the returned file.
   */
  protected function relocateFile($fileData, $destination) {
    if (!is_dir($destination) || !is_writable($destination)) {
      throw new \Fortissimo\Exception('Could not write to the destination directory.');
    }

    $destFilename = $destination . DIRECTORY_SEPARATOR . $fileData['name'];
    if (file_exists($destFilename)) {
      // FIXME: Should we try to automatically rename the file instead of doing this?
      throw new \Fortissimo\Exception('File already exists and cannot be overwritten.');
    }

    if (!move_uploaded_file($fileData['tmp_name'], $destFilename)) {
      throw new \Fortissimo\Exception('Failed to move uploaded file.');
    }

    return $destFilename;
  }

  /**
   * Check whether the given file has an allowed extension.
   *
   * This is useful as a security precaution. Make sure that 
   * uploaded files don't have an extension that might
   * be cause for security concern (e.g. php, exe, cgi, etc.)
   *
   * Example:
   * <code>
   * $result = $obj->checkExtension('myfile.txt', 'txt,doc,rtf');
   * </code>
   *
   * @param string $filename
   *  The name of the filename to test. This can have a path.
   * @param mixed $extensions
   *  An array of allowed extensions or a string containing a comma-separated
   *  list of extensions. DO NOT INCLUDE A LEADING DOT. 
   *  - The special value '*' will return true for ANY extension.
   *  - Compound extensions (e.g. tar.gz) are not checked. Only the last extension (gz) is checked.
   * @return boolean
   *  Returns TRUE if the filename has an extension in the 
   *  extensions list.
   */
  public function checkExtension($filename, $extensions) {
    if (is_string($extensions)) {
      $extensions = array_map('trim', explode(',', $extensions));
    }

    // Checking isset() is faster than in_array(), so we flip.
    $extensions = array_flip($extensions);

    // Always pass in this case.
    if (isset($extensions['*'])) return TRUE;

    // Get the extension.
    //$fileInfo = pathinfo($filename);
    //$ext = $fileInfo['extension'];

    $ext = pathinfo($filename, PATHINFO_EXTENSION);

    return isset($extensions[$ext]);
  }

  /**
   * Given a filename and a list of extensions and a rule, rewrite any problematic extensions.
   *
   * @param string $filename
   *  The filename to test.
   * @param mixed $extensions
   *  The extensions to test. If they match, then the file will receive a new extension.
   *  This can be either an array of extensions or a string containing a comma-separated list of
   *  extensions.
   * @param string $rewriteRule
   *  The transformation that will be performed on the extension to rewrite it. At this time,
   *  a simple sprintf filter is all that is supported, where the first %s will be replaced by
   *  the original extension. e.g. %.txt will transform index.php to index.php.txt.
   * @return string
   *  The name of the file, with replacements made (if necessary).
   */
  public function replaceExtension($filename, $extensions, $rewriteRule) {
    if (is_string($extensions)) {
      $extensions = array_map('trim', explode(',', $extensions));
    }

    // Checking isset() is faster than in_array(), so we flip.
    $extensions = array_flip($extensions);

    // Get the extension.
    $fileInfo = pathinfo($filename);
    $ext = $fileInfo['extension'];

    if (isset($extensions[$ext])) {
      $newExt = sprintf($rewriteRule, $ext);
      $fname = $fileInfo['filename'] . '.' . $newExt;

      // Prepend base dir if necessary.
      if (!empty($fileInfo['dirname'])) {
        $fname = $fileInfo['dirname'] . DIRECTORY_SEPARATOR . $fname;
      }
      return $fname;
    }

    return $filename;
  }

  /**
   * Check that a given file type is allowed.
   *
   * This compares the given MIME type with the list of allowed MIME types. It returns 
   * true if either the MIME is found or the * MIME type is listed in $allowed.
   *
   * @param string $given
   *  The given MIME type
   * @param mixed $allowed
   *  An array or comma-separated list of MIME types that are allowed. * / * matches all.
   * @return boolean
   *  TRUE if the given MIME type is allowed, FALSE otherwise.
   */
  public function checkMIMEType($given, $allowed) {
    if (is_string($allowed)) {
      $allowed = array_map('trim', explode(',', $allowed));
    }

    // We do this for a speedier lookup, though it only makes
    // a difference on very large lists of $allowed.
    $allowed = array_flip($allowed);

    // */* matches everything.
    if (isset($allowed['*/*'])) return TRUE;

    return isset($allowed[$given]);
  }

  /**
   * Ascertain the cause of the upload error.
   *
   * @param int $err
   *  The error code.
   * @return string
   *  An error message.
   */
  protected function getUploadError($err) {
    switch ($err) {
      case UPLOAD_ERR_INI_SIZE:
        return 'The file is larger than the maximum size the server allows.';
      case UPLOAD_ERR_FORM_SIZE:
        return 'The file is larger than the form allows.';
      case UPLOAD_ERR_PARTIAL:
        return 'The file was only partially loaded.';
      case UPLOAD_ERR_NO_FILE:
        return 'No file was uploaded.';
      case UPLOAD_ERR_NO_TMP_DIR:
        return 'Server file storage is not available.';
      case UPLOAD_ERR_CANT_WRITE:
        return 'File could not be written on server.';
      case UPLOAD_ERR_EXTENSION:
        return 'An unspecified PHP extension prevented the upload.';
    }
    return 'An unknown error occured while processing the file.';
  }


  /**
   * Utility function to get the original file name.
   * This is useful for error reporting.
   *
   * @param string $filename
   *  The name of the current filename.
   * @return array
   *  An array with the following keys:
   *  - found: TRUE if the item was successfully located
   *  - name: The name. If the lookup was unsuccessful, $filename is used.
   *  - param: The name of the param used to upload. This can be useful for error reporting.
   */
  public static function originalName($filename) {
    foreach ($_FILES as $param_name => $info) {
      if ($info['name'] == $filename || $info['tmp_name'] == $filename) {
        return array(
          'param' => $param_name,
          'name' => $info['name'],
          'found' => TRUE,
        );
      }
    }
    return array(
      'name' => $filename,
      'found' => FALSE,
    );
  }
}
