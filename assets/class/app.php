<?php

namespace Nenge;

use Nenge\DB;
use Nenge\template;

class APP implements \ArrayAccess
{
    public static object $_app;
    public array $conf;
    public array $plugin = array();
    public static array $plugin_class = array();
    public array $language = array();
    public array $data = array();
    public array $settings = array();
    public function __construct($conflink = '')
    {
        self::$_app = $this;
        $this->settings['root'] =  dirname(__DIR__, 2) . "\\";
        $this->settings['classpath'] =  __DIR__ . "\\";
        #初始化 配置
        #注册 类自动加载
        spl_autoload_register(array($this, 'register_autoload'), true, true);
        if (empty($conflink)) $conflink = $this->settings['root'] . 'config.inc.php';
        if (is_file($conflink)) {
            $this->conf = (array)include($conflink);
            DB::app($this->conf);
            $this->init_settings();
            $this->init_variable();
        }
        $this->plugin_method_call('common',fn($method)=>call_user_func($method));
    }
    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
    public function offsetGet($offset): mixed
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : '';
    }
    public static function app()
    {
        if (empty(self::$_app)) new APP();
        return self::$_app;
    }
    public static function ip_validate($ip)
    {
        return \filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    public static function ip2long($ip = "")
    {
        $hex = self::ip2hex($ip);
        if (function_exists('gmp_init')) {
            return gmp_strval(gmp_init($hex, 16), 10);
        } elseif (function_exists('bcadd')) {
            return self::hex2num($hex);
        } else {
            trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
        }
    }
    public static function long2ip($dec)
    {
        if (function_exists('gmp_init')) {
            $hex = gmp_strval(gmp_init($dec, 10), 16);
            $hex = str_pad($hex, strlen($hex) > 8 ? 32 : 8, '0', STR_PAD_LEFT);
        } elseif (function_exists('bcadd')) {
            $hex = self::num2hex($dec);
        } else {
            trigger_error('GMP or BCMATH extension not installed!', E_USER_ERROR);
        }
        return self::hex2ip($hex);
    }
    public static function hex2ip($hex)
    {
        $len = strlen($hex);
        $arr = str_split($hex, $len == 8 ? 2 : 4);
        if ($len == 8) $arr = array_map(fn ($v) => $v ? base_convert($v, 16, 10) : 0, $arr);
        $ip = implode($len == 8 ? '.' : ':', $arr);
        return $len == 8 ? $ip : inet_ntop(inet_pton($ip));
    }
    public static function ip2hex($ip)
    {
        return bin2hex(inet_pton($ip));
    }
    public static function hex2num($str, $split = 4)
    {
        if (empty($str)) return 0;
        $snum = strlen($str);
        if ($split > $snum) $split = $snum;
        $arr = array_map(fn ($v) => $v ? base_convert($v, 16, 10) : 0, str_split($str, $split));
        $num = 0;
        $len = count($arr) - 1;
        foreach ($arr as $k => $v) {
            $num = bcadd($num, $v ? bcmul($v, $len ? bcpow(16, ($len) * $split) : 1) : 0);
            $len--;
        }
        return $num;
    }
    public static function num2hex($num, $isip = false)
    {
        $snum = strlen($num);
        if ($snum < 2) $split = 1;
        else if ($snum < 3) $split = 2;
        else $split = 4;
        $arr = [1];
        for ($i = 1; $i != 0; $i++) {
            $p = bcpow(16, $i * $split);
            if (bccomp($p, $num) == 1) break;
            $arr[$i] = $p;
        }
        $hex = '';
        if (!empty($arr)) {
            $arr = array_reverse($arr);
            foreach ($arr as $k => $v) {
                $arr[$k] = bcdiv($num, $v, 0);
                $num = bcsub($num, bcmul($v, $arr[$k]));
            }
            $hex = implode('', array_map(fn ($v) => str_pad(base_convert($v, 10, 16), $split, '0', STR_PAD_LEFT), $arr));
        }

        if ($isip) {
            $iplen = strlen($hex);
            $arr = str_split($hex, $iplen == 8 ? 2 : 4);
            if ($iplen == 8) $arr = array_map(fn ($v) => $v ? base_convert($v, 16, 10) : 0, $arr);
            $hex = implode($iplen == 8 ? '.' : ':', $arr);
            return $iplen == 8 ? $hex : inet_ntop(inet_pton($hex));
        }
        return $hex;
    }
    public static function output_debug()
    {
        $myapp = self::app();
        if (empty($myapp->conf['debug'])) return '';
        $str = '<div class="debug"><div class="container"><div class="card"><div class="card-body">';
        $sqldata = DB::getSql();
        if (!empty($sqldata)) {
            $str .= '<h5>[SQL]</h5><ul>' . PHP_EOL;
            foreach ($sqldata as $v) {
                $str .= '<li>' . $v['time'] . 'ms ' . $v['sql'] . '</li>' . PHP_EOL;
            }
            $str .= '</ul>';
        }
        $include = get_included_files();
        $str .= '<h5>' . $myapp->language['included_files'] . '('.count($include).')</h5><ul>';
        foreach ($include as $v) {
            $str .= '<li>' . str_replace(dirname(__DIR__, 2) . '\\', '', $v) . '</li>' . PHP_EOL;
        }
        $str .= '</ul></div></div></div></div>';
        return $str;
    }
    public static function json($json): never
    {
        header('Content-type: application/json');
        self::exit(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    public function getCookies($name)
    {
        if (!empty($this->data['cookies'][$name])) {
            return $this->data['cookies'][$name];
        }
        return null;
    }
    public function setCookies($name, $data = "", $time = -1)
    {
        $this->data['cookies'][$name] = $data;
        if(!empty($data)&&$time==-1)$time = $this->data['time']+2592000;
        setcookie(
            $this->conf['cookie_prefix'] . $name,
            $data,
            ($time > $this->data['time'] || $time <= 0) ? $time : $time + $this->data['time'],
            $this->conf['cookie_path'],
            $this->conf['cookie_domain'],
            $_SERVER['HTTPS']!='off',
            true
        );
    }
    public function register_autoload($class)
    {
        $arr = explode('\\', $class);
        if(!empty($arr[1])&&$arr[0]==$arr[1]&&is_file($this->settings['classpath'] . $arr[0] . '\\' . $arr[0] . '.php')) {
            include_once($this->settings['classpath'] . $arr[0] . '\\' . $arr[0] . '.php');
        }else if($arr[0]=='plugin'&&is_file($this->settings['root'].$class.'.class.php')){
            include_once($this->settings['root'].$class.'.class.php');
        }else if (is_file($this->settings['classpath'] . $class . '.php')) {
            include_once($this->settings['classpath'] . $class . '.php');
        } else {
            throw $class . ' is lost!';
        }
    }
    public static function mkdir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    public function rmdir($src)
    {
        if (is_dir($src)) {
            $dirs = scandir($src);
            foreach($dirs as $link){
                if($link=='.'||$link=='..')continue;
                $full = $src. $link;
                if (is_dir($full.'\\')) {
                    $this->rmdir($full.'\\');
                } else {
                    unlink($full);
                }

            }
            rmdir($src);
        }
    }
    public function rm_cache($path=false)
    {
        if(empty($path))$path = $this->data['path']['cache'];
        $cache_dirs = scandir($path);
        foreach($cache_dirs as $dir){
            if($dir=='.'||$dir=='..')continue;
            $full = $path. $dir;
            if(is_file($full)){
                unlink($full);
            }elseif(is_dir($full)){
                $this->rm_cache($full.'\\');
            }
        }
    }
    public function exit_clear()
    {
        if (!empty($_FILES)) {
            $tmp = $this->data['path']['upload'] . 'tmp/';
            foreach ($_FILES as $k => $v) {
                $file = $v['tmp_name'];
                if (!empty($file)) {
                    if (is_array($file)) {
                        foreach ($file as $a => $b) {
                            if (is_file($b) && is_uploaded_file($b)) {
                                move_uploaded_file($b, $tmp . basename($b));
                                unlink($tmp . basename($b));
                            }
                        }
                    } elseif (is_file($file) && is_uploaded_file($file)) {
                        move_uploaded_file($file, $tmp . basename($file));
                        unlink($tmp . basename($file));
                    }
                }
            }
        }
    }
    public function str_encrypt($txt)
    {
        return openssl_encrypt($txt, $this->conf['encrypt_method'], $this->conf['encrypt_key'], OPENSSL_RAW_DATA, hex2bin(md5($this->conf['encrypt_key'])));
    }
    public function str_decrypt($txt)
    {
        return openssl_decrypt($txt, $this->conf['encrypt_method'], $this->conf['encrypt_key'], OPENSSL_RAW_DATA, hex2bin(md5($this->conf['encrypt_key'])));
    }
    public static function str_hash($str, $algo = PASSWORD_DEFAULT)
    {
        return password_hash($str, $algo);
    }
    public static function str_verify($str, $hash)
    {
        return password_verify($str, $hash);
    }
    public function str_path($router, $type = 'template')
    {
        $routerlist = explode(':', $router);
        $name = array_pop($routerlist);
        $plugin = array_pop($routerlist);
        if (empty($plugin)) {
            if (!empty($this->plugin[$type][$name])) {
                $plugin = $this->plugin[$type][$name];
            } else if ($type == 'template' && !empty($this->data['style_name'])) {
                $plugin = $this->data['style_name'];
            }
        }
        if (!empty($plugin)) {
            $path = $this->data['path']['plugin'] . $plugin . '\\' . $type . '\\';
        } else {
            $path = $this->data['path'][$type];
        }
        $cachename = preg_replace('/(\/|\:|\\\|\s)/', '_', $router);
        if ($type == 'template') {
            $cachename .= '.php';
        } elseif ($type == 'css') {
            $cachename .='.css';
            $name .= '.scss';
            $path .= $name;
        } elseif ($type == 'router') {
            $cachename .= '.php';
            $path .= $name . '.inc.php';
        }
        return array(
            $path,
            $this->data['path']['_' . $type] . $cachename,
            $this->data['site']['_' . $type] . $cachename,
            $name
        );
    }
    public function init_settings()
    {
        $path = dirname(__DIR__, 2) . "/cache/data/settings.php";
        if (is_file($path)) $this->data = (array)include($path);
        if (empty($this->data)) $this->write_settings();
    }
    public function init_router_var()
    {
        $router = array();
        $script_query = urldecode($_SERVER['QUERY_STRING']);
        if (count($_GET) > 1) {
            $firstkey = strstr($script_query, '&', !0);
            $this->data['query'] = substr($script_query, strlen($firstkey) + 1);
            parse_str($this->data['query'], $get);
        } else {
            $firstkey = $script_query;
            $this->data['query'] = '';
        }
        $router_key = preg_replace('/\.(php|htm|html)$/', '', trim($firstkey, '\//-'));
        if (is_numeric($router_key)) {
            $router = array(
                0 => 'user',
                1 => (int)$router_key,
                'user' => (int)$router_key
            );
        } elseif (!empty($router_key)) {
            $router_key = trim(preg_replace('/(\/|\\\)/', '-', $router_key), '-');
            if (!empty($this->data['settings']['router_replace'])) {
                $router_key = strtr($router_key, array_flip($this->data['settings']['router_replace']));
            }
            $router_arr = explode('-', $router_key);
            foreach ($router_arr as $k => $v) {
                $router[] = $v;
            }
            if(!empty($router[0])&&!is_numeric($router[0])){
                $router[$router[0]] = isset($router[1])?$router[1]:'';
            }
        }
        return $router;
    }
    public function init_variable()
    {
        ini_set('session.name', $this->conf['cookie_prefix'] . 'sid');
        ini_set('session.sid_length', '32');
        ini_set('session.use_cookies', 'On');
        ini_set('session.use_only_cookies', 'On');
        ini_set('session.use_strict_mode', 'On');
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_secure', 'On');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.session.sid_bits_per_character', 5);
        ini_set('session.cookie_domain', $this->conf['cookie_domain']);
        ini_set('session.cookie_path', $this->conf['cookie_path']);
        ini_set('session.cookie_httponly', 'On');
        set_error_handler(array($this, 'error_handler'));
        set_exception_handler(array($this, 'error_exception_handler'));
        $get = array();
        $router = array();
        $get_first = array_key_first($_GET);
        if (!empty($get_first) && empty($_GET[$get_first])) {
            $router = $this->init_router_var();
        } else {
            $get = $_GET;
        }
        if (empty($router[0])) {
            $router = array(
                0 => 'index',
                1 => 1,
                'index' => 1
            );
        }
        $onlineip = $_SERVER['REMOTE_ADDR'];
        $this->data += array(
            'GET' => $get,
            'POST' => $_POST,
            'router' => $router,
            'time' => time(),
            'ip' => $onlineip,
            'longip' => $this->ip2long($onlineip),
            'title' => '',
            'keywords' => '',
            'description' => '',
            'footerjs'=>array(),
            'mobile' => empty($_SERVER['HTTP_SEC_CH_UA_MOBILE']) || $_SERVER['HTTP_SEC_CH_UA_MOBILE'] != '?0',
            'method'=>$_SERVER['REQUEST_METHOD'],
            'charset'=>empty($this->conf['charset'])?'utf-8':$this->conf['charset']
        );
        if (!is_file($this->data['path']['lang'] . $this->conf['lang'] . '.php')) {
            $this->conf['lang'] = 'zh-cn';
        }
        $this->language = (array)include($this->data['path']['lang'] . $this->conf['lang'] . '.php');
        if (is_file($this->data['path']['data'] . 'plugin.php')) {
            $this->plugin = (array)include($this->data['path']['data'] . 'plugin.php');
            if (!empty($this->data['settings']['style_name'])) {
                $style_name = $this->data['settings']['style_name'];
                if (!empty($this->plugin['list'][$style_name])) {
                    $this->data['style_name'] = $style_name;
                    $this->data['path']['styletemplate'] = $this->data['path']['plugin'] . '\\' . $style_name . '\template\\';
                    $this->data['path']['styleroot'] = $this->data['path']['plugin'] . '\\' . $style_name . '\\';
                    $this->data['site']['styleroot'] = $this->data['site']['plugin'] . '/' . $style_name . '/';
                }
            }
            if (!empty($this->plugin['list'])) {
                $langdirs = array_column($this->plugin['list'], 'dir_lang');
                if (!empty($langdirs)) {
                    if (!empty($this->conf['debug']) || !is_file($this->data['path']['data'] . 'plugin_lang.php')) {
                        $this->write_plugin_lang($langdirs, $this->conf['lang']);
                    } else {
                        $this->language += (array)include($this->data['path']['data'] . 'plugin_lang_'.str_replace('-','_',$this->conf['lang']).'.php');
                    }
                }
            }
        }
        $this->data['uid'] = 0;
        $this->data['gid'] = 0;
        if (!empty($this->data['settings']['timezone'])) {
            #设置时区
            ini_set('date.timezone', $this->data['settings']['timezone']);
        }
        if (!empty($_COOKIE)) {
            #读取专属cookies
            foreach ($_COOKIE as $k => $v) {
                if (strpos($k, $this->conf['cookie_prefix']) === 0) {
                    $this->data['cookies'][str_replace($this->conf['cookie_prefix'], '', $k)] = $v;
                }
            }
            if (!empty($this->data['cookies']['tokens'])) {
                parse_str($this->str_decrypt($this->data['cookies']['tokens']), $tokens);
                #print_r($tokens);
                if (!empty($tokens) && count($this->session_fields) == count($tokens)) {
                    #print_r($tokens);

                    $this->data['tokens'] = array_combine($this->session_fields, $tokens);
                    $this->data['user'] = $this->data['tokens'];
                } else if (!empty($this->data['cookies']['sid'])) {
                    session_start();
                    if (!empty($_SESSION['uid'])) {
                        $this->data['user'] = $_SESSION;
                    }
                }
                if (!empty($this->data['user']['uid'])) {
                    if ($this->data['time'] - $this->data['user']['login_date'] > $this->data['settings']['update_online']) {
                        $uparray = array(
                            'login_ip' => $this->data['longip'],
                            'login_date' => $this->data['time'],
                            //'+:logins' => 1
                        );
                        $this->data['user']['login_date'] = $this->data['time'];
                        //$this->data['user']['logins'] += 1;
                        DB::t('user')->update($uparray, array('uid' => $this->data['user']['uid']));
                        if (!empty($this->data['tokens'])) {
                            $this->session_tokens($this->data['user']);
                        }
                    }
                    $this->data['gid'] = $this->data['user']['gid']?:0;
                    $this->data['uid'] = $this->data['user']['uid']?:0;
                    $this->data['access'] = $this->data['grouplist'][$this->data['gid']];
                }
            }
        }
        if(empty($this->data['uid'])){
            $this->data['gid'] = 0;
            $this->data['access'] = $this->data['grouplist'][0];
            $this->data['user'] = array('uid'=>0,'gid'=>0);
        }
    }
    public function allowforum()
    {
        $data = $this->data;
        if (!isset($this->data['allowforum']) || $this->data['allowforum']==null) {
            $this->data['allowforum'] = array();
            foreach ($data['forumlist'] as $k => $v) {
                if (isset($data['forum_access'][$v['fid']])&&isset($data['forum_access'][$v['fid']][$data['gid']])) {
                    if(!empty($data['forum_access'][$v['fid']][$data['gid']]['allowread']))$this->data['allowforum'][] = $v['fid'];
                } else if (!empty($data['access']['allowread'])) {
                    $this->data['allowforum'][] = $v['fid'];
                }
            }
        }
        return $this->data['allowforum'];
    }
    public static function GET($name)
    {
        return empty($_GET[$name]) ? '' : $_GET[$name];
    }
    public static function POST($name)
    {
        return empty($_POST[$name]) ? '' : trim($_POST[$name]);
    }
    public static function url($router, $param = '', $clear = true)
    {
        $data = self::app()->data;
        $query = $clear ? array() : $data['get'];
        $URL = str_replace('/index.php','/',$_SERVER['URL']);
        if (!empty($param)) {
            if (is_string($param)) parse_str($param, $param);
            $query = array_merge($query, $param);
        }
        if (!empty($query)) $query = http_build_query($query);
        else $query = '';
        if (!empty($data['settings']['router_replace'])) {
            $router = strtr($router, $data['settings']['router_replace']);
        }
        if (!empty($data['settings']['site_rewrite'])) {
            if ($data['settings']['site_rewrite'] == 1) $router .= '.html';
            else if ($data['settings']['site_rewrite'] == 2) $router = str_replace('-', '/', $router) . '/';
            if (!empty($query)) $query = '?' . $query;
            return $URL. $router . $query;
        } else {
            if (!empty($query)) $query = '&' . $query;
            $result = $URL. '?' . $router . '.html' . $query;
            return strtr($result,array('/?index.html&'=>'/?','/?index.html'=>'/'));
        }
    }
    public static function avatar($user)
    {
        $uid = is_array($user) ? $user['uid'] : $user;
        $myapp =  self::app();
        $path = 'avatar/' . (substr(sprintf("%09d", $uid), 0, 3) . '/' . $uid) . '.png';
        if (is_file($myapp->data['path']['upload'] . $path)) {
            return $myapp->data['site']['upload'] . $path;
        }
        return $myapp->data['site']['images'] . 'avatar.png';
    }
    public function forumimg($fid)
    {
        if(is_file($this->data['path']['forum'].$fid.'.png')){
            return $this->data['site']['forum'].$fid.'.png';
        }
        return $this->data['site']['images'].'forum.png';
    }
    public static function exit($msg = false, $url = ''): never
    {
        self::app()->exit_clear();
        if ($url) header('Location:' . $url);
        else if (!empty($msg)) echo $msg;
        exit;
    }
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        if (empty($this->conf['debug']) || !(error_reporting() & $errno)) {
            return false;
        }

        // $errstr may need to be escaped:
        $errstr = htmlspecialchars($errstr);
        $language = language::app();
        if (!empty($this->data['path']['root'])) {
            $errstr = strtr($errstr, array(
                'Undefined variable' => $language['Undefined_variable'],
                'No such file or directory' => $language['no_such_file_or_dir'],
                'Failed to open stream' => $language['failed_open_stream'],
                'Failed opening' => $language['failed_opening'],
                $this->data['path']['root'] => ''
            ));
            $errfile = str_replace($this->data['path']['root'], $language['root_path'], $errfile);
        }
        switch ($errno) {
            case E_USER_ERROR:
                echo $this->error_text($errfile, $errline, $errstr, $language['NOTICE']);
                $this->exit();
                break;
            case E_USER_NOTICE:
            case E_NOTICE:
                echo $this->error_text($errfile, $errline, $errstr, $language['NOTICE']);
                break;
            default:
                echo $this->error_text($errfile, $errline, $errstr, $language['WARNING']);
                break;
        }
        return true;
    }
    public function error_text($errfile, $errline, $errstr, $level = "WARNING")
    {
        return '<span style="display:inline-block;background:#f95543;color:#000;border:1px solid #313131;font-size:12px;border-radius:.25rem;padding:1px;"><b>' . $level . ':</b>' . $errfile . '(' . $errline . ')<b style="color:#fff;">' . $errstr . '</b></span>';
    }
    public function error_exception_handler($exception)
    {
        #print_r($exception);
        ob_end_clean();
        include $this->template('exception');
        $this->exit();
    }
    public $session_fields = array('uid', 'gid', 'username', 'login_date', 'logins');
    public function session_tokens($user)
    {
        $tokens = array();
        foreach ($this->session_fields as $v) {
            $tokens[] = empty($user[$v])?'':$user[$v];
        }
        #echo $this->str_encrypt(http_build_query(array_values($tokens)));
        $this->setCookies('tokens',$this->str_encrypt(http_build_query(array_values($tokens))));
    }
    public function session_login($uid)
    {
        $user = DB::t('user')->uids($uid);
        #print_r($user);
        if(!empty($user['uid'])){
            $this->data['user'] = $user;
            $this->data['uid'] = $user['uid'];
            $this->data['gid'] = $user['gid'];
            $this->data['access'] = $this->data['grouplist'][0];
            $this->session_tokens($user);
        }
    }
    public function session_verify()
    {
        if(!empty($this->data['uid'])&&empty($this->data['user']['password'])){
            $this->data['user'] = DB::t('user')->uids($this->data['user']['uid']);
            if(!empty($this->data['user']['uid'])){
                $this->data['uid'] = $this->data['user']['uid'];
                $this->data['gid'] = $this->data['user']['gid'];
                $this->data['access'] = $this->data['grouplist'][$this->data['gid']];
                $this->data['allowforum'] = null;
            }
        }
    }
    public static function template($router)
    {
        $myapp = self::app();
        list($path, $cachefile, $cachelink, $name) = $myapp->str_path($router);
        if (!empty($myapp->conf['debug']) || !is_file($cachefile)) new template($name, $path, $cachefile);
        return $cachefile;
    }
    public function router($name)
    {
        list($path, $cachelink) = $this->str_path($name, 'router');
        if (empty($this->data['settings']['include_router'])) {
            if (is_file($path)) {
                return $path;
            } else {
                return $this->data['path']['router'] . '404.inc.php';
            }
        } else {
            if (!empty($this->conf['debug']) || !is_file($cachelink)) {
                if (is_file($path)) {
                    $this->router_parse($path, $cachelink);
                } else {
                    return $this->data['path']['router'] . '404.inc.php';
                }
            }
        }
        return $cachelink;
    }
    public function router_parse($path, $link)
    {
        $router_data = file_get_contents($path);
        if (!isset($this->plugin['include_data'])) {
            $this->write_plugin_data();
        }
        if (!empty($this->plugin['include_data'])) {
            $router_data = preg_replace_callback('/\/\/\shook\s(.+)(\.php)?\s+?\n/', fn ($m) => empty($this->plugin['include_data'][$m[1]]) ? PHP_EOL : $this->plugin['include_data'][$m[1]] . PHP_EOL, $router_data);
        }
        file_put_contents($link, $router_data);
    }
    public static function time_format($time)
    {
        return date("Y-m-d H:i:s", $time);
    }
    public static function time_human($timesize)
    {
        $myapp = APP::app();
        if (strlen($timesize) >= 10) {
            $timesize = $myapp->data['time'] - $timesize;
        }
        if ($timesize <= 60) {
            $str = $timesize . $myapp->language['time_second_ago'];
        } elseif ($timesize <= 3600) {
            $str = floor($timesize / 60) . $myapp->language['time_minute_ago'];
        } elseif ($timesize <= 86400) {
            $str = floor($timesize / 3600) . $myapp->language['time_hour_ago'];
        } elseif ($timesize <= 86400 * 7) {
            $str = floor($timesize / 3600) . $myapp->language['time_day_ago'];
        } elseif ($timesize <= 86400 * 30) {
            $str = floor($timesize / (86400 * 7)) . $myapp->language['time_week_ago'];
        } elseif ($timesize <= 86400 * 365) {
            $hours = floor($timesize / (86400 * 30));
            $str = $hours . $myapp->language['time_month_ago'];
        } else {
            $year = floor($timesize / (86400 * 365));
            $str = $year . $myapp->language['time_year_ago'];
        }
        return $str;
    }
    public static function write_data($path, $data)
    {
        return file_put_contents($path, "<?php\n/*time: " . date("M j, Y, G:i") . "*/\ndefined('XIUNO')||die('return to <a href=\"\">Home</a>');\nreturn " . var_export($data, true) . ";\n?>");
    }
    public static function init_path()
    {

        //$webroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $webroot = $_SERVER['DOCUMENT_ROOT'];
        //$website = '//'. $_SERVER['HTTP_HOST'];
        $approot =  dirname(__DIR__, 2) . "\\";
        //$approot = str_replace('\\', '/', $appnav);
        $appsite = str_replace($webroot, '', $approot);
        $assets = $approot . 'assets\\';
        $appsite = str_replace(array($webroot, '\\'), array('', '/'), $approot);
        return array(
            'path' => array(
                'web' => $webroot . '\\',
                'root' => $approot,
                'assets' => $assets,
                'conf' => $assets . 'conf\\',
                'cache' => $approot . 'cache\\',
                'data' => $approot . 'cache\\data\\',
                '_css' => $approot . 'cache\\css\\',
                '_router' => $approot . 'cache\\router\\',
                '_template' => $approot . 'cache\\template\\',
                'lang' => $assets . 'lang\\',
                'class' => $assets . 'class\\',
                'template' => $assets . 'template\\',
                'router' => $assets . 'router\\',
                'css' => $assets . 'css\\',
                'plugin' => $approot . 'plugin\\',
                'upload' => $approot . 'upload\\',
                'attach' => $approot . 'upload\\attach\\',
                'avatar' => $approot . 'upload\\avatar\\',
                'forum' => $approot . 'upload\\forum\\',
                'tmp' => $approot . 'upload\\tmp\\',
            ),
            'site' => array(
                'root' => $appsite,
                'assets' => $appsite . 'assets/',
                'js' => $appsite . 'assets/js/',
                'css' => $appsite . 'assets/css/',
                'fonts' => $appsite . 'assets/fonts/',
                'lang' => $appsite . 'assets/lang/',
                'images' => $appsite . 'assets/images/',
                'template' => $appsite . 'assets/template/',
                'cache' => $appsite . 'cache/',
                'data' => $appsite . 'cache/data/',
                '_css' => $appsite . 'cache/css/',
                '_router' => $appsite . 'cache/router/',
                '_template' => $appsite . 'cache/template/',
                'plugin' => $appsite . 'plugin/',
                'upload' => $appsite . 'upload/',
                'attach' => $appsite . 'upload/attach/',
                'avatar' => $appsite . 'upload/avatar/',
                'forum' => $appsite . 'upload/forum/',
            )
        );
    }
    public function write_settings()
    {
        $data = $this->init_path();
        //$webroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $webroot = $_SERVER['DOCUMENT_ROOT'];
        //$website = '//'. $_SERVER['HTTP_HOST'];
        $approot =  dirname(__DIR__, 2) . "\\";
        //$approot = str_replace('\\', '/', $appnav);
        $appsite = str_replace($webroot, '', $approot);
        $assets = $approot . 'assets\\';
        $appsite = str_replace(array($webroot, '\\'), array('', '/'), $approot);
        $data['path'] = array(
            'web' => $webroot . '\\',
            'root' => $approot,
            'assets' => $assets,
            'conf' => $assets . 'conf\\',
            'cache' => $approot . 'cache\\',
            'data' => $approot . 'cache\\data\\',
            '_css' => $approot . 'cache\\css\\',
            '_router' => $approot . 'cache\\router\\',
            '_template' => $approot . 'cache\\template\\',
            'lang' => $assets . 'lang\\',
            'class' => $assets . 'class\\',
            'template' => $assets . 'template\\',
            'router' => $assets . 'router\\',
            'css' => $assets . 'css\\',
            'plugin' => $approot . 'plugin\\',
            'upload' => $approot . 'upload\\',
            'attach' => $approot . 'upload\\attach\\',
            'avatar' => $approot . 'upload\\avatar\\',
            'forum' => $approot . 'upload\\forum\\',
            'tmp' => $approot . 'upload\\tmp\\',
        );
        $data['site'] = array(
            'root' => $appsite,
            'assets' => $appsite . 'assets/',
            'js' => $appsite . 'assets/js/',
            'css' => $appsite . 'assets/css/',
            'fonts' => $appsite . 'assets/fonts/',
            'lang' => $appsite . 'assets/lang/',
            'images' => $appsite . 'assets/images/',
            'template' => $appsite . 'assets/template/',
            'cache' => $appsite . 'cache/',
            'data' => $appsite . 'cache/data/',
            '_css' => $appsite . 'cache/css/',
            '_router' => $appsite . 'cache/router/',
            '_template' => $appsite . 'cache/template/',
            'plugin' => $appsite . 'plugin/',
            'upload' => $appsite . 'upload/',
            'attach' => $appsite . 'upload/attach/',
            'avatar' => $appsite . 'upload/avatar/',
            'forum' => $appsite . 'upload/forum/',
        );
        $arr = ['data', 'css', 'template', 'router', 'class'];
        if (!is_dir($data['path']['cache'])) $this->mkdir($data['path']['cache']);
        foreach ($arr as $k => $v) {
            if (!is_dir($data['path']['cache'] . $v)) {
                $this->mkdir($data['path']['cache'] . $v);
            }
        }
        $arr = ['attach', 'avatar', 'forum', 'tmp'];
        if (!is_dir($data['path']['upload'])) $this->mkdir($data['path']['upload']);
        foreach ($arr as $k => $v) {
            if (!is_dir($data['path']['upload'] . $v)) {
                $this->mkdir($data['path']['upload'] . $v);
            }
        }
        if (!is_dir($data['path']['plugin'])) $this->mkdir($data['path']['plugin']);
        #$result = DB::mquery_table(array('settings', 'forum', 'forum_access', 'group'));
        $result = array(
            'settings' => DB::t('settings')->all(),
            'forum' => DB::t('forum')->all('',array('order'=>array('rank'=>'DESC'))),
            'forum_access' => DB::t('forum_access')->all(),
            'group' => DB::t('group')->all(),
        );
        foreach ($result['settings'] as $k => $v) {
            if ($v['type'] == 2) $v['value'] = unserialize($v['value']);
            $data['settings'][$v['name']] = $v['value'];
        }
        $data['smtp'] = $data['settings']['smtp'];
        unset($data['settings']['smtp']);
        if (isset($result['forum'])) {
            $moduids = [];
            foreach ($result['forum'] as $k => $v) {
                if (is_file($data['path']['upload'] . 'forum/' . $k . '.png')) {
                    $v['img_url'] = $data['site']['upload'] . 'forum/' . $k . '.png';
                } else {
                    $v['img_url'] = $data['site']['images'] . 'forum.png';;
                }
                if (!empty($v['moduids'])) {
                    $v['moduids'] = array_unique(explode(',', $v['moduids']));
                    $moduids = array_merge($moduids, $v['moduids']);
                }
                $data['forumlist'][$v['fid']] = $v;
            }
            $data['forumnames'] = array_column($data['forumlist'], 'name','fid');
            $userlist = [];
            if (!empty($moduids)) {
                $userlist = DB::t('user')->uids(array_unique($moduids));
            }
            foreach ($data['forumlist'] as $k => $v) {
                if (!empty($userlist) && !empty($v['moduids'])) {
                    foreach ($v['moduids'] as $a => $b) {
                        if (isset($userlist[$b])) $data['forumlist'][$k]['moderators'][$b] = $userlist[$b]['username'];
                    }
                }
                if (!empty($v['fup']) && isset($data['forumlist'][$v['fup']])) {
                    $data['forumlist'][$v['fup']]['subforum'][] = $v['fid'];
                }
            }
        }
        if (isset($result['forum_access'])) {
            foreach ($result['forum_access'] as $k => $v) {
                $data['forum_access'][$v['fid']][$v['gid']] = $v;
            }
        }
        if (isset($result['group'])) {
            foreach ($result['group'] as $k => $v) {
                $data['grouplist'][$v['gid']] = $v;
            }
        }
        if ($dh = opendir($data['path']['router'])) {
            while (($file = readdir($dh)) !== false) {
                if (strpos($file, '.inc.php') !== false) {
                    $data['router_list'][] = str_replace('.inc.php', '', $file);
                }
            }
            closedir($dh);
        }
        $data['ver'] = time();
        $this->data = $data;
        $this->write_data($data['path']['data'] . 'settings.php', $data);
        $this->write_rewrite($data);
        $this->write_plugin($data);
    }
    public function write_rewrite($data = array())
    {
        if (empty($data)) $data = $this->data;
        $rootlist = [];
        if ($dh = opendir($data['path']['root'])) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($data['path']['root'] . $file)) {
                        $rootlist[] = $file . "\/?";
                    } elseif (is_file($file)) {
                        $rootlist[]  = addcslashes($file, '.[]?-_:'); //str_replace('.', '\.', $file);
                    }
                }
            }
            closedir($dh);
        }
        $rule_str = '(?!(' . implode('|', $rootlist) . '))(.*)';
        if (!empty($_SERVER['IIS_UrlRewriteModule'])) {
            $doc = new \DOMDocument('1.0','UTF-8');
            $doc->formatOutput = !0;
            $doc->preserveWhiteSpace = !0;
            $rewrite_file = $data['path']['root'] . 'web.config';
            if (is_file($rewrite_file)) {
                @$doc->load($rewrite_file);
                $node_rules = null;
                $node_rewrite = $doc->getElementsByTagName('rewrite')[0];
                if (!empty($node_rewrite))$node_rules = $node_rewrite->getElementsByTagName('rules')[0];
                if (!empty($node_rules)) {
                    $rule_list = $node_rules->getElementsByTagName('rule');
                    for($i=$rule_list->length;--$i>=0;){
                        $rule_elm = $rule_list->item($i);
                        if (!empty($rule_elm) && $rule_attr = $rule_elm->attributes->getNamedItem('name')) {
                            $nodeValue = $rule_attr->nodeValue;
                            if ($nodeValue == 'xiuno_rewrite') {
                                $rule_elm->parentNode->removeChild($rule_elm);
                                continue;
                            }
                        }
                    }
                }else{
                    $xdoc = $doc;
                    foreach(array('configuration','system.webServer','rewrite','rules') as $v){
                        $elm = $doc->getElementsByTagName($v)[0];
                        if(empty($elm)){
                            $elm = $doc->createElement($v);
                            $xdoc->appendChild($elm);
                        }
                        $xdoc = $elm; 
                    }
                    $node_rules = $xdoc;
                }
            } else {
                $doc->loadXML("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n\t<system.webServer>\n\t\t<rewrite>\n\t\t\t<rules>\n\t\t\t</rules>\n\t\t</rewrite>\n\t</system.webServer>\n</configuration>");
                $node_rewrite = $doc->getElementsByTagName('rewrite')[0];
                $node_rules = $node_rewrite->getElementsByTagName('rules')[0];
            }
            $new_rule = $doc->createElement('rule');
            $new_match = $doc->createElement('match');
            $new_action = $doc->createElement('action');
            $new_conditions = $doc->createElement('conditions');
            $node_rules->appendChild($new_rule);
            $new_rule->appendChild($new_match);
            $new_rule->appendChild($new_conditions);
            $new_rule->appendChild($new_action);
            $new_rule->setAttribute('name', 'xiuno_rewrite');
            $new_rule->setAttribute('stopProcessing', 'true');
            $new_match->setAttribute('url', '^' . $rule_str . '$');
            $new_conditions->setAttribute('logicalGrouping', 'MatchAll');
            $new_conditions->setAttribute('trackAllCaptures', 'false');
            $new_action->setAttribute('type', 'Rewrite');
            $new_action->setAttribute('url', 'index.php?{R:0}');
            file_put_contents($rewrite_file, $doc->saveXML());
        } else {
            file_put_contents($data['path']['root'] . '.htaccess', "<ifMudule mod_rewrite.c>\nRewriteEngine on\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteCond %{REQUEST_FILENAME} !-f\n" . 'RewriteRule ^' . addcslashes($data['site']['root'], '/.-_') . $rule_str . '$ ' . $data['site']['root'] . 'index.php?$1 [L]' . "\n</ifMudule>");
        }
    }
    public function write_plugin($data = array())
    {
        if (empty($data)) $data = $this->data;
        $result = array(
            'list' => [],
            'router' => [],
            'template' => []
        );
        $path = $data['path']['plugin'];
        $pluginjs = '';
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                $pathfile = $path . $file . '/app.json';
                if (is_dir($path . $file) && is_file($pathfile)) {
                    $v = json_decode(file_get_contents($pathfile), !0);
                    if (!empty($v['require'])) {
                        if (is_string($v['require'])) {
                            $v['require'] = explode(',', $v['require']);
                            $result['require'][$file] = $v['require'];
                        }
                    }
                    foreach (['hook', 'include', 'template', 'css', 'lang','router'] as $dir) {
                        if (is_dir($path . $file . '\\' . $dir)) {
                            $v['dir_' . $dir] = $file;
                        }
                    }
                    $result['list'][$file] = $v;
                }
            }
            closedir($dh);
        }
        if (!empty($result['list'])) {
            $plugin_names = array_keys($result['list']);
            foreach ($result['list'] as $file => $v) {
                if (!empty($v)) {
                    if (!empty($v['require'])) {
                        if ($this->plugin_read_require($file, $plugin_names, $result['require'])) {
                            unset($result['list'][$file]);
                            echo '<h1 style="color:red;">plugin:' . $file . ' lost require:' . implode('|', $v['require']) . ',will not running!</h1>';
                            continue;
                        }
                    }
                    foreach(array('template','router','css','class','js') as $dir){
                        if (!empty($v[$dir])) {
                            if($v[$dir]===!0){
                                if($dir=='class'){
                                    $this->plugin_class_getMethod($file.'\\'.$file,function($method,$class_name) use (&$result){
                                        $result['method'][$method][] = $class_name;
                                    });
                                }
                                continue;
                            }elseif (is_string($v[$dir])) $v[$dir] = array_unique(explode(',', $v[$dir]));
                            foreach ($v[$dir] as $tempname) {
                                $tempname = basename($tempname);
                                if($dir=='class'){
                                    $this->plugin_class_getMethod($file.'\\' . $tempname,function($method,$class_name) use (&$result){
                                        $result['method'][$method][] = $class_name;
                                    });
                                }elseif($dir=='js'){
                                    if (is_file($path . $file . '\js\\' . $tempname.'.js')) {
                                        $pluginjs .= file_get_contents($path . $file . '\js\\' . $tempname.'.js') . ';' . PHP_EOL;
                                    }
                                }else{
                                    $result[$dir][$tempname] = $file;
                                }
                            }
                        }
                    }
                }
            }
        }
        file_put_contents($data['path']['data'] . 'plugin.js', $pluginjs);
        $this->write_data($data['path']['data'] . 'plugin.php', $result);
        return $result;
    }
    public function plugin_class_getMethod($class_name,$fn)
    {
        $class_name = 'plugin\\'.$class_name;
        if (class_exists($class_name)) {
            $methods = get_class_methods($class_name);
            foreach ($methods as $method) {
                $fn($method,$class_name);
            }
        }
    }
    public function write_plugin_data($dir = 'include')
    {
        $loadpath = $this->data['path']['data'] . 'plugin_' . $dir . '.php';
        if (!empty($this->conf['debug']) || !is_file($loadpath)) {
            foreach ($this->plugin['list'] as $k => $v) {
                if (empty($v['dir_' . $dir])) continue;
                $path = $this->data['path']['plugin'] . $k . '\\' . $dir . '\\';
                if (is_dir($path)) {
                    if ($dh = opendir($path)) {
                        while (($file = readdir($dh)) !== false) {
                            if ($filename = strstr($file, '.', !0) && !empty($filename)) {
                                $strdata = '';
                                $filetype = strstr($file, '.');
                                if ($filetype == '.php') {
                                    if ($dir == 'include') {
                                        $strdata = preg_replace('/^<\?php\s(.+)\?>$/is', '\\1', trim(php_strip_whitespace($path . $file)));
                                    } elseif ($dir == 'hook') {
                                        $strdata = (string)include($path . $file);
                                    }
                                } else {
                                    $strdata = file_get_contents($path . $file) . ";\n";
                                }
                                if (!isset($this->plugin[$dir . '_data'][$filename])) $this->plugin[$dir . '_data'][$filename] = "\n";
                                $this->plugin[$dir . '_data'][$filename] .= $strdata;
                            }
                        }
                    }
                }
            }
            $this->write_data($loadpath, $this->plugin[$dir . '_data']);
        } else {
            $this->plugin[$dir . '_data'] = (array)include($loadpath);
        }
    }
    public function write_plugin_lang($dirs, $name)
    {
        $langdata = [];
        foreach ($dirs as $dir) {
            $file = $this->data['path']['plugin'] . $dir . '\lang\\' . $name . '.php';
            if (is_file($file)) {
                $langdata += (array) include($file);
            }
        }
        $this->write_data($this->data['path']['data'] . 'plugin_lang_'.str_replace('-','_',$name).'.php', $langdata);
        $this->language += $langdata;
    }
    public function plugin_read_require($name, $plugin_names, &$require)
    {
        foreach ($require[$name] as $plugin_name) {
            if (empty($plugin_names[$plugin_name])) {
                unset($require[$name]);
                return !0;
            } else if (!empty($array[$plugin_name])) {
                return $this->plugin_read_require($plugin_name, $plugin_names, $require);
            }
        }
        return !1;
    }
    public function plugin_class_load($plugin)
    {
        if (!isset(self::$plugin_class[$plugin])) {
            self::$plugin_class[$plugin] = !1;
            if(class_exists($plugin)){
                return self::$plugin_class[$plugin] = new $plugin;
            }
        }
        return self::$plugin_class[$plugin];
    }
    public function plugin_class_method($plugin,$method)
    {
        if($plugin_class = $this->plugin_class_load($plugin)){
            $plugin_method = array($plugin_class,$method);
            if(is_callable($plugin_method)){
                return $plugin_method;
            }
        }
    }
    public function plugin_method_call($method,$fn=null)
    {
        $fn_arr = array();
		if (!empty($this->plugin['method'][$method])) {
            #插件中处理格式化后的HTML
			foreach ($this->plugin['method'][$method] as $k => $v) {
                if($plugin_method = $this->plugin_class_method($v,$method)){
                    if($fn)$fn($plugin_method);
                    $fn_arr[] = $plugin_method;
                }
            }
        }
        return $fn_arr;
    }
    public function plugin_method_filter($method,$data='')
    {
        $fn_arr = array();
		if (!empty($this->plugin['method'][$method])) {
			foreach ($this->plugin['method'][$method] as $k => $v) {
                if($plugin_method = $this->plugin_class_method($v,$method)){
                    $data = call_user_func($plugin_method,$data);
                }
            }
        }
        return $data;
    }
    public function scss_load($name,$link=false)
    {
		list($path, $cachefile, $csslink) = $this->str_path($name, 'css');
		if(!empty($this->conf['debug']) || !is_file($csslink)){
			if (!is_file($path)) {
				$path = $this->data['path']['css'] . basename($path);
			}
			if (is_file($path)) $this->scss_write($path, $cachefile, $this->data['site']);
		}
		if ($link) return $csslink;
		return '<link rel="stylesheet" type="text/css" href="' . $csslink . '?' . $this['ver'] . '" />';
    }
	public function scss_write($srcFile, $tempFile, $var = array())
	{
		$SCSS = new \ScssPhp\ScssPhp\Compiler();
		$SCSS->setOutputStyle('compressed');
		$SCSS->setImportPaths(dirname($srcFile) . '\\');
		if (!empty($var)) {
			$SCSS->addVariables($var);
		}
		$scssStr = file_get_contents($srcFile);
		file_put_contents($tempFile, $SCSS->compileString($scssStr)->getCss());
	}
}
class language implements \ArrayAccess
{
    public static object $_app;
    public static function app()
    {
        if (empty(self::$_app)) {
            $class = get_called_class();
            new $class();
        }
        return self::$_app;
    }
    function __construct()
    {
        self::$_app = $this;
    }
    public function offsetSet($offset, $value): void
    {
        APP::app()->language[$offset] = $value;
    }
    public function offsetExists($offset): bool
    {
        return isset(APP::app()->language[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset(APP::app()->language[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->offsetExists($offset) ? APP::app()->language[$offset] : '!' . $offset . '!';
    }
    public function parse($offset, $arr = array())
    {
        $data = $this->offsetGet($offset);
        if (!empty($arr)) {
            if (array_is_list($arr)) {
                if (preg_match_all('/(\{.+?\})/', $data, $matchs)) {
                    foreach ($matchs[0] as $k => $v) {
                        if ($arr[$k]) {
                            $data = str_replace($v, $arr[$k], $data);
                        }
                    }
                }
            } else {
                foreach ($arr as $k => $v) {
                    $data = str_replace('{' . $k . '}', $v, $data);
                }
            }
        }
        return $data;
    }
}
class sitelink implements \ArrayAccess
{
    public static object $_app;
    public static function app()
    {
        if (empty(self::$_app)) {
            $class = get_called_class();
            new $class();
        }
        return self::$_app;
    }
    function __construct()
    {
        self::$_app = $this;
    }
    public function offsetSet($offset, $value): void
    {
        APP::app()->data['site'][$offset] = $value;
    }
    public function offsetExists($offset): bool
    {
        return isset(APP::app()->data['site'][$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset(APP::app()->data['site'][$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->offsetExists($offset) ? APP::app()->data['site'][$offset] : APP::app()->data['site']['root'];
    }
}
class message{
    public static function post_sub($post,$length)
    {
        if($post['doctype'] == 0){
            $post['message'] = self::html($post['message'],$length);
        }else{
            $post['message'] = nl2br(htmlentities(substr(trim($post['message']),0,$length)));
        }
        return $post;
    }
    public static function html($message,$length=0)
    {
        $doc = new \DOMDocument('1.0','UTF-8');
        $myapp = APP::app();
        if($length!=0){
            $message = substr($message,0,$length);
        }
        @$doc->loadHTML('<html><head><meta charset="'.$myapp->data['charset'].'"></head><body><b style="position:1;background-position: bottom;">'.$message.'</body></html>');
        $body = $doc->getElementsByTagName('body')[0];
        foreach(array('script','style','title','meta','link','iframe') as $v){
            $srcipt = $doc->getElementsByTagName($v);
            if(!empty($srcipt)&&$srcipt->length){
                for($i=$srcipt->length;--$i>=0;){
                    $node = $srcipt->item($i);
                    $parentNode = $node->parentNode;
                    if(!in_array($parentNode->nodeName,array('head','html'))){
                        $parentNode->removeChild($node);
                    }

                }
            }
        }
        self::html_remove_attr($body->childNodes);
        $result = trim(preg_replace('/^\<body\>(.+?)\<\/body\>$/s',"\\1",html_entity_decode($doc->saveHTML($body))));
        return $result;
    }
    public static function html_remove_attr($nodes)
    {
        if(!empty($nodes->length)){
            foreach( iterator_to_array( $nodes ) as $node ){
                if($node->nodeType===1){
                    if(!empty($node->attributes->length)){
                        foreach( iterator_to_array( $node->attributes ) as $attribute ){
                            if($attribute->nodeName=='style'){
                                $attribute->nodeValue = preg_replace('/(?!-)position\s*?\:/i','',$attribute->nodeValue);
                            }else if(preg_match('/^(on|id)/i',$attribute->nodeName)){
                                $node->removeAttribute($attribute->nodeName);
                            }
                            if($node->childNodes->length){
                                self::html_remove_attr($node->childNodes);
                            }
                        }
                    }
                }
            }
        }
    }
    public static function post_html($post)
    {
        $post['message'] = self::html($post['message']);
        APP::app()->plugin_method_call('message_format_html',function($plugin_method) use (&$post){
            $post=call_user_func($plugin_method,$post)?:$post;
        });
        APP::app()->plugin_method_call('message_format_ubb',function($plugin_method) use (&$post){
            $post=call_user_func($plugin_method,$post)?:$post;
        });
        return $post;
    }
    public static function post_text($post)
    {
        APP::app()->plugin_method_call('message_format_text',function($plugin_method) use (&$post){
            $post=call_user_func($plugin_method,$post)?:$post;
        });
        APP::app()->plugin_method_call('message_format_ubb',function($plugin_method) use (&$post){
            $post=call_user_func($plugin_method,$post)?:$post;
        });
        return $post;
    }
    public static function post($post)
    {
        APP::app()->plugin_method_call('message_format',function($plugin_method) use (&$post){
            $post=call_user_func($plugin_method,$post);
        });
        if($post['doctype'] == 0){
            $post = self::post_html($post);
        }elseif($post['doctype'] == 1){
            $post['message'] = nl2br(htmlentities(trim($post['message'])));
            $post = self::post_text($post);
        }else{
            APP::app()->plugin_method_call('message_format_other',function($plugin_method) use (&$post){
                $post=call_user_func($plugin_method,$post)?:$post;
            });
        }
        return $post;
    }
}