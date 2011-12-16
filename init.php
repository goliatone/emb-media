<?php defined('SYSPATH') or die('No direct script access.');

$config = Kohana::$config->load('media');
GBugger::log("initialize media");

Route::set('emb-media', $config->route, $config->regex)
	->defaults(array(
		'controller' => 'media',
		'action'     => 'serve',
	));

unset($config);