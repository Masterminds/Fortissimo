<?php
/**
 * Forms processor general code.
 */
 
/**
 * Form definition processor.
 */
class Form {
  
  /**
   * A QueryPath object wrapping the form.
   */
  protected $form = NULL;
  
  /**
   * @param mixed $form
   *  An XML document in any format supported by {@link QueryPath}.
   */
  public function __construct($form) {
    $this->form = qp($form);
  }
  
  public function getName() {
    $name = $this->form->find(':root')->attr('name');
    return isset($name) ? $name : '';
  }
  
  public function getFormItems($deep = TRUE) {
    
  }
  
  /**
   * Process the submitted form data according to the rules in the form definition.
   */
  public function process() {
    
  }
  
  public function toHTML() {
    
  }
  
  public function toFormXML() {
    
  }
}

/**
 * A form item is any individual component in a form.
 *
 * Typically, forms are composed of two types of item: Groups and fields. A 
 * group is an aggregation of fields. A field is an individual input element or 
 * textual component.
 */
abstract class FormItem implements Explainable {
  
  abstract function expects();
  
  /**
   * Every form item should be able to extract itself from Fortissimo Form XML.
   *
   * @param QueryPath $doc
   *  The document from which the form item definition can be extracted.
   */
  public function fromFormXML(QueryPath $doc);
  
  /**
   * Get the value or values of all items that this form item contains.
   */
  public function getValues();
  
  /**
   * Every form item should be able to represent itself as HTML.
   * @param QueryPath doc
   *  The document that this form item should append itself to. The form data
   *  should be added with {@link QueryPath::append()}.
   */
  public function inHTML(QueryPath $doc);
  /**
   * Every form item should be able to represent itself as Fortissimo Form XML.
   *
   * @param QueryPath doc
   *  The document that this form item should append itself to. The form data
   *  should be added with {@link QueryPath::append()}.
   */
  public function inFormXML(QueryPath $doc);
}

/**
 * A group containing other items.
 *
 */
class FormGroup implements explainable {
    
  public function getFormItems() {
    
  }
}