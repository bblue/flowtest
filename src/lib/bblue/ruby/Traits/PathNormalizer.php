<?php
namespace bblue\ruby\Traits;

trait PathNormalizer
{
    /**
     * Normalizes the directory path
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
}