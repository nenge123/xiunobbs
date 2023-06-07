<?php
 namespace League\CommonMark; interface MarkdownConverterInterface { public function convertToHtml(string $markdown): string; } 