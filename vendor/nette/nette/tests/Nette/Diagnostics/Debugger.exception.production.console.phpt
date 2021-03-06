<?php

/**
 * Test: Nette\Diagnostics\Debugger exception in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$consoleMode = TRUE;
Debugger::$productionMode = TRUE;

Debugger::enable();

register_shutdown_function(function(){
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
});
ob_start();


throw new Exception('The my exception', 123);
