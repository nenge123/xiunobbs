<?php
 namespace League\CommonMark\Input; interface MarkdownInputInterface { public function getContent(): string; public function getLines(): iterable; public function getLineCount(): int; } 