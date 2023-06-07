<?php
 namespace League\CommonMark\Reference; interface ReferenceInterface { public function getLabel(): string; public function getDestination(): string; public function getTitle(): string; } 