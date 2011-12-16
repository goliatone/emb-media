<?php 
defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Media helper. It takes the workload to check for updates.
 */
abstract class Core_Media
{

    /**
     * @var     string  default instance name
     */
    public static $default = 'default';
    
    /**
     * @var     array   Media class instances
     */
    public static $instances = array();
    
    /**
     * @var     array   Headers array
     */
    public $headers = array();
    
	/**
	 * @var
	 */
	public $config = FALSE;
	
	/**
	 * @var  Request  Request that created the controller
	 */
	protected $_request;
	
	protected $_file_path;
	
	protected $_file;
	protected $_sep;
	protected $_uid;
	protected $_ext;
	
    /**
     * Returns a singleton instance of Media.
     *
     * @param   string  configuration group name
     * @return  object
     */
    public static function instance($name = NULL)
    {
        if ($name === NULL)
        {
            // Use the default instance name
            $name = Media::$default;
        }
        
        if (!isset(Media::$instances[$name]))
        {
            // Load the configuration data
            //$config = Kohana::config('media')->$name;
			//$config = Kohana::config('media'.$name);
            $config = Kohana::$config->load('media');
            
            
            // Set static instance name to array
            Media::$instances[$name] = new Media($config);
        }
        
        return Media::$instances[$name];
    }
    
    /**
     * Loads up the configuration
     */
    public function __construct($config = NULL)
    {
    	if($config !== NULL) $this->config = $config;
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
	 * 
	 */
	public function compress($file_path = NULL)
	{
		 $file_path AND $this->_file_path = $file_path;
		
		if ($this->_ext == 'js' OR $this->_ext == 'css')
		{
			Kohana::$log->add(Log::INFO, 'Comressing file: '.$file_path);
			$this->compress_yui($this->_file_path);
						
		}
		else if( $this->_ext == 'jpg' OR $this->_ext == 'png')
		{
			Kohana::$log->add(Log::INFO, 'Comressing image file: '.$file_path);
			$this->smushit($this->_file_path);
		}
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
     * Gzip compression of the file
     */
    public function gzip()
    {
        if (Request::accept_encoding('gzip'))
        {
            $this->filename = $this->filename.'_gzip';
            
            $this->headers['Content-Encoding'] = 'gzip';
            
            if (!$this->is_cached($this->filename))
            {
                $this->content = $this->write(gzencode($this->content));
            }
        }
        
        return $this;
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

	public function __get($value)
	{
		if(isset($this->{'_'.$value})) $value = '_'.$value;
		return $this->$value;
	}
}