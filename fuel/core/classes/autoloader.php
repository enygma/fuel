<?php

namespace Fuel\Core;

class Autoloader {
	
	/**
	 * @var	array	$classes	holds all the classes and paths
	 */
	protected static $classes = array();

	/**
	 * @var	array	Holds all the class aliases
	 */
	protected static $aliases = array();

	/**
	 * @var	array	Holds all the namespace paths
	 */
	protected static $namespaces = array();

	/**
	 * @var	array	Holds all the namespace aliases
	 */
	protected static $namespace_aliases = array();

	/**
	 * @var	array	The default path to look in if the class is not in a package
	 */
	protected static $default_path = null;

	/**
	 * @var	bool	whether to initialize a loaded class
	 */
	protected static $auto_initialize = null;

	/**
	 * Adds a package to the autoloader.  The prefix is the prefix for the
	 * package classes.
	 *
	 * @access	public
	 * @param	string	the class name prefix
	 * @param
	 * @return	void
	 */
	public static function add_prefix($prefix, $path)
	{
		static::$prefixes[$prefix] = $path;
	}

	/**
	 * Adds an array of packages to the autoloader
	 *
	 * @access	public
	 * @param	array	the packages
	 * @return	void
	 */
	public static function add_prefixes(array $prefixes)
	{
		static::$prefixes = array_merge(static::$prefixes, $prefixes);
	}

	/**
	 * Returns the prefix's path or false when it doesn't exist
	 *
	 * @param	string
	 * @return	array|bool
	 */
	public static function prefix_path($prefix)
	{
		if ( ! array_key_exists($prefix, static::$prefixes))
		{
			return false;
		}

		return static::$prefixes[$prefix];
	}

	/**
	 * Adds a namespace and path
	 *
	 * @access	public
	 * @param	string	the namespace
	 * @param	string	the path
	 * @return	void
	 */
	public static function add_namespace($namespace, $path)
	{
		static::$namespaces[$namespace] = $path;
	}

	/**
	 * Adds an array of namespaces
	 *
	 * @access	public
	 * @param	array	the namespaces
	 * @return	void
	 */
	public static function add_namespaces(array $namespaces, $prepend = false)
	{
		if ( ! $prepend)
		{
			static::$namespaces = array_merge(static::$namespaces, $namespaces);
		}
		else
		{
			static::$namespaces = $namespaces + static::$namespaces;
		}
	}

	/**
	 * Returns the namespace's path or false when it doesn't exist
	 *
	 * @param	string
	 * @return	array|bool
	 */
	public static function namespace_path($namespace)
	{
		if ( ! array_key_exists($namespace, static::$namespaces))
		{
			return false;
		}

		return static::$namespaces[$namespace];
	}

	/**
	 * Adds an alias for a class.
	 *
	 * @access	public
	 * @param	string	the alias
	 * @param	string	class name
	 * @return	void
	 */
	public static function add_alias($alias, $class)
	{
		static::$aliases[strtolower($alias)] = $class;
	}

	/**
	 * Adds an array of class aliases.
	 *
	 * @access	public
	 * @param	string	the alias
	 * @param	string	class name
	 * @return	void
	 */
	public static function add_aliases(array $aliases)
	{
		static::$aliases = array_merge(static::$aliases, array_change_key_case($aliases, CASE_LOWER));
	}

	/**
	 * Adds an alias for a namespace.
	 *
	 * @access	public
	 * @param	string	the alias
	 * @param	string	alias name
	 * @return	void
	 */
	public static function add_namespace_alias($alias, $namespace, $prepend = false)
	{
		$namespace = (array) $namespace;

		if (array_key_exists($alias, static::$namespace_aliases))
		{
			static::$namespace_aliases[$alias] = array_merge($namespace, static::$namespace_aliases[$alias]);
		}
		else
		{
			if ($prepend)
			{
				static::$namespace_aliases = array($alias => $namespace) + static::$namespace_aliases;
			}
			else
			{
				static::$namespace_aliases[$alias] = $namespace;
			}
		}
	}

	/**
	 * Adds an array of namespaces aliases.
	 *
	 * @access	public
	 * @param	array	the aliases
	 * @return	void
	 */
	public static function add_namespace_aliases(array $aliases, $prepend = false)
	{
		foreach ($aliases as $alias => $namespace)
		{
			$namespace = (array) $namespace;
			static::add_namespace_alias($alias, $namespace, $prepend);
		}
	}	
	/**
	 * Adds a class path
	 * 
	 * @param	string	$class	the class name
	 * @param	string	$path	the path to the class file
	 */
	public static function add_class($class, $path)
	{
		static::$classes[$class] = $path;
	}

	/**
	 * Adds multiple class paths
	 * 
	 * @param	array	$classes	the class names and paths
	 */
	public static function add_classes($classes)
	{
		foreach ($classes as $class => $path)
		{
			static::$classes[$class] = $path;
		}
	}

	/**
	 * Aliases a class to a namespace, the root by default
	 * 
	 * @param	string	$class		the class name
	 * @param	string	$namespace	the namespace to alias to
	 */
	public static function alias_to_namespace($class, $namespace = '')
	{
		$parts = explode('\\', $class);
		$root_class = $namespace.array_pop($parts);
		class_alias($class, $root_class);
	}

	/**
	 * Register's the autoloader to the SPL autoload stack.
	 *
	 * @return	void
	 */
	public static function register()
	{
		spl_autoload_register('\\Fuel\\Core\\Autoloader::load', true, true);
	}

	public static function load($class)
	{
		$class = ltrim($class, '\\');
		$namespaced = strpos($class, '\\') !== false;

		if (empty(static::$auto_initialize))
		{
			static::$auto_initialize = $class;
		}
		if (array_key_exists($class, static::$classes))
		{
			include str_replace('/', DS, static::$classes[$class]);
			static::_init_class($class);
			return true;
		}
		elseif ( ! $namespaced and array_key_exists($class_name = 'Fuel\\Core\\'.$class, static::$classes))
		{
			include str_replace('/', DS, static::$classes[$class_name]);
			static::alias_to_namespace($class_name);
			static::_init_class($class);
			return true;
		}
		elseif ( ! $namespaced)
		{
			$file_path = str_replace('_', DS, $class);
			$file_path = \Fuel::find_file('classes', $file_path);
			if ($file_path !== false)
			{
				require $file_path;
				static::_init_class($class);
				return true;
			}
		}
		else
		{
			// TODO: Implement the Namespace path autoload.
		}
		return false;
	}

	/**
	 * Checks to see if the given class has a static _init() method.  If so then
	 * it calls it.
	 *
	 * @param	string	the class name
	 */
	private static function _init_class($class)
	{
		if (static::$auto_initialize === $class)
		{
			static::$auto_initialize = null;
			if (is_callable($class.'::_init'))
			{
				call_user_func($class.'::_init');
			}
		}
	}
}