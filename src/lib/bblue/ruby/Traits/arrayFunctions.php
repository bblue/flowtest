<?php
namespace bblue\ruby\Traits;
trait ArrayFunctions
{
    public function array_flatten($array) {
    
        $return = array();
        foreach ($array as $key => $value) {
            if (is_array($value)){ $return = array_merge($return, $this->array_flatten($value));}
            else {$return[$key] = $value;}
        }
        return $return;
    
    }
}