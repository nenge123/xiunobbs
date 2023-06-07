<?php
 namespace League\CommonMark; use League\CommonMark\Block\Element\Document; interface DocParserInterface { public function parse(string $input): Document; } 