<?php
/** @file
 * Testing configuration file.
 */
Config::includePath("test/Tests/Fortissimo/Stubs");
Config::includePath("test/Tests/Fortissimo");

Config::logger('fail')->whichInvokes('FortissimoArrayInjectionLogger');

Config::group('bootstrap')
  ->doesCommand('database')
    ->whichInvokes('SimpleDatabaseAccess')
    ->withParam('dsn')->whoseValueIs('MyDSN')
    ->withParam('user')->whoseValueIs('user')
    ->withParam('password')->whoseValueIs('pass')
;

Config::group('grouptwo')
  ->doesCommand('groupTwoCommand')
    ->whichInvokes('CommandMockThree')
    ->withParam('deleteMe')->whoseValueIs('Test')
;

Config::request('default')
  ->usesGroup('bootstrap')
  ->doesCommand('echo')
    ->whichInvokes('FFEchoCommand')
    ->withParam('message')->whoseValueIs('Echo this')
  ->doesCommand('template')
    ->whichInvokes('template')
    ->withParam('template_name')->whoseValueIs('templates/main.html')
    ->withParam('something_id')->from('get:something')
    ->withParam('my_session')->from('session:session_id')
    ->withParam('echo_out')->from('cmd:echo')
    ->withParam('foo')->from('get:primary post:secondary')->whoseValueIs('Default value')
;

Config::request('item')
  ->doesCommand('echo')
    ->whichInvokes('FFEchoCommand')
    ->withParam('message')->whoseValueIs('Test')
  ->doesCommand('dump')
    ->whichInvokes('FFVarDumper')
    ->withParam('variable')->from('cmd:echo')
;

Config::request('testHandleRequest1')->doesCommand('mockCommand')->whichInvokes('MockCommand');
Config::request('testHandleRequest2')
  ->doesCommand('mockCommand2')
  ->whichInvokes('MockCommand')
    ->withParam('value')->whoseValueIs('From Default')
;

Config::request('testHandleRequest3')
  ->doesCommand('mockCommand2')
    ->whichInvokes('MockCommand')
    ->withParam('value')->whoseValueIs("From Default 2")
  ->doesCommand('repeater')
    ->whichInvokes('CommandRepeater')
    ->withParam('cmd')->from('cmd:mockCommand2')->whoseValueIs('Binky was here')
;

Config::request('testForwardRequest1')
  ->doesCommand('forwarder')
    ->whichInvokes('CommandForward')
    ->withParam('forward')->whoseValueIs('testForwardRequest2')
;

Config::request('testForwardRequest2')
  ->doesCommand('mockCommand2')
    ->whichInvokes('MockCommand')
    ->withParam('value')->whoseValueIs('From Default')
;

Config::request('testRequestCache1')
  ->isCaching(TRUE)
  ->doesCommand('mockCommand2')
    ->whichInvokes('MockCommand')
    ->withParam('value')->whoseValueIs('From Default')
;

Config::request('testRequestCache2')
  ->isCaching(TRUE)
  ->doesCommand('barprint')
    ->whichInvokes('MockPrintBarCommand')
;

Config::request('testBaseFortissimoCommand1')
  ->doesCommand('simpleCommandTest1')
    ->whichInvokes('SimpleCommandTest')
    ->withParam('testString')->whoseValueIs('String1')
    ->withParam('testNumeric')->whoseValueIs(3.5)
    ->withParam('testNumeric2')->whoseValueIs(3.5)
;

Config::request('dummy')
  ->doesCommand('mock1')->whichInvokes('CommandMockOne')
    ->withParam('variable0')->whoseValueIs('Default Value')
    ->withParam('variable1')->from('get:test1')
    ->withParam('variable2')->from('get:test2')->whoseValueIs('Default Value')
    ->withParam('variable3')->from('POST:test3')
    ->withParam('variable4')->from('session:test4')
    ->withParam('variable5')->from('environment:test5')
    ->withParam('variable6')->from('cookie:test6')
  ->doesCommand('mock2')->whichInvokes('CommandMockTwo')
    ->withParam('variable0')->whoseValueIs('Default Value')
    ->withParam('variable1')->from('g:test1')
    ->withParam('variable2')->from('g:test2')->whoseValueIs('Default Value')
    ->withParam('variable3')->from('p:test3')
    ->withParam('variable4')->from('s:test4')
    ->withParam('variable5')->from('env:test5')
    ->withParam('variable6')->from('cmd:mock1')
  ->usesGroup('grouptwo')
  ->doesCommand('mock4')->whichInvokes('CommandMockOne')
;
