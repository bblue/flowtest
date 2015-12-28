<?php
namespace bblue\ruby\Traits;

/**
 *       // a message with brace-delimited placeholder names
 *       $message = "User {username} created";
 *
 *       // a context array of placeholder names => replacement values
 *       $context = array('username' => 'bolivar');
 *
 *       // echoes "User bolivar created"
 *       echo interpolate($message, $context);
 */

trait Interpolate 
{    
    /**
     * Interpolates context values into the message placeholders.
     */
    function replacePlaceholders($message, array $context = array())
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }
    
        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }
}