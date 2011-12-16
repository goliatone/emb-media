<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Helper class to manage insertion of media in site.
 *
 * @package    	Media
 * @author 		Emiliano Burgos <hello@goliatone.com>
 * @copyright  	(c) 20011 Emiliano Burgos
 * @license    	http://kohanaphp.com/license
 * 
 * ####TODO 
 * 
 * - Implement order system and sorting, to give weight to elements.
 * - Refactor, remove static methods and move implementation into Core_Resources 
 */
class Resources
{
	/**
	 * 
	 */	
	const JS_HEADER  = 'header';
	
	/**
	 * @const string Signifies a JS positining region: footer.
	 */
	const JS_FOOTER  = 'footer';
	
	/**
	 * 
	 */
	const JS_SNIPPET = 'snippets';
	
	/**
	 * 
	 * @var Core_Resources
	 */
	private static $_instance;
	
	/**
	 * @var array Holds all meta tags.
	 */
    protected $_metas = array();
    
	/**
	 * @var array Holds all CSS styles.
	 */
    protected $_styles = array();
    
	/**
	 * @var array Holds all JS scripts and snippets.
	 */
    protected $_scripts = array('header'=>array(), 'footer'=>array(),'snippets' => array());
	
	/**
	 * 
	 * @private
	 */
    private function __construct() 
    {
    	// A private constructor; prevents direct creation of object
    	$this->_configure( );
    	
    }
	
	/**
	 * Collect initial resources from config.
	 * 
	 * @private
	 * @return void
	 */
	private function _configure()
	{
		$config = Kohana::$config->load('resources');
		
		if(isset($config->meta))   $this->_metas   = $config->meta;
    	if(isset($config->styles)) $this->_styles  = $config->styles;
		
    	if(isset($config->scripts))
		{
			foreach($config->scripts as $script => $location )
			{
				$this->_scripts = Arr::merge( $this->_scripts, array($location=>array($script)));
			} 
				
		}
	}
	
	/**
	 * 
	 * @return Resources
	 */
    public static function instance() 
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }
	
	/**
	 * Will output all metadata formated in their wrapping tags.
	 * 
	 * @return string HTML formated meta tag list.
	 */
	public static function get_metadata()
	{
		echo HTML::metadata( self::instance()->metas);  
	}
	
	/**
	 * Will output all CSS links.
	 * 
	 * @return string HTML formated CSS link list.
	 */
	public static function get_styles()
	{
		echo HTML::styles( self::instance()->styles);
	}
	
	/**
	 * Tells wheter a certain location has associated scripts. 
	 * There are three posible regions, <code>JS_HEADER</code>, <code>JS_FOOTER</code>, and <code>JS_SNIPPET</code>
	 * 
	 * @param string $location Script location in layout.
	 * @return boolean Wheter a certain location has associated scripts.
	 */
	public static function has_scripts($location)
	{
		return (count(self::instance()->scripts[$location]) > 0);
	}
	
	/**
	 * @param string $location Script location in layout.
	 * @return string HTML formated JS link or snippet list.
	 */
	public static function get_scripts($location)
	{
		if( ! self::instance()->has_scripts($location)) return '';
		
		if( $location === self::JS_SNIPPET) echo HTML::snippets(self::instance()->scripts[$location], NULL, TRUE);
		else echo HTML::scripts(self::instance()->scripts[$location], NULL, TRUE);
	}
	
	/**
	 * @param string $name	Metadata name.
	 * @param string $content	Metadata content.
	 * @param string $overwrite	Should we overwrite or append.
	 * 
	 * @return Resources
	 */
	public function metadata($name,$content, $overwrite = TRUE )
	{
		if( empty(self::instance()->_metas[$name]))
		{
			self::instance()->_metas[$name] = $content;
		} 
		elseif($overwrite)
		{
			self::instance()->_metas = Arr::overwrite(self::instance()->_metas, array($name => $content));	
		}
		else
		{
			//thought for keyword metadata, to merge default values.
			$content = explode(',', $content);
			$old 	 = explode(',', self::instance()->_metas[$name]);
			$content = Arr::merge($old, $content);
			$content = implode(',', $content);
			self::instance()->_metas = array($name => $content);
		}		
		
		return $this;
		 
	}
	
	/**
	 * 
	 * @param mixed $script
	 * @param string $location 
	 * 
	 * @return Resources
	 */
	public function js($script, $location = 'header')
	{
		self::instance()->_scripts = Arr::merge( self::instance()->_scripts, array($location=>array($script)));
		
		return $this;
	}
	
	/**
	 * @param 	string 	$heref 	Href to the CSS stylesheet.	
	 * @param 	string	$media	CSS media type. i.e. screen,print
	 * @return 	Resources
	 */
    public function css($href, $media = 'screen')
	{	
		/*if ( ! 'http://' == substr($href, 0, 7)   )
        { 
				$href = URL::site($href,TRUE);
		}*/
		
		self::instance()->_styles = array_merge( self::instance()->_styles, array($href => $media));
		
		return $this;
	}	
	
	
	/**
	 * PHP magic getter method.
	 * 
	 * @private
	 */
	public function & __get($key)
	{
		$key = '_'.$key;
		
		if ( isset($this->{$key}))
		{
			return $this->{$key};
		}		
		else
		{
			throw new Kohana_Exception('Resource not found: :var',
				array(':var' => $key));
		}
	}	
	
	
	// Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }
}