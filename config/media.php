<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'route' => 'media/(<action>/)<file>(<sep><uid>).<ext>',
	'regex' => array(
		'action' => 'serve|find',
		/**
		 * Pattern to match the file path (without extension)
		 * http://localhost/ko32/media/css/messages-20.00.50.css
		 * This pattern will match any file path until one of the following:
		 * - an extension is found
		 * - a forward slash is found, followed by a version number (#.#.#) where # is one or more digits
		 */	
		'file' => '(.*?)((?=(\.([a-zA-Z0-9]+)$))|(?=\-(?=([0-9]+\.){3})))',
		// Match the separator between file and hash
		'sep'  => '([\-])(?=([0-9]+\.){3})',
		// Match the unique string that is not part of the media file
		'uid' => '([a-zA-Z0-9\.])+(?=[\.][a-zA-Z0-9]+$)',
		// Match the file extension (without the dot)
		'ext'  => '([a-zA-Z0-9]+)$',
	),	
	// The directory to look for src media, also can be array.
	'source_dir' => array('views'),	
	//'source_dir' => 'views',	
	// The public accessible directory
	'output_dir' => DOCROOT.'media',
	// Write the files to the public directory when in production
	//'cache'      => Kohana::$environment === Kohana::PRODUCTION,
	'cache'      => Kohana::$environment === Kohana::DEVELOPMENT,
	'cache_for' => 3600,//how long do we cache stuff?
	// Compress the files...
	'compress'      => Kohana::$environment === Kohana::DEVELOPMENT,
	/**
	* Options for configuring YUI Compressor
	*/
	'yui' => array(
		'options' => NULL,
		'java' => 'java',
		'jar' => MODPATH.'emb-media/vendor/yui/yuicompressor-2.4.2.jar',
	),
	/**
	* Options for configuring YUI Compressor
	*/
	'smushit' => array(
		'vendor' => 'vendor',
		'file_path' => 'smushit/smushit',
	),
);