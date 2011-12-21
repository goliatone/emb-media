<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Media helper. It takes the workload to check for updates.
 *
 * @package    	Media
 * @category	Core
 * @author 		Emiliano Burgos <hello@goliatone.com>
 * @copyright  	(c) 20011 Emiliano Burgos
 * @license    	http://kohanaphp.com/license
 */
abstract class Core_Media
{

	/**
	 * @var	Kohana_Config
	 */
	public $config = FALSE;
	
	/**
	 * @var string Mime type of content 
	 */
	public $mime;
	
	/**
	 * @var array
	 */
	protected $_comppression_extensions = array('js' => 'compress_yui','css' => 'compress_yui','jp' => 'smushit','png' => 'smushit');
	
	/**
	 * @var  Request  Request that created the controller
	 */
	protected $_request;
	
	/**
	 * @var string 
	 */
	protected $_file_path;
	
	/**
	 * 
	 *
	 * @var string example.com/{media/css/base}-0.0.2.css
	 */
	protected $_file;
	
	/**
	 * @var string example.com/media/css/base({-})0.0.2.css
	 */
	protected $_sep;
	
	/**
	 * @var string example.com/media/css/base-({0.0.2}).css
	 */
	protected $_uid;
	
	/**
	 * @var string example.com/media/css/base-0.0.2.{css}
	 */
	protected $_ext;
	
	/*
	 * @var array Paths to perfomr find_file.
	 */ 
	protected $_paths = array('views');
	
	
	/**
	 * 
	 */
	public static function factory($config=NULL)
	{
		return new Media($config);
	}
	
    /**
     * Loads up the configuration
     */
    public function __construct($config = NULL)
    {
    	if($config !== NULL) $this->config = $config;
		
		if(isset($this->config->source_dir))
		{
			if(is_array($this->config->source_dir)) $this->_paths = $this->config->source_dir;
			else $this->_paths = array($this->config->source_dir);
		} 
    }
    
	/**
	 * 
	 * @param Request	$request
	 * @return Media	
	 */
    public function request( $request )
	{
		$this->_request = $request;
		
		$this->_file = $this->_request->param('file');
		$this->_sep  = $this->_request->param('sep');
		$this->_uid  = $this->_request->param('uid');
		$this->_ext  = $this->_request->param('ext');
		
		return $this;
	}
    
	/**
	 * 
	 */
	public function find_file()
	{
		$this->_file_path = FALSE;
		
		foreach ($this->_paths as $dir)
		{
			if ($found_file = Kohana::find_file($dir, $this->_file, $this->_ext))
			{
				// A path has been found
				$this->_file_path = $found_file;

				// Stop searching
				break;
			}
		}
		
		return $this->_file_path;	
		
		//$this->_file_path = Kohana::find_file($this->config->source_dir, $this->_file, $this->_ext);
		//return $this->_file_path;
	}
	
	/**
	 * 
	 */
	public function get_file()
	{
		$content = file_get_contents($this->_file_path);
		
		if($this->config->cache)
		{
			$this->_create_cache($this->_file_path,$content);
		} 
		
		return $content;   
	}
	
	/**
	 * 
	 * @return string	Original filename.
	 */
	public function file_name()
	{
		return $this->file.$this->sep.$this->uid.'.'.$this->ext;
	}
	
	/**
	 * @param	string	$file_path	Media file path.
	 */
	public function set_file_path($file_path)
	{
		$this->_file_path = $file_path;
	}
	
	/**
	 * @param 	string 	$file_path
	 */
	public function compress($file_path = NULL)
	{
		 $file_path AND $this->_file_path = $file_path;
		
		$compression_method = $this->_comppression_extensions[$this->_ext];
		
		$this->{$compression_method}($this->_file_path);
		Kohana::$log->add(Log::INFO, "Comressing file: $file_path");
	}
	
	/**
	 * Compresses JS and CSS files using the YUI compressor
	 *
	 * @return void
	 * @author Jonathan Geiger
	 **/
	public function compress_yui($file)
	{
		if (empty($this->config->yui))
		{
			return;
		}

		// Determine the executable and jar file
		$java = $this->config->yui['java'];
		$jar  = escapeshellarg($this->config->yui['jar']);
		$args = ($this->config->yui['options']) ? escapeshellarg($this->config->yui['options']) : '';
		$file = escapeshellarg($file);

		// -o sets the output file to the same as the input file
		$command = $java.' -jar '.$jar.' '.$args.' -o '.$file.' '.$file;

		// Execute the command in the background to save us from JAVA. I haven't
		// actually tested this on windows.
		if (substr(php_uname(), 0, 7) == 'Windows')
		{
			pclose(popen('start /B '.$command, 'r'));
		}
		else
		{
			exec($command.' > /dev/null &');
		}
	}
	
	/**
	 * 
	 */
	public function smushit($file)
	{
        include Kohana::find_file($this->config->smushit['vendor'],$this->config->smushit['file_path']);
		
		try
		{
			$img = new SmushIt($file);
			Kohana::$log->add(Log::INFO, 'Image Smushed: '.$img->filename.' was reduced '.$img->savings.'%');
		
			$this->write(file_get_contents($img->compressedUrl));
		}	
		catch(ErrorException $e)
		{
			
		}	
		
	}

    
    /**
     * Write contents to cache filename
     *
     * @param   string  $content
     */
    private function write($content = NULL)
    {
        if ($fh = fopen($this->_file_path, 'w'))
        {
            fwrite($fh, $content);
            fclose($fh);
            
            return $content;
        }
    }
	
	/**
	 * 
	 *
	 */
	private function _create_cache($filepath,$content)
	{
		// Save the contents to the public directory for future requests
		$public = $this->config->output_dir.DIRECTORY_SEPARATOR.$this->file_name();
		$directory = dirname($public);
		
		if ( ! is_dir($directory))
		{
			// Recursively create the directories needed for the file
			mkdir($directory.'/', 0777, TRUE);
		}
		
		// Store file mime type
    	$this->mime = File::mime($filepath);
		
		file_put_contents($public, $content);
		
		if($this->config->compress)
		{
			$this->compress($public);
		}
	}
	
	/**
	 * @private
	 */
	public function format_GMT($timestamp)
	{
		return gmdate('D, d M Y H:i:s',$timestamp).'GMT';
	}
	
	/**
	 * PHP magic method.
	 */
	public function __get($value)
	{
		if(isset($this->{'_'.$value})) $value = '_'.$value;
		return $this->$value;
	}
}