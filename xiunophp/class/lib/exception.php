<?php
/**
 * 能哥网
 * @link https://nenge.net
 * array
 * @var \Exception $exception
 */
namespace lib;
use MyApp;
class throwException
{
	public function __construct($exception)
	{

		@ob_clean();
		$message = $exception->getMessage();
		$message_code = $exception->getCode();
		$error_name = get_class($exception);
		$error_file = $exception->getFile();
		$error_line = $exception->getLine();
		$parseMessage = '';
		$message = MyApp::convert_safe_path($message);
		if (defined('DEBUG')):
			$trace = $exception->getTrace();
			if (!empty($trace)):
				$file = '';
				$traceCount = count($trace);
				$traceMessage = '';
				foreach ($trace as $index => $list):
					if (isset($list['file'])):
						$newfile = MyApp::convert_safe_path($list['file']);
						if ($file != $newfile):
							$traceMessage .=  '<li><h4 style="margin:.5em 0px">' . $newfile . '</h4><ul style="padding-left: 1.5em;">';
							$file = $newfile;
						endif;
					else:
						$file = '';
					endif;
					$traceMessage .=  '<li><code style="font-family: auto;">';
					if (!empty($list['class'])):
						$traceMessage .=  '<b>' . $list['class'] . '</b>' . (empty($list['type']) ? '::' : $list['type']);
					endif;
					if (!empty($list['function'])):
						$traceMessage .=  '<b>' . MyApp::convert_safe_path($list['function']) . '</b>';
						if (!empty($list['args'])):
							$traceMessage .=  '(' . implode(',', array_map(fn($m) => is_string($m) ? MyApp::convert_safe_path($m) : (is_object($m) ? get_class($m) : gettype($m)), $list['args'])) . ')';
						else:
							$traceMessage .=  '()';
						endif;
					endif;
					$traceMessage .=  '<b style="color:red">&nbsp;' . (empty($list['line']) ? '' : 'line:' . $list['line']) . '</b></code></li>';
					if (isset($list['file'])):
						if (empty($trace[$index + 1]['file']) || $file != MyApp::convert_safe_path($trace[$index + 1]['file'])):
							$traceMessage .=  '</ul></li>';
						endif;
					endif;
				endforeach;
			endif;
			$parseMessage = '<ul><li>' . MyApp::convert_safe_path($error_file) . '<sup>' . $error_line . '</sup></li></ul>';
			$includeList = get_included_files();
			if (!empty($includeList)):
				$includeTitle = '加载文件';
				$includeTitle .= '<sup>' . count($includeList) . '</sup>';
				$includeTitle .= '&nbsp;' . ceil(1000 * (microtime(1) - $_SERVER['REQUEST_TIME_FLOAT'])) . 'ms';
				$includeMessage = '';
				foreach ($includeList as $list):
					$includeMessage .= '<li><code style="font-family: auto;">' . MyApp::convert_safe_path($list) . '</code></li>';
				endforeach;
			endif;
		else:
			$message .= '<br>联系管理员!';
		endif;
?>
		<!DOCTYPE html>
		<html data-bs-theme="light">

		<head>
			<title><?= $error_name ?></title>
			<meta charset="utf-8" />
		</head>

		<body style="margin: 0px;background-color: #c5d6e5;padding: 1em;font-size: xx-small;">
			<h1>
				<code style="font-family: auto;"><?= $error_name ?></code>
				<sup style="color: red;"><?= $message_code ?></sup>
			</h1>
			<div style="background-color: #4096df;color:#fff;padding:1rem;">
				<pre><?= $message ?></pre>
			</div>
			<?php echo $parseMessage;
			if (isset($traceMessage)): ?>

				<details open>
					<summary style="font-size: large;margin-bottom: .5em;">回溯<b>(<?= $traceCount ?>)</b></summary>
					<ul style="color:blue;padding-left: 2em;"><?= $traceMessage ?></ul>
				</details>
			<?php endif;
			if (isset($includeMessage)): ?>
				<details>
					<summary style="font-size: large;margin-bottom: .5em;"><?= $includeTitle ?></summary>
					<ul style="color:blue;padding-left: 2em;"><?= $includeMessage ?></ul>
				</details><?php endif; ?>
		</body>

		</html>
<?php

	}
}
