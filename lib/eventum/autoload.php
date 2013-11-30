<?php
/**
 * Autoload class for Eventum.
 *
 * @author Tarmo Lehtpuu <tarmo.lehtpuu@delfi.ee>
 * @author Tanel Suurhans <tanel.suurhans@delfi.ee>
 * @author Elan Ruusamäe <glen@delfi.ee>
 *
 * @package Eventum
 */
class Eventum_Autoload {

	private static $excludes = array('.', '..', '.svn', 'CVS');
	private static $classes;

	public static function autoload($className) {
		if (class_exists($className, false) || interface_exists($className, false)) {
			return true;
		}

		// Zend framework
		if (strpos($className, 'Zend') === 0){
			require_once str_replace('_', '/', $className) . '.php';
			return true;
		}

		// Smarty
		if ($className === 'Smarty') {
			require_once APP_SMARTY_PATH . '/Smarty.class.php';
			return true;
		}

		// SphinxClient
		if ($className === 'SphinxClient') {
			require_once 'sphinxapi.php';
			return true;
		}

		if (!is_array(self::$classes)) {
			self::$classes = array();
			self::scan(dirname(__FILE__));
		}

		$className = strtolower($className);
		if (array_key_exists($className, self::$classes)) {
			require_once self::$classes[$className];
			return true;
		}

		return false;
	}

	private static function scan($path) {

		$dh = opendir($path);
		if ($dh === false) {
			return;
		}

		while (($file = readdir($dh)) !== false) {
			// omit exclusions
			if (array_search($file, self::$excludes) !== false) {
				continue;
			}

			// exclude hidden paths
			if ($file[0] == '.') {
				continue;
			}

			// recurse
			if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
				self::scan($path . DIRECTORY_SEPARATOR . $file);
				continue;
			}

			// skip without .php extension
			if (substr($file, -4) != '.php') {
				continue;
			}
			$class = substr($file, 0, -4);

			// rewrite from class.CLASSNAME.php
			if (substr($class, 0, 6) === 'class.') {
				$class = strtolower(substr($class, 6));

				self::$classes[$class] = $path . DIRECTORY_SEPARATOR . $file;
			}
		}
		closedir($dh);
	}
}

if (function_exists('spl_autoload_register')) {
	spl_autoload_register(array('Eventum_Autoload', 'autoload'));
} else {
	function __autoload($className) {
		Eventum_Autoload::autoload($className);
	}
}
