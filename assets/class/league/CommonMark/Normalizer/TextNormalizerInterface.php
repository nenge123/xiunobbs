<?php
 declare(strict_types=1); namespace League\CommonMark\Normalizer; interface TextNormalizerInterface { public function normalize(string $text, $context = null): string; } 