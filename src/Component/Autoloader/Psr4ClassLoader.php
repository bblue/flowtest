<?php

namespace bblue\ruby\Component\Autoloader;

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
		// normalize namespace prefix
		$sPrefix = trim($sPrefix, '\\') . '\\';
		
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
	    // normalize the base directory with a trailing separator
	    $sDirPath = str_replace((DIRECTORY_SEPARATOR == '/' ? '\\' : '/'), DIRECTORY_SEPARATOR, $sDirPath);
	    $sDirPath = rtrim($sDirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
	     
	    return $sDirPath;
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
		    $aBaseDirectories = $this->aPrefixes[$prefix];
		} elseif (isset($this->sDefaultDirPath) === true) { // Try to find file via default directory
			$aBaseDirectories = array($this->sDefaultDirPath . DIRECTORY_SEPARATOR . $prefix);
		}

		// look through base directories for this namespace prefix
		foreach ($aBaseDirectories as $base_dir) {

			// replace the namespace prefix with the base directory,
			// replace namespace separators with directory separators
			// in the relative class name, append with .php
			$file = $base_dir
				  . str_replace('\\', '/', $relative_class)
				  . '.php';

			// if the mapped file exists, require it
			if ($this->requireFile($file)) {
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