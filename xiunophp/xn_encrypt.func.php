<?php

// 获取配置文件中的 key，优先从扩展中获取（比较安全）。 
function xn_key($fromso = TRUE)
{
	$conf = _SERVER('conf');
	return isset($conf['auth_key']) ? $conf['auth_key'] : '';
}

// 安全的加密 key，过期时间 100 秒，如果最后 2 位 大于 90，则
// 临时使用，一般用作数据传输和校验
function xn_safe_key()
{
	global $conf, $longip;
	$conf = _SERVER('conf');
	$longip = _SERVER('longip');
	$key = xn_key();
	$behind = intval(substr($_SERVER['REQUEST_TIME'], -2, 2));
	$t = $behind > 80 ? $_SERVER['REQUEST_TIME'] - 20 : ($behind < 20 ? $_SERVER['REQUEST_TIME'] - 40 : $_SERVER['REQUEST_TIME']); // 修正范围，防止进位，有效时间窗口
	$front = substr($t, 0, -2);
	$key = md5($key . $_SERVER['HTTP_USER_AGENT'] . $front);
	return $key;
}

function xn_encrypt($txt, $key = '')
{
	empty($key) and $key = xn_key();
	$encrypt = xxtea_encrypt($txt, $key);
	return xn_urlencode(base64_encode($encrypt));
}

function xn_decrypt($txt, $key = '')
{
	empty($key) and $key = xn_key();
	$encrypt = base64_decode(xn_urldecode($txt));
	$ret = xxtea_decrypt($encrypt, $key);
	return $ret;
}

function xxtea_long2str($v, $w)
{
	$len = count($v);
	$n = ($len - 1) << 2;
	if ($w) {
		$m = $v[$len - 1];
		if (($m < $n - 3) || ($m > $n)) return FALSE;
		$n = $m;
	}
	$s = array();
	for ($i = 0; $i < $len; $i++) {
		$s[$i] = pack("V", $v[$i]);
	}
	if ($w) {
		return substr(join('', $s), 0, $n);
	} else {
		return join('', $s);
	}
}

function xxtea_str2long($s, $w)
{
	$v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
	$v = array_values($v);
	if ($w) {
		$v[count($v)] = strlen($s);
	}
	return $v;
}

function xxtea_int32($n)
{
	while ($n >= 2147483648) $n -= 4294967296;
	while ($n <= -2147483649) $n += 4294967296;
	return (int)$n;
}

function xxtea_encrypt($str, $key)
{
	if ($str == '') return '';
	$v = xxtea_str2long($str, TRUE);
	$k = xxtea_str2long($key, FALSE);
	if (count($k) < 4) {
		for ($i = count($k); $i < 4; $i++) {
			$k[$i] = 0;
		}
	}
	$n = count($v) - 1;

	$z = $v[$n];
	$y = $v[0];
	$delta = 0x9E3779B9;
	$q = floor(6 + 52 / ($n + 1));
	$sum = 0;
	while (0 < $q--) {
		$sum = xxtea_int32($sum + $delta);
		$e = $sum >> 2 & 3;
		for ($p = 0; $p < $n; $p++) {
			$y = $v[$p + 1];
			$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$z = $v[$p] = xxtea_int32($v[$p] + $mx);
		}
		$y = $v[0];
		$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
		$z = $v[$n] = xxtea_int32($v[$n] + $mx);
	}
	return xxtea_long2str($v, FALSE);
}

function xxtea_decrypt($str, $key)
{
	if ($str == '') return '';
	$v = xxtea_str2long($str, FALSE);
	$k = xxtea_str2long($key, FALSE);
	if (count($k) < 4) {
		for ($i = count($k); $i < 4; $i++) {
			$k[$i] = 0;
		}
	}
	$n = count($v) - 1;

	$z = $v[$n];
	$y = $v[0];
	$delta = 0x9E3779B9;
	$q = floor(6 + 52 / ($n + 1));
	$sum = xxtea_int32($q * $delta);
	while ($sum != 0) {
		$e = $sum >> 2 & 3;
		for ($p = $n; $p > 0; $p--) {
			$z = $v[$p - 1];
			$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
			$y = $v[$p] = xxtea_int32($v[$p] - $mx);
		}
		$z = $v[$n];
		$mx = xxtea_int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ xxtea_int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
		$y = $v[0] = xxtea_int32($v[0] - $mx);
		$sum = xxtea_int32($sum - $delta);
	}
	return xxtea_long2str($v, TRUE);
}
