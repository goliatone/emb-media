<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 
 *
 * @package    	Media
 * @category	Controller
 * @author 		Emiliano Burgos <hello@goliatone.com>
 * @copyright  	(c) 20011 Emiliano Burgos
 * @license    	http://kohanaphp.com/license
 * 
 * ####TODO 
 * 
 * - Cache. DONE 
 * - Minify. DONE. 
 * - Move to module. DONE. 
 * - Recognize files that have php and have variables.(?) 
 * - Return a 404 status if not found. DONE 
 * - Move heavy load to Media helper: method to find file, method to set headers.
 */
class Controller_Media extends Controller
{
	/**
	 * @var Media
	 */
	public $media = NULL;
	
	/** 
	 * 
	 */
	protected $_compress = TRUE;
	
	/**
	 * @var Kohana_Config
	 */
	public $config;
	
	public function before()
	{
		$this->media  = Media::instance('default');
		$this->config = Kohana::$config->load('media');		
	}
	
	/**
	 * This method only get's called if there is no such
	 * file in the target directory. In that case, we move
	 * the file from it's original path to the serving directory. 
	 */
	public function action_serve()
	{
		
		$this->media->request($this->request);
		
		//REFACTOR Move to media heleper
		//given an url: http://example.com/media/css/base-0.0.2.css
		$file = $this->request->param('file'); //file css/base  
		$sep  = $this->request->param('sep');  //sep -
		$uid  = $this->request->param('uid');  //uid 0.0.2
		$ext  = $this->request->param('ext');  //ext css
		
		//GBugger::log("file $file sep $sep uid $uid ext $ext");
		$this->media->filename;
		Kohana::$log->add(Log::INFO, 'enter serve action');
		
						  //| REFACTOR Move to media heleper
		if (! $filepath = Kohana::find_file($this->config->source_dir, $file, $ext))
        {
			Kohana::$log->add(Log::ERROR, "File {$this->media->file_name()} could not we loaded.");
			//File not found. Send 404 status.
        	$this->response->status(404);		
			return;			           
        }
		
		GBugger::log("file path is $filepath");
        	
		Kohana::$log->add(Log::INFO, "file $file, sep $sep, uid $uid, ext $ext");
		
    	$this->media->set_file_path($filepath);
		
        // Send the file content as the response
		$content = file_get_contents($filepath);
		
		if($this->config->cache)
		{
			$this->_create_cache($filepath,$content);
		}           
		
		/*
		 * TODO Fix this, extend Request to have a internal method.
		 * We don't want debugtoolbar to attach content to our files!
		 */
		//Request::$is_ajax = TRUE;
		
		// Tell browsers to cache the file. Look into Chrome being ieish, and combo cache-conrol + content-length bump. 
		$cache_for = $this->config->cache_for;
		$this->request->headers('Expires', 		 $this->_format_GMT(time()+$cache_for));
		$this->request->headers('Last-Modified', $this->_format_GMT(filemtime($filepath)));
		$this->request->headers('Cache-Control', "max-age={$cache_for}, must-revalidate, public");
		
		// Send the file content as the response, and send some basic headers		
		$this->request->headers('Content-Type',   $this->mime);
		$this->request->headers('Content-Length', filesize($filepath));
		
		// then send along with response
		$this->response->body($content);
		
	}
	
	/**
	 * REFACTOR Move to media heleper
	 */
	private function _create_cache($filepath,$content)
	{
		// Save the contents to the public directory for future requests
		$public = $this->config->output_dir.DIRECTORY_SEPARATOR.$this->media->file_name();
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
			$this->media->compress($public);
		}
	}
	/**
	 * REFACTOR Move to media heleper
	 * @private
	 */
	private function _format_GMT($timestamp)
	{
		return gmdate('D, d M Y H:i:s',$timestamp).'GMT';
	}
	
	/**
	 * 
	 */
	public function action_find()
	{
		$file = $this->request->param('file');
		$sep  = $this->request->param('sep');
		$uid  = $this->request->param('uid');
		$ext  = $this->request->param('ext');
		
		$filepath = Kohana::find_file('views/themes', $file, $ext);
		$mime = File::mime($filepath);
		$this->response->body($this->config->output_dir.DIRECTORY_SEPARATOR.$file.$sep.$uid.'.'.$ext);
	}	
	
}