<?php

/*
# 持久的 key value 数据存储
DROP TABLE IF EXISTS bbs_kv;
CREATE TABLE bbs_kv (
  k char(32) NOT NULL default '',
  v mediumtext NOT NULL,
  expiry int(11) unsigned NOT NULL default '0',		# 过期时间
  PRIMARY KEY(k)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
*/
class cache_file
{

	public $conf = array();
	public $db = NULL;
	public $link = NULL;
	public $table = 'cache';
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	public $savepath;
	public array $datas;
	public array $list = array('runtime');
	public function __construct()
	{
		$conf = $GLOBALS['conf'];
		$this->savepath = APP_PATH . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'data'.DIRECTORY_SEPARATOR;
		if (!is_dir($this->savepath)):
			mkdir($this->savepath, 0755, true);
		endif;
	}
	public function connect() {}
	public function set($k, $v, $life = 0)
	{
		$time = time();
		$expiry = $life ? $time + $life : 0;
		$arr = array(
			'name' => $k,
			'data' => $v,
			'expiry' => $expiry,
		);
		$this->datas[$k] = $arr;
		MyApp::write_data_file($k,$arr);
	}
	public function getdata($k)
	{
		$data = MyApp::get_data_file($k);
		if (empty($data)):
			$this->datas[$k] = false;
		else:
			$this->datas[$k] = $data;
		endif;
	}
	public function get($k)
	{
		$time = time();
		if (!isset($this->datas[$k])):
			$this->getdata($k);
		endif;
		if (!empty($this->datas[$k])):
			if ($this->datas[$k]['expiry'] && $time > $this->datas[$k]['expiry']):
				return NULL;
			endif;
			return $this->datas[$k]['data'];
		endif;
		return NULL;
	}
	public function delete($k)
	{
		MyApp::delete_data_file($k);
		if (!isset($this->datas[$k])):
			unset($this->datas[$k]);
		endif;
	}
	public function truncate()
	{
		MyApp::clear_data_file();
		$this->datas = array();
	}
}
