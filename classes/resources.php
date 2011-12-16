<?php 
defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to manage insertion of media in site.
 * 
 * TODO	Implement order system and sorting, to give weight to elements.
 * TODO	Refactor, remove static methods and move implementation into Core_Resources  
 * 
 * 
 */
class Resources
{
	/**
	 * 
	 */	
	const JS_HEADER  = 'header';
	
	/**
	 * 
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
	 * 
	 */
    protected $_metas = array();
    
	/**
	 * 
	 */
    protected $_styles = array();
    
	/**
	 * 
	 */
    protected $_snippets = array();
    
	/**
	 * 
	 */
    protected $_scripts = array('header'=>array(), 'footer'=>array(),'snippets' => array());
	
	
    private function __construct() 
    {
    	// A private constructor; prevents direct creation of object
    	$this->_configure( );
    	
    }
	
	/**
	 * Collect initial resources from config.
	 * 
	 * @private
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
	
    public static function instance() 
    {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }

        return self::$_instance;
    }
	
	/**
	 * 
	 */
	public static function get_metadata()
	{
		echo HTML::metadata( self::instance()->metas);  
	}
	
	/**
	 * 
	 */
	public static function get_styles()
	{
		echo HTML::styles( self::instance()->styles);
	}
	
	/**
	 * 
	 */
	public static function has_scripts($location)
	{
		return (count(self::instance()->scripts[$location]) > 0);
	}
	
	/**
	 * 
	 */
	public static function get_scripts($location)
	{
		if( ! self::instance()->has_scripts($location)) return '';
		
		if( $location === 'snippets') echo HTML::snippets(self::instance()->scripts[$location], NULL, TRUE);
		else echo HTML::scripts(self::instance()->scripts[$location], NULL, TRUE);
	}
	
	/**
	 * 
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
		 
	}
	
	/**
	 * 
	 * @param object $script
	 * @return 
	 */
	public function js($script, $location = 'header')
	{
		self::instance()->_scripts = Arr::merge( self::instance()->_scripts, array($location=>array($script)));
	}
	
	/**
	 * 
	 */
    public function css($href, $media = 'screen')
	{	
		/*if ( ! 'http://' == substr($href, 0, 7)   )
        { 
				$href = URL::site($href,TRUE);
		}*/
		
		self::instance()->_styles = array_merge( self::instance()->_styles, array($href => $media));
	}	
	
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