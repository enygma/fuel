<?php
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


Fuel\Core\Autoloader::add_classes(array(
	'Fuel\\Octane\\Tests'		=> __DIR__.'/classes/tests',
	'Fuel\\Octane\\TestCase'	=> __DIR__.'/classes/testcase.php',
));

/* End of file bootstrap.php */