<?php

namespace model;

use MyApp;

class adminupload
{

	public int $fid;
	public string $path;
	public string $site;
	public function __construct($fid)
	{
		$this->fid = intval($fid);
		$this->upload_by_action();
	}

	public function upload_by_action()
	{
		$action = MyApp::head('content-action');
		$this->path = MyApp::path('upload/forum/');
		$this->site = MyApp::site('upload/forum/');
		MyApp::create_dir($this->path);
		switch ($action):
			case 'attach/list':
				$this->upload_list();
				break;
			case 'attach/upload':
				if (!empty($_FILES['file']['error'])):
					$this->upload_error($_FILES['file']['error'], MyApp::head('content-length', $_FILES['file']['size']));
				endif;
				if (!empty($_FILES['file']['tmp_name'])):
						switch ($_FILES['file']['type']):
							case 'image/png':
							case 'image/jpg':
							case 'image/jpeg':
							case 'image/webp':
							case 'image/gif':
							case 'image/apng':
							case 'image/avif':
								$imageinfo = @getimagesize($_FILES['file']['tmp_name']);
								if (!empty($imageinfo)):
									$ext = explode('/', $imageinfo['mime']);
									$extension = array_pop($ext);
									if ($extension == 'jpeg'):
										$extension = 'jpg';
									endif;
									$this->upload_file($extension);
								endif;
								break;
							case 'application/zip':
							case 'application/x-zip-compressed':
							case 'application/rar':
							case 'application/7z':
							case 'application/x-rar-compressed':
							case 'application/x-7z-compressed':
								$fp = fopen($_FILES['file']['tmp_name'], 'rb');
								$byte = bin2hex(fread($fp, 4));
								fclose($fp);
								$extension = match ($byte) {
									'504b0304' => 'zip',
									'52617221' => 'rar',
									'377abcaf' => '7z',
									'52494646' => 'webp',
									default => false
								};
								if ($extension) {
									$this->upload_file($extension);
								}
								break;
							default:
								if (str_starts_with($_FILES['file']['type'], 'video')):
									$this->upload_video();
								endif;
								break;
						endswitch;
						$this->message(array('message' => '上传失败!'));
				endif;
				break;
			case 'attach/delete':
				$filename = basename(MyApp::post('name'));
				if (is_file($this->path . $filename)):
					@unlink($this->path . $filename);
				endif;
				$this->upload_list();
				break;
			case 'attach/big':
				if (!empty($_FILES['file']['error'])):
					$this->upload_error($_FILES['file']['error'], MyApp::head('content-length', $_FILES['file']['size']));
				endif;
				if (!empty($_FILES['file']['tmp_name'])):
					$this->upload_big();
				endif;
				break;
			case 'attach/convert':
				$filename = basename(MyApp::post('name'));
				$filepath = $this->path . $filename;
				if (is_file($filepath . '.mp4')):
					$this->message(array('message' => '文件已转换!'));
				else:
					if (is_file($filepath . '.lock')):
						$this->message(array('message' => '后台转换中!'));
					elseif (is_file($filepath . '.video')):
						$ffmpegpath = APP_PATH .'ffmpeg/ffmpeg.php';
						if (is_file($ffmpegpath)):
							include($ffmpegpath);
							(new \myffmpeg())->save($filepath . '.video', $filepath . '.mp4');
							$this->message(array('url' => $myapp->convert_site($filepath . '.mp4')));
						endif;
					else:
						$this->message(array('message' => '转换的文件不存在!'));
					endif;
				endif;
				break;
		endswitch;
	}
	public function upload_file(string $extension)
	{
		
		$filename = $this->fid . '-' . date('YmdHis-') . rand(1000, 9999) . '.' . $extension;
		if (move_uploaded_file($_FILES['file']['tmp_name'], $this->path . $filename)):
			$this->message(array('url' => $this->site . $filename));
		endif;
	}
	public function upload_video(?string $filepath = null)
	{
		
		$ffmpegpath = APP_PATH .'ffmpeg/ffmpeg.php';
		if (is_file($ffmpegpath)):
			include($ffmpegpath);
			if (empty($filepath)):
				$filepath = $_FILES['file']['tmp_name'];
			endif;
			if (class_exists('myffmpeg', false)):
				$myffmpeg = new \myffmpeg();
				$extstr = $myffmpeg->extinfo($filepath);
				if (!empty($extstr)):
					$filename = 'video-' . date('YmdHis') . rand(1000, 9999) . '.';
					$savepath = $this->path . $filename;
					$myffmpeg->gif($savepath . 'webp');
					if (str_contains($extstr, 'mp4')):
						$extname = 'mp4';
					elseif (str_contains($extstr, 'webm')):
						$extname = 'webm';
					else:
						$extname = 'video';

					endif;
					if (is_uploaded_file($filepath)):
						move_uploaded_file($filepath, $savepath . $extname);
					else:
						rename($filepath, $savepath . $extname);
					endif;
					$this->message(array(
						'url' => $this->site . $filename . 'webp',
						'video' => $this->site . $filename . $extname,
					));
				endif;
			endif;
		endif;
	}
	public function upload_big()
	{
		
		$filename = MyApp::head('content-name');
		$filesize = intval(MyApp::head('content-size'));
		$filepos = intval(MyApp::head('content-pos'));
		$md5 = MyApp::head('content-md5');
		$filemime = $_FILES['file']['type'];
		if (empty($filename)):
			$filename = '#' . date('YmdHis') . rand(1000, 9999);
			$fp = fopen($this->path . $filename . '.big', 'a');
		elseif (is_file($this->path . $filename . '.big')):
			$fp = fopen($this->path . $filename . '.big', 'a');
		else:
			$this->message(array('message' => '文件不存在'));
		endif;
		//fseek($fp, $filepos);
		fwrite($fp, file_get_contents($_FILES['file']['tmp_name']));
		fclose($fp);
		$nowmd5 = md5_file($this->path . $filename . '.big');
		if ($filesize < filesize($this->path . $filename . '.big')):
			@unlink($this->path . $filename . '.big');
			$this->message(array('message' => '异常错误'));
		endif;
		if ($nowmd5 != $md5):
			$this->message(
				array(
					'pos' => $filepos + $_FILES['file']['size'],
					'md5' => $nowmd5,
					'filename' => $filename
				)
			);
		endif;
		if (str_starts_with($filemime, 'video')):
			$this->upload_video($this->path . $filename . '.big');
		else:
			$fp = fopen($_FILES['file']['tmp_name'], 'rb');
			$byte = bin2hex(fread($fp, 4));
			fclose($fp);
			$extension = match ($byte) {
				'504b0304' => 'zip',
				'52617221' => 'rar',
				'377abcaf' => '7z',
				'52494646' => 'webp',
				default => false
			};
			if ($extension):
				$filename = $this->fid . '-' . date('YmdHis-') . rand(1000, 9999) . '.' . $extension;
				rename(
					$this->path . $filename . '.big',
					$this->path . $filename
				);
				$this->message(array('url' => $this->site . $filename));
			endif;
		endif;
		@unlink($this->path . $filename . '.big');
		$this->message(array('message' => '不是压缩文件或者视频文件'));
	}
	public function upload_list()
	{
		$files = MyApp::scanDIR($this->path);
		$files = array_filter($files, fn($m) => !str_ends_with($m, '.big'));
		$this->message(array('data' => array_map(fn($m) => $this->site . $m, $files)));
	}
	public function upload_error($error, $filesize): void
	{
		
		switch ($error):
			case UPLOAD_ERR_INI_SIZE:
				$message = sprintf('当前文件大小:%.2f MB超出服务器上限!', $filesize / 1024 / 1024);
				$this->message(array('message' => $message));
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$this->message(array('message' => '上传文件的大小超过了 HTML 表单中最大值.'));
				break;
			case UPLOAD_ERR_PARTIAL:
				$this->message(array('message' => '文件只有部分被上传.'));
				break;
			case UPLOAD_ERR_NO_FILE:
				$this->message(array('message' => '没有文件被上传.'));
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->message(array('message' => '找不到文件夹.'));
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$this->message(array('message' => '文件写入失败.'));
			case UPLOAD_ERR_EXTENSION:
				$this->message(array('message' => '未知文件类型.'));
				break;
		endswitch;
	}
	public function message($json)
	{
		@ob_clean();
		header('content-type:application/json;charset=' .MyApp::conf('charset','utf-8'));
		echo xn_json_encode($json);
		exit;
	}
}
