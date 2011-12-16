<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 * @package    	Media
 * @category	Core
 * @author 		Emiliano Burgos <hello@goliatone.com>
 * @copyright  	(c) 20011 Emiliano Burgos
 * @license    	http://kohanaphp.com/license
 */
abstract class Core_HTML extends Kohana_HTML {
	
	
	/**
	 * 
	 * @param object $metas
	 * @return 
	 */
	public static function metadata($metas)
	{
		$output = '';
		
		foreach( $metas as $name => $content )
			$output .= self::meta($name, $content);
			
		return $output;
	}
	
	/**
	 * 
	 * @param object $name
	 * @param object $content
	 * @return 
	 */
	public static function meta($name, $content)
	{
		if( !isset($name) || ! isset($content)) return '';
		if(is_array($content)) $content = implode(' ', $content);
		
		$attributes = array('name'=>$name, 'content'=>$content);
		
		return '<meta'.HTML::attributes($attributes).' />'.PHP_EOL;
		
		return '<meta name = "'.$name.'" content = "'.$content.'"/>'.PHP_EOL;
	}
	
	/**
	 * 
	 * @param object $codes
	 * @param object $attributes [optional]
	 * @uses    HTML::snippet
	 * @return 
	 */
	public static function snippets($codes, array $attributes = NULL, $index = FALSE)
	{
		$code = implode(PHP_EOL,$codes);
		
		return HTML::snippet($code, $attributes);
	}
	
	/**
	 * Creates a script link.
	 *
	 *     echo HTML::snippet('$(document){alert(\'Hola\');})');
	 *
	 * @param   string   Snippet content
	 * @param   array    default attributes	 
	 * @return  string	 
	 * @uses    HTML::attributes
	 */
	public static function snippet( $code, array $attributes = NULL )
	{
		// Set the script type
		$attributes['type'] = 'text/javascript';
		
		// Set the script type
		$attributes['charset'] = 'utf-8';
		
		$output  = '<script'.HTML::attributes($attributes).'>'.PHP_EOL;
		$output .= $code.PHP_EOL;
		$output .= '</script>'.PHP_EOL;
		
		return $output;
		//return '<script'.HTML::attributes($attributes).'>'.$code.'</script>';
	}
	
	/**
	 * 
	 * @param array $scripts
	 * @param array $attributes [optional]
	 * @param boolean $index [optional]
	 * @uses    HTML::script
	 * @return 
	 */
	public static function scripts(array $scripts, $attributes=array(), $index = FALSE)
	{
		$output = '';
 
		//Data sanitisation
		$index = $index ? TRUE : false;
		if ( !is_array($attributes) ) $attributes = array();
 
		foreach ( $scripts as $script )
		{
			$output .= HTML::script($script, $attributes, $index).PHP_EOL;
		}			
 
		return $output;
	}
 
 	/**
	 * 
	 * @param array $styles
	 * @param array $attributes [optional]
	 * @param boolean $index [optional]
	 * @uses    HTML::style
	 * @return 
	 */
	public static function styles(array $styles, $attributes=array(), $index = FALSE)
	{
		$output = '';
 
		//Data sanitisation
		$index = $index ? TRUE : FALSE;
		if ( !is_array($attributes) ) $attributes = array();
		
		if(Arr::is_assoc($styles))
		{
			foreach ($styles as $file=>$type) 
				$output .= HTML::style($file, array('media'=>$type)).PHP_EOL;
		}
		else
		{
		 	foreach ( $styles as $style )
			$output .= HTML::style($style, $attributes, $index).PHP_EOL;
		}
		 
		return $output;
	}

}