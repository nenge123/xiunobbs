<?php
 namespace League\CommonMark\Util; final class Xml { public static function escape($string) { return \str_replace(['&', '<', '>', '"'], ['&amp;', '&lt;', '&gt;', '&quot;'], $string); } } 