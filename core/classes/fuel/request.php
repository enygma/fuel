<?php defined('COREPATH') or die('No direct script access.');
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Fuel Development Team
 * @license		MIT License
 * @copyright	2010 Dan Horrigan
 * @link		http://fuelphp.com
 */

class Fuel_Request {

	/**
	 * @var	object	Holds the global request instance
	 */
	public static $instance = false;
	
	/**
	 * @var	object	Holds the global request instance
	 */
	public static $active = false;

	/**
	 * Returns the a Request object singleton
	 *
	 * @static
	 * @access	public
	 * @return	object
	 */
	public static function instance($uri = NULL)
	{
		if ( ! Request::$instance)
		{
			Request::$instance = Request::$active = new Request($uri);
		}

		return Request::$instance;
	}

	/**
	 * Returns the active request
	 *
	 * @static
	 * @access	public
	 * @return	object
	 */
	public static function active()
	{
		return Request::$active;
	}

	/**
	 * Shows a 404.  Checks to see if a 404_override route is set, if not show a default 404.
	 *
	 * @access	public
	 * @return	void
	 */
	public static function show_404()
	{
		if (Config::get('routes.404') === false)
		{
			Request::active()->output = View::factory('404');
		}
		else
		{
			list($controller, $action) = array_pad(explode('/', Config::get('routes.404')), 2, false);

			$action or $action = 'index';

			$class = 'Controller_'.$controller;
			$method = 'action_'.$action;

			if (class_exists($class))
			{
				$controller = new $class(Request::active());
				if (method_exists($controller, $method))
				{
					// Call the before method if it exists
					if (method_exists($controller, 'before'))
					{
						$controller->before();
					}

					$controller->{$method}();

					// Call the after method if it exists
					if (method_exists($controller, 'after'))
					{
						$controller->after();
					}
					
					// Get the controller's output
					Request::active()->output =& $controller->output;
				}
				else
				{
					throw new Fuel_Exception('404 Action not found.');
				}
			}
			else
			{
				throw new Fuel_Exception('404 Controller not found.');
			}
		}
	}

	/**
	 * Generates a new request.  This is used for HMVC.
	 *
	 * @access	public
	 * @param	string	The URI of the request
	 * @return	object	The new request
	 */
	public static function factory($uri)
	{
		return new Request($uri);
	}

	/**
	 * @var	string	Holds the response of the request.
	 */
	public $output = NULL;

	/**
	 * @var	object	The request's URI
	 */
	public $uri = '';

	/**
	 * @var	string	The request's controller
	 */
	public $controller = '';

	/**
	 * @var	string	The request's action
	 */
	public $action = 'index';

	/**
	 * @var	string	The request's params
	 */
	public $params = array();


	public function __construct($uri)
	{
		$this->uri = Request::detect_uri($uri);
		$route = Route::parse($this->uri);

		$this->controller = $route['controller'];
		$this->action = $route['action'];
		$this->params = $route['params'];
		unset($route);
	}

	public static function detect_uri($uri = NULL)
	{
		// Nothing was provided (HMVC request)
		if ($uri === NULL)
		{
			// We want to use PATH_INFO if we can.
			if ( ! empty($_SERVER['PATH_INFO']))
			{
				$uri = $_SERVER['PATH_INFO'];
			}
			else
			{
				if (isset($_SERVER['REQUEST_URI']))
				{
					// Some servers require 'index.php?' as the index page
					// if we are using mod_rewrite or the server does not require
					// the question mark, then parse the url.
					if (Config::get('index_file') != 'index.php?')
					{
						$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
					}
					else
					{
						$uri = $_SERVER['REQUEST_URI'];
					}
				}
				else
				{
					throw new Fuel_Exception('Unable to detect the URI.');
				}

				// Remove the base URL from the URI
				$base_url = parse_url(Config::get('base_url'), PHP_URL_PATH);
				if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
				{
					$uri = substr($uri, strlen($base_url));
				}

				// If we are using an index file (not mod_rewrite) then remove it
				$index_file = Config::get('index_file');
				if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
				{
					$uri = substr($uri, strlen($index_file));
				}

				// Lets split the URI up in case it containes a ?.  This would
				// indecate the server requires 'index.php?' and that mod_rewrite
				// is not being used.
				preg_match('#(.*?)\?(.*)#i', $uri, $matches);

				// If there are matches then lets set set everything correctly
				if ( ! empty($matches))
				{
					$uri = $matches[1];
					$_SERVER['QUERY_STRING'] = $matches[2];
					parse_str($matches[2], $_GET);
				}
			}
		}

		// Do some final clean up of the uri
		$uri = str_replace(array('//', '../'), '/', trim($uri, '/'));

		return $uri;
	}

	public function execute()
	{
		$class = 'Controller_'.ucfirst($this->controller);
		$method = 'action_'.$this->action;

		try
		{
			if (class_exists($class))
			{
				$controller = new $class($this);
				if (method_exists($controller, $method))
				{
					// Call the before method if it exists
					if (method_exists($controller, 'before'))
					{
						$controller->before();
					}

					$controller->{$method}();

					// Call the after method if it exists
					if (method_exists($controller, 'after'))
					{
						$controller->after();
					}

					// Get the controller's output
					$this->output =& $controller->output;
				}
				else
				{
					throw new Fuel_Exception('Action not found.', 404);
				}
			}
			else
			{
				throw new Fuel_Exception('Controller not found.', 404);
			}
		}
		catch (Fuel_Exception $e)
		{
			switch ($e->getCode())
			{
				case 404:
					Request::show_404();
					break;

				default:
					Debug::dump($e);
			}
		}
		return $this;
	}

	/**
	 * PHP magic function returns the Output of the request.
	 *
	 * @access	public
	 * @return	string
	 */
	public function __toString()
	{
		return $this->output;
	}
}

/* End of file request.php */