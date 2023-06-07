<?php
 namespace League\CommonMark\Inline\Element; class Text extends AbstractStringContainer { public function append(string $character) { $this->content .= $character; } } 