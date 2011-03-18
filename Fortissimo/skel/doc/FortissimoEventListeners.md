Events (Observers) in Fortissimo

In Fortissimo, commands can declare events that other classes can listen for. In this way, an
application can extend not just vertically (chain of command), but also horizontally
(event listeners).

If you are familiar with JavaScript, events in Fortissimo are similar to JavaScript events
like `onload` or `onmouseover`. In fact, we use the `bind()` method in a way almost 
identical to jQuery's `bind()` function.

There are three parts to the Fortissimo event model:

  * Firing Events with Commands: Each command is responsible for declaring its own events. The idea is that
    a command can strategically fire an event to gain more data, notify other components,
    and generally increase the flexibility of the application. *But the command must 
    declare and fire the event.*
  * Creating Event Handlers: A command may declare an event, but for other things to take advantage
    of this, they must provide event handlers. An event handler is a piece of code that responds
    to an event being fired.
  * Configuration: This is where an event is tied to an event handler. Events can either be declared
    at a global level ("every time CommandA is executed, listen for the 'load' event") or at a
    request level ("During this command in this request, listen for the 'load' event").


## Writing a command that declares an event.

Here is an example of a command that declares a single event, `prepare_results`.

The event is declared in the `expects()` function so that the system can register events and
also provide `EXPLAIN` data for each event.

Then, at any arbitrary point in the command, it may call `$this->fireEvent()` with
`prepare_results`, and any observers will have an opportunity to respond.

**Developer's Note:** `fireEvent` is declared in `Observerable`, but implemented in `BaseFortissimoCommand`.
If you are writing a command from scratch, you will need to declare it to be an `Observable` and
write your own observation logic.

It's up to you (the Command author) what you wish to do with the results of an event, but in the
case below, we return that data, assuming that it is a modified version of `$data`.

**Developer's Note:** There is no guarantee that there will be a listener. Code accordingly.

<?php
class FooCommand extends BaseFortissimoCommand {
  
  public function expects() {
    return $this->description('Example of an observeable command.')
      ->declaresEvent('prepare_results', 'This event is run at the end of the command.')
      ->andReturns('An array')
    ;
  }

  public function doCommand() {
    $data = array('a', 'b', 'c');
    
    // Get an array of responses to this event.
    $event_data = $this->fireEvent('prepare_results', $data);
    
    return $data;
  }
}

?>

## Writing an Event Listener

An event observer can be *any* PHP callable. Here we will look at a simple class:

<?php
class PrepareResultListener {
  public static function hello(array $array) {
    print "Hello World";
  }
}
?>

There is nothing special about this class. It's just a regular old class with a single static method. (For our purposes, a static method works well.)

Following the traditional CS paradigm, we just print Hello World from this handler.

Next, we need to add this observer.

## Configuration: Telling a listener to listen for an event.

Let's say we have a basic request called `default`, and let's configure it to use the `FooCommand` that we created above.
<?php
// This is executed if no path is specified.
Config::request('default')
  ->doesCommand('foo')
    ->whichInvokes('FooCommand')
    ->bind('prepare_results', array('PrepareResultListener', 'hello') )
;
?>

In the example above, we use `bind()` to bind a callable (`array('PrepareResultListener', 'hello')`) to the `prepare_results` event.

## Altogether Now!

We've created a command that declares an event.

We've created an event listener.

We've registered the event listener on that command for the default request.

Now, when the default request is executed, it will execute the `FooCommand` command, which will do its thing. At the right moment in execution, it will call `fireEvent('prepare_results')`, which will then execute `PrepareResultListener::hello()`. The hello handler will print `hello world` to the screen and return control to the `FooCommand`, which will finish its own task and return it's array of data.

## Modifying data in an event handler

In many cases, you may wish to modify a command's data from within an event handler. This is how you ought to do that:

<?php
class FooCommand extends BaseFortissimoCommand {
  
  public function expects() {
    return $this->description('Example of an observeable command.')
      ->declaresEvent('prepare_results', 'This event is run at the end of the command.')
      ->andReturns('An array')
    ;
  }

  public function doCommand() {
    $values = array('a', 'b', 'c');
    
    // Pass data by reference
    $data = array('values' => &$values);
    
    // Get an array of responses to this event.
    $event_data = $this->fireEvent('prepare_results', $data);
    
    return $data;
  }
}
class PrepareResultListener {
  public static function hello(array $array) {
    $values =& $array['values'];
    
    // shift off the first item.
    array_unshift($values);
    
    // Now when FooCommand regains control, the $values element will contain 
    // array('b', 'c');
  }
}

?>

## Assigning a global listener

In the examples above, I've explained how to assign an event listener in a specific request. But sometimes it is desirable to assign an event to ALL instances of a particular command -- regardless of request. This can be done using the `Config::listener()` configuration directive.

<?php
// This is executed if no path is specified.
Config::request('default')
  ->doesCommand('foo')
    ->whichInvokes('FooCommand')
;

Config::request('another')
  ->doesCommand('foo')
    ->whichInvokes('FooCommand')
;

Config::listener('FooCommand', 'prepare_results', array('PrepareResultListener', 'hello') )
?>

In the example above, the `PrepareResultListener::hello()` method will be called for both the `default` and the `another` requests.