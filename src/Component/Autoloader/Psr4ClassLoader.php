<?php

namespace bblue\ruby\Component\Autoloader;

/** 
 * Modified example from https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 */
class Psr4ClassLoader
{
	/**
	 * An associative array where the key is a namespace prefix and the value
	 * is an array of base directories for classes in that namespace.
	 * 
	 * @var array
	 */
	protected $aPrefixes = array();

	/**
	 * String to store the default directory to use when namespace specific dirs have not been set
	 * 
	 * @var string
	 */
	protected $sDefaultDirPath;

	/**
	 * Cache of strings attempted to load. This is used 
	 * to not load a file several times
	 * 
	 * @var array
	 */
	protected $loadedPaths = array();

	/**
	 * Register loader with SPL autoloader stack
	 * 
	 * @return void
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'loadClass'));
	}
	
	/**
	 * Add a base directory for a namespace prefix.
	 * 
	 * @param string $sPrefix The namespace prefix
	 * @param string $sBaseDir A base directory for class files in the namespace
	 * @param bool $bPrepend If true, prepend the base directory to the stack instead of appending it; this causes it to be searched first rather than last
	 * @return void
	 */
	public function addNamespace($sPrefix, $sBaseDir, $bPrepend = false)
	{
		// normalize namespace prefix and suffix
		$sPrefix = $this->normalizeNamespace($sPrefix);

		// normalize the base directory
		$sBaseDir = $this->normalizeDirectoryPath($sBaseDir);

		// initialize the namespace prefix array
		if (isset($this->aPrefixes[$sPrefix]) === false) {
		    $this->aPrefixes[$sPrefix] = array();
		}
		
		// retain the base directory for the namespace prefix
		if ($bPrepend) {
			array_unshift($this->aPrefixes[$sPrefix], $sBaseDir);
		} else {
			array_push($this->aPrefixes[$sPrefix], $sBaseDir);
		}
	}
	
	/**
	 * Normalize the directory path
	 * 
	 * @param string $sDirPath
	 * @return string
	 */
	public function normalizeDirectoryPath($sDirPath)
	{
		// convert to correct slashes
		$sDirPath = str_replace((DIRECTORY_SEPARATOR == '/') ? '\\' : '/', DIRECTORY_SEPARATOR, $sDirPath);
		
		// remove extra slashes
		$sDirPath = preg_replace("/[\/]+|[\\\\]+/",DIRECTORY_SEPARATOR,$sDirPath);
		
		// add a trailing slash and return
	    return trim($sDirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	}
	
	public function normalizeNamespace($namespace)
	{
		// convert to correct slashes
		$namespace = str_replace('/', '\\', $namespace);
		
		// remove extra slashes
		$namespace = preg_replace("/[\\\\]+/",'\\',$namespace);
		
		// add a trailing slash and return
	    return trim($namespace, '\\') . '\\';
	}

	/**
	 * Define a default location when a specific namespace has not been set
	 * 
	 * @param string $sDefaultDir 
	 */
	public function setDefaultDirectory($sDefaultDir)
	{
	    $this->sDefaultDirPath = $this->normalizeDirectoryPath($sDefaultDir);
	}
	
	/**
	 * Loads the class file for a given class name.
	 * 
	 * @param string $sClass The fully-qualified class name.
	 * @return mixed The mapped file name on success, or boolean false on failure
	 */
	public function loadClass($class)
	{
		// the current namespace prefix
		$prefix = $class;

		// work backwards through the namespace names of the fully-qualified
		// class name to find a mapped file name
		while (false !== $pos = strrpos($prefix, '\\')) {

			// retain the trailing namespace separator in the prefix
			$prefix = substr($class, 0, $pos + 1);

			// the rest is the relative class name
			$relative_class = substr($class, $pos + 1);

			// try to load a mapped file for the prefix and relative class
			$mapped_file = $this->loadMappedFile($prefix, $relative_class);
			if ($mapped_file) {
				return $mapped_file;
			}

			// remove the trailing namespace separator for the next iteration
			// of strrpos()
			$prefix = rtrim($prefix, '\\');   
		}

		// never found a mapped file
		return false;
	}

	/**
	 * Load the mapped file for a namespace prefix and relative class.
	 * 
	 * @param string $prefix The namespace prefix.
	 * @param string $relative_class The relative class name.
	 * @return mixed Boolean false if no mapped file can be loaded, or the
	 * name of the mapped file that was loaded.
	 */
	protected function loadMappedFile($prefix, $relative_class)
	{
		// are there any base directories for this namespace prefix?
		if (isset($this->aPrefixes[$prefix]) === true) {
		    $namespaceDirectories = $this->aPrefixes[$prefix];
		} elseif (isset($this->sDefaultDirPath) === true) {
			// Try to find file via default directory
			$namespaceDirectories = array($this->sDefaultDirPath . DIRECTORY_SEPARATOR . $prefix);
		}

		// look through base directories for this namespace prefix
		foreach ($namespaceDirectories as $namespaceDirectory) {

			// replace the namespace prefix with the base directory,
			// replace namespace separators with directory separators
			// in the relative class name, append with .php
			$file = rtrim($this->normalizeDirectoryPath($namespaceDirectory
				 	. $relative_class), DIRECTORY_SEPARATOR)
				  	. '.php';

			// Make sure we have not already tried this path
			if(!in_array($file, $this->loadedPaths)) {
				$this->loadedPaths[] = $file;
			}
			
			// if the mapped file exists, require it
			if ($this->requireFile($file)) {
				// reset the loaded file array
				$this->loadedPaths = array();

				// yes, we're done
				return $file;
			}
		}

		// never found it
		return false;
	}

	/**
	 * If a file exists, require it from the file system.
	 * 
	 * @param string $file The file to require.
	 * @return bool True if the file exists, false if not.
	 */
	protected function requireFile($file)
	{
		if (file_exists($file)) {
			require $file;
			return true;
		}
		return false;
	}
}