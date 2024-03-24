<?php

/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 */

namespace Nenge;
use ArrayAccess;
use lib\static_app;
class language implements ArrayAccess
{
    use static_app;
    public function offsetSet($offset, $value): void
    {
        APP::app()->language[$offset] = $value;
    }
    public function offsetExists($offset): bool
    {
        return isset(APP::app()->language[$offset]);
    }
    public function offsetUnset($offset): void
    {
        if($this->offsetExists($offset))unset(APP::app()->language[$offset]);
    }
    public function offsetGet($offset): mixed
    {
        return APP::app()->getlang($offset);
    }
}
