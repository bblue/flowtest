<?php

namespace bblue\ruby\Traits;

trait StringHelper
{
    static public function endsWith(string $haystack, string $needle): string
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    static public function getClassNameFromFqcn(string $fqcn): string
    {
        return substr(strrchr($fqcn, '\\'), 1);
    }

    static public function startsWith(string $haystack, string $needle): string
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }
}