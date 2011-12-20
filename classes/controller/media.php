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
	 * @var Kohana_Config
	 */
	public $config;
	
	public function before()
	{
		$this->config = Kohana::$config->load('media');		
		$this->media  = Media::factory($this->config);
	}
	
	/**
	 * This method only get's called if there is no such
	 * file in the target directory. In that case, we move
	 * the file from it's original path to the serving directory. 
	 */
	public function action_serve()
	{
		
		$this->media->request($this->request);
		
		//GBugger::log("file $file sep $sep uid $uid ext $ext");
		
		if (! $filepath = $this->media->find_file())
        {
			Kohana::$log->add(Log::ERROR, "File {$this->media->file_name()} could not we loaded.");
			//File not found. Send 404 status.
        	throw new HTTP_Exception_404(':file could not be loaded.', array(':file' => $this->media->file_name()));		
			return;			           
        }
		
		//GBugger::log("file path is $filepath");
        	
        // Send the file content as the response
		$content = $this->media->get_file();
		
		/*
		 * TODO Fix this, extend Request to have a internal method.
		 * We don't want debugtoolbar to attach content to our files!
		 */
		//Request::$is_ajax = TRUE;
		
		//REVISION Should we move this into media helper?
		// Tell browsers to cache the file. 
		//Look into Chrome being ieish, and combo cache-conrol + content-length bump. 
		$cache_for = $this->config->cache_for;
		$this->request->headers('Expires', 		 $this->media->format_GMT(time()+$cache_for));
		$this->request->headers('Last-Modified', $this->media->format_GMT(filemtime($filepath)));
		$this->request->headers('Cache-Control', "max-age={$cache_for}, must-revalidate, public");
		
		// Send the file content as the response, and send some basic headers		
		$this->request->headers('Content-Type',   $this->media->mime);
		$this->request->headers('Content-Length', filesize($filepath));
		
		// then send along with response
		$this->response->body($content);
		
	}
	
	/**
	 * TEST METHOD
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