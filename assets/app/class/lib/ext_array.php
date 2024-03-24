<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * ArrayAccess方法
 */
namespace lib;
use Nenge\APP;
class ext_array  implements \ArrayAccess{
    use static_app;
    public string $key;
    public function __construct($key='')
    {
        if(!empty($key)):
            $this->key = $key;
        endif;
    }
    public function offsetSet($offset, $value): void
    {
        if(empty($this->key)):
            APP::app()->data[$offset] = $value;
        else:
            APP::app()->data[$this->key][$offset] = $value;
        endif;
    }
    public function offsetExists($offset): bool
    {
        if(!empty($this->key)):
            return isset(APP::app()->data[$offset]);
        else:
            return isset(APP::app()->data[$this->key][$offset]);
        endif;
    }
    public function offsetUnset($offset): void
    {
        if($this->offsetExists($offset)):
            if(empty($this->key)):
                unset(APP::app()->data[$offset]);
            else:
                unset(APP::app()->data[$this->key][$offset]);
            endif;
        endif;
    }
    public function offsetGet($offset): mixed
    {
        if($this->offsetExists($offset)):
            if(empty($this->key)):
                return APP::app()->data[$offset];
            else:
                return APP::app()->data[$this->key][$offset];
            endif;
        endif;
        return '';
    }

}