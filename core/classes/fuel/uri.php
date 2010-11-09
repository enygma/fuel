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

class Fuel_URI {

	/**
	 * Returns the desired segment, or false if it does not exist.
	 *
	 * @access	public
	 * @param	int		The segment number
	 * @return	string
	 */
	public static function segment($segment)
	{
		if (isset(Request::active()->uri->segments[$segment - 1]))
		{
			return Request::active()->uri->segments[$segment - 1];
		}

		return false;
	}

	public static function string()
	{
		return Request::active()->uri;
	}

	/**
	 * @var	string	The URI string
	 */
	public $uri = '';

	/**
	 * @var	array	The URI segements
	 */
	public $segments = '';

	/**
	 * Contruct takes a URI or detects it if none is given and generates
	 * the segments.
	 *
	 * @access	public
	 * @param	string	The URI
	 * @return	void
	 */
	public function __construct($uri = NULL)
	{
		if ($uri === NULL)
		{
			$uri = URI::detect();
		}
		$this->uri = trim($uri, '/');
		$this->segments = explode('/', $this->uri);
	}
	
	public function __toString()
	{
		return $this->uri;
	}
}

/* End of file uri.php */