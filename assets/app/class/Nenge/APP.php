<?php
/**
 * @author Nenge<m@nenge.net>
 * @copyright Nenge.net
 * @link https://nenge.net
 * 核心引导文件
 */
namespace Nenge;
use ArrayAccess;
use lib\static_app;
use lib\app_method_get;
class APP implements ArrayAccess
{
    use static_app;
    use app_method_get;
    public array    $data = array();
    public array    $plugin;
    public array    $style;
    public array    $conf;
    public array    $settings;
    private array   $plugin_class;
    public array    $language;
    public array    $headlist = array();
    public array    $cookielist = array();
    public function __construct()
    {
        self::$_app = $this;
        include(APPROOT.DIRECTORY_SEPARATOR.'function'.DIRECTORY_SEPARATOR.'common.inc.php');
        register_shutdown_function(array($this, 'handler_shutdown'));
        set_exception_handler(array($this, 'handler_exception'));
        header_register_callback(array($this, 'handler_headers'));
        set_error_handler(array($this, 'handler_error'));
        ob_start();
        $this->load_init();
        $this->load_conf();
        $this->load_cookies();
        $this->load_variable();
        #调用插件 默认方法 非必要插件不应该启用
        $this->get_plugin_method('common');
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
        #if ($offset == 'language') return $this->language;
        return $this->offsetExists($offset) ? $this->data[$offset] : '';
    }
    private function load_conf()
    {
        $conflink = WEBROOT . 'config.inc.php';
        if(is_file($conflink)):
            $this->conf = (array)include($conflink);
            if(isset($this->conf['charset'])):
                $this->data['charset'] = $this->conf['charset'];
            endif;
        endif;
        #载入数据库驱动
        if(!empty($this->conf[1])):
            dbc::app($this->conf);
            $this->get_cache_data();
            $this->get_plugin_method('common_before');
        else:
            cache::app($this);
        endif;
    }
    private function load_init()
    {
        $this->data = array(
            'time'      => $_SERVER['REQUEST_TIME'],
            'microtime' =>$_SERVER['REQUEST_TIME_FLOAT'],
            'ip'        => $_SERVER['REMOTE_ADDR'],
            'title' => '',
            'keywords' => '',
            'description' => '',
            'routerjs' => 'script',
            'header_js_module' => array(),
            'header_js_async' => array(),
            'header_js' => array(),
            'footer_js' => array(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'charset' => 'utf-8',
            'user' => array(
                'uid' => 0,
                'gid' => 0,
            ),
            'cookies'=>array(),
            'i18n'  =>'zh-CN',
            'param' =>array(),
            'ajax' => urldecode($this->HEAD('ajax-fetch'))
        );
    }
    private function load_router()
    {
        $router_list = array(
            'index'=>''
        );
        $name = basename($_SERVER['SCRIPT_FILENAME']);
        $router_list = array('index');
        $query = '';
        #分析地址
        if(!empty($_SERVER['UNENCODED_URL'])):
            $scriptHref = urldecode($_SERVER['UNENCODED_URL']);
        elseif(!empty($_SERVER['REDIRECT_URL'])):
            $scriptHref = $_SERVER['REDIRECT_URL'];
        elseif(!empty($_SERVER['REQUEST_URI'])):
            $scriptHref = urldecode($_SERVER['REQUEST_URI']);
        endif;
        if(!empty($scriptHref)):
            #保证地址一致性
            $scriptHref = str_replace(WEBSITE.'?',$name.'?',$scriptHref);
            $scriptHref = str_replace(WEBSITE,'',$scriptHref);
            #解析 URL
            $urldata = parse_url($scriptHref);
            if($urldata['path']!=$name):
                #不是 index.php 伪静态
                $rstr = $urldata['path'];
                $key = array_keys($_GET);
                #确保 参数没有值!
                if(isset($key[0])&&isset($_GET[$key[0]])&&empty($_GET[$key[0]])):
                    $router = $rstr;
                    $query = isset($urldata['query'])?$urldata['query']:'';
                else:
                    $query = $scriptHref;
                endif;
            elseif(!empty($urldata['query'])):
                #是index.php 读取query部分
                #拆分
                $rstr = explode('&',$urldata['query']);
                $key = array_keys($_GET);
                #确保 参数没有值!
                if(isset($key[0])&&isset($_GET[$key[0]])&&empty($_GET[$key[0]])):
                    $router = $rstr[0];
                    $query = isset($rstr[1])?$rstr[1]:'';
                else:
                    $query = $urldata['query'];
                endif;
            endif;
            if(!empty($router)):
                $router = trim($router);
                $router = str_replace('\\','/',$router);
                #含有句号 如 xxx.html 拆分
                if(!empty($strl = strrchr($router,'.'))):
                    $router = substr($router,0,-strlen($strl));
                endif;
                #进一步过滤逗号
                $router = str_replace('.','/',$router);
                #含有破折号 过滤斜杠并转化
                if(strpos($router,'-')!==false):
                    if(strpos($router,'/')!==false):
                        $router = str_replace("/","-",$router);
                    endif;
                    $router_list = explode('-',$router);
                #只含有斜杠转化
                elseif(strpos($router,'/')!==false):
                    $router_list = explode('/',$router);
                else:
                #其他
                    $router_list = array($router);
                endif;
                foreach($router_list as $key=>$value):
                    if(is_numeric($value)):
                        #把数字字符转化为数字类型
                        $router_list[$key] = intval($value);
                    endif;
                endforeach;
                if(is_int($router_list[0])):
                    #第一个参数为数字当作帖子处理
                    array_unshift($router_list,'thread');
                endif;
                #匹配自定义正则 待定
                if(isset($this->data['settings']['url_preg'])):
                    $rstr = implode('-',$router_list);
                    $rstr2 = implode('/',$router_list);
                    foreach($this->data['settings']['url_preg'] as $key=>$value):
                        if(preg_match($value,$rstr,$matchs)):
                            $router_list = $matchs;
                            $router_list[0] = $key;
                            break;
                        endif;
                        if(preg_match($value,$rstr2,$matchs)):
                            $router_list = $matchs;
                            $router_list[0] = $key;
                            break;
                        endif;
                    endforeach;
                endif;
            endif;
        endif;
        parse_str($query,$this->data['param']);
        $this->data['router'] = $router_list;
    }
    private function load_variable()
    {
        $this->load_router();
        if(defined('DEBUG')):
            $this->data['v'] = $this->data['time'];
        endif;
        if($this->get_rewrite_check()):
            $this->data['rewrite'] = true;
        endif;
        if (!empty($this->data['mobile'])) :
            $this->data['ismobile'] = ' data-ismobile="true"';
        endif;
        $timezone = 'Asia/Shanghai';
        if(empty($this->data['cookies']['timezone']) || !date_default_timezone_set($this->data['cookies']['timezone'])):
            date_default_timezone_set($timezone);
        endif;
        if(isset($this->data['cookies']['sid'])):
            session_start();
            if(isset($_SESSION['login_uid'])):
                if($_SESSION['login_uid']>0):
                    $this->data['user'] = $this->t('user')->value($_SESSION['login_uid']);
                endif;
            else:
                #session_unset();
                #unset($this->data['cookies']['sid']);
            endif;
        endif;
        if(isset($this->data['cookies']['login'])):
            if($this->data['user']['uid']<=0):
                $login = $this->decrypt($this->data['cookies']['login']);
                if(!empty($login)):
                    $login = explode(':',$login);
                    $this->data['user'] = $this->t('user')->value($login[0]);
                endif;
            endif;
        endif;
    }
    private function load_cookies()
    {
        $this->conf['cookie_prefix'] = empty($this->conf['cookie_prefix'])?'bbs_':$this->conf['cookie_prefix'];
        $this->conf['cookie_domain'] = empty($this->conf['cookie_domain'])?'':$this->conf['cookie_domain'];
        $this->conf['cookie_path'] = empty($this->conf['cookie_path'])?'':$this->conf['cookie_path'];
        ini_set('session.name', $this->conf['cookie_prefix'] . 'sid');
        ini_set('session.sid_length', '32');
        ini_set('session.use_cookies', 'On');
        ini_set('session.use_only_cookies', 'On');
        ini_set('session.use_strict_mode', 'On');
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_secure', 'On');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.session.sid_bits_per_character', 5);
        ini_set('session.cookie_domain',$this->conf['cookie_domain']);
        ini_set('session.cookie_path', $this->conf['cookie_path']);
        ini_set('session.cookie_httponly', 'On');
        #读取专属cookies
        foreach ($_COOKIE as $name => $data) :
            if (strpos($name, $this->conf['cookie_prefix']) === 0) :
                $key = preg_replace('/^'.$this->conf['cookie_prefix'].'/','',$name);
                $this->data['cookies'][$key] = $data;
            endif;
        endforeach;
    }
    public function debug()
    {
        if(defined('DEBUG')):
            include $this->template('include/debug');
        endif;
        return '';
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
        if (!empty($data) and $time == -1) $time = $this->data['time'] + 2592000;
        $this->cookielist[$this->conf['cookie_prefix'] . $name] = array(
            $this->conf['cookie_prefix'] . $name,
            $data,
            ($time > $this->data['time'] or $time <= 0) ? $time : $time + $this->data['time'],
            $this->conf['cookie_path'],
            $this->conf['cookie_domain'],
            $_SERVER['HTTPS'] == 'on',
            true
        );
    }
    public function GET($name)
    {
        if(is_int($name))return array_slice(array_keys($_GET),$name,1)?:'';
        return empty($_GET[$name]) ? '' : trim($_GET[$name]);
    }
    public function POST($name)
    {
        if(is_int($name))return array_slice(array_keys($_POST),$name,1)?:'';
        return empty($_POST[$name]) ? '' : trim($_POST[$name]);
    }
    public function HEAD($name)
    {
        $name = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        return empty($_SERVER[$name]) ? '' : strtolower(urldecode(trim($_SERVER[$name])));
    }
    public function url($router, $param = '', $clear = true)
    {
        $data = $this->data;
        $query = $clear ? array() : $data['get'];
        $URL = str_replace('/index.php', '/', $_SERVER['PHP_SELF']);
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
            return $URL . $router . $query;
        } else {
            if (!empty($query)) $query = '&' . $query;
            $result = $URL . '?' . $router . '.html' . $query;
            return strtr($result, array('/?index.html&' => '/?', '/?index.html' => '/'));
        }
    }
    public function error($language, $title = 'unknow_action', $type = 'error')
    {
        #type insufficient_privilege 权限不足
        #unknow_action 未知操作
        #http_response_code
        $this->data['title'] = $this->getlang($title);
        $this->data['message'] = $this->getlang($language);
        $this->data['error_type'] = $type;
        if (empty($this->data['error_href'])) :
            $this->data['error_href'] = WEBSITE;
        endif;
        if ($this->data['ajax'] == 'ajax') :
            $this->json(array(
                'type' => $type,
                'title' => $this->data['title'],
                'messagge' => $this->data['message'],
                'href' => $this->data['error_href']
            ));
            exit;
        endif;
        include $this->template('error');
    }
    public function exit($msg = false, $url = ''): never
    {
        if ($url) $this->setHeader('Location:' . $url);
        else if (!empty($msg)) echo $msg;
        exit();
    }
    public function json($json): never
    {
        @ob_end_clean();
        $this->setHeader('Content-type: application/json');
        $this->exit(json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    public function exit_http($code = 404)
    {
        @ob_end_clean();
        http_response_code($code);
        exit;
    }
    public function template($router,$dir=false)
    {
        $result = $this->get_template_path($router);
        if(is_string($result)) return $result;
        if($dir&&$this->data['path']['_template']!=$dir):
            if(is_file($dir.$result[1])):
                return $dir.$result[1];
            endif;
        endif;
        $cachefile = $this->data['path']['_template'].$result[1];
        if (defined('DEBUG') || !is_file($cachefile)) :
            $template = new template($result[0], $cachefile);
            unset($template);
        endif;
        return $cachefile;
    }
    public function scss($scss){
        if(empty($result = $this->plugin_read('scss_link',$scss))):
            $newresult = $this->get_scss_path($scss);
            if(!empty($newresult)):
                if($this->write_scss($newresult[0],$newresult[1])):
                    $result = $newresult[2];
                endif; 
            endif;
        endif;
        if(!empty($result)):
            return '<link rel="stylesheet" type="text/css" href="' .$result.'" />';
        endif;
        return '';
    }
    public function write_scss($path,$pathlink)
    {
        if($content = file_get_contents($path)):
            if (class_exists('ScssPhp\\ScssPhp\\Compiler')):
                $SCSS = new \ScssPhp\ScssPhp\Compiler();
                $SCSS->addImportPath(fn($p)=>$this->get_scss_import($p,dirname($path).DIRECTORY_SEPARATOR));
                $pathFunc = "@function SitePath(\$src,\$mode:'root'){\n\t\$site:'".WEBSITE."'!default;\n";
                if (!empty($this->data['site'])):
                    foreach((array)$this->data['site'] as $key=>$value):
                        $pathFunc .= "\t@if \$mode == '".$key."'{\$site:'".$value."';}\n";
                    endforeach;
                endif;
                $pathFunc .= "\t@return \$site+\$src;\n}";
                if(!empty($this->style['var'])):
                    foreach($this->style['var'] as $key=>$value):
                        $pathFunc .= '\n$'.$key.':'.$value.';';
                    endforeach;
                endif;
                $scssStr = $pathFunc.$content;
                file_put_contents($pathlink, $SCSS->compileString($scssStr)->getCss());
                unset($SCSS);
                return true;
            endif;
        endif;
    }
    public function plugin_read(string $method,...$params)
    {
        $method = 'read_'.$method;
        if($keys = $this->get_plugin_keys($method)):
            foreach ($keys as $id):
                if($data = $this->get_plugin_class($id,$method,...$params)):
                    return $data;
                endif;
            endforeach;
        endif;
        return false;
    }
    public function plugin_set(string $method,mixed $data,...$params)
    {
        $method = 'set_'.$method;
        if($keys = $this->get_plugin_keys($method)):
            foreach ($keys as $id):
                $this->get_plugin_class($id,$method,$data,...$params);
            endforeach;
        endif;
        return $data;
    }
    public function plugin_str($method,...$params)
    {
        return $this->get_plugin_str('html_'.$method,$params);
    }
    public function plugin_echo($method,...$params)
    {
        echo $this->plugin_str($method,$params);
    }
    public function formatTime($time)
    {
        return date("Y-m-d H:i:s", $time);
    }
    public function outputTime($time, $hunman = false)
    {
        $str = $hunman ? $this->humanTime($time) : $this->formatTime($time);
        return '<app-time data-time="' . $time . '">' . $str . '</app-time>';
    }
    public  function humanTime($timesize)
    {
        if (strlen($timesize) >= 10) :
            $timesize = $this->data['time'] - $timesize;
        endif;
        if ($timesize <= 60) :
            $str = $timesize . $this->getLang('time_second_ago');
        elseif ($timesize <= 3600) :
            $str = floor($timesize / 60) . $this->getLang('time_minute_ago');
        elseif ($timesize <= 86400) :
            $str = floor($timesize / 3600) . $this->getLang('time_hour_ago');
        elseif ($timesize <= 86400 * 7) :
            $str = floor($timesize / 3600) . $this->getLang('time_day_ago');
        elseif ($timesize <= 86400 * 30) :
            $str = floor($timesize / (86400 * 7)) . $this->getLang('time_week_ago');
        elseif ($timesize <= 86400 * 365) :
            $hours = floor($timesize / (86400 * 30));
            $str = $hours . $this->getLang('time_month_ago');
        else :
            $year = floor($timesize / (86400 * 365));
            $str = $year . $this->getLang('time_year_ago');
        endif;
        return $str;
    }
    public function getLang($name, $arr = array())
    {
        if(!isset($this->language)):
            $this->get_lang_data();
        endif;
        if(isset($this->language[$name])):
            if(!empty($arr)):
                $data = $this->language[$name];
                if (isList($arr)) :
                    if (preg_match_all('/(\{.+?\})/', $data, $matchs)) :
                        foreach ($matchs[0] as $k => $v) :
                            if ($arr[$k]) :
                                $data = str_replace($v, $arr[$k], $data);
                            endif;
                        endforeach;
                    endif;
                else :
                    foreach ($arr as $k => $v) :
                        $data = str_replace('{' . $k . '}', $v, $data);
                    endforeach;
                endif;
                return $data;
            endif;
            return $this->language[$name];
        endif;
        return '<b>'.$name.'</b>';
    }
    public function setLang($data)
    {
        $this->language = $data;
    }
    public function t($table, $plugin = '')
    {
        return dbc::app()->table($table, $plugin);
    }
    public function getSql()
    {
        return dbc::app()->getSql();
    }
    public function setLink($path, $type = 'root')
    {
        if ($type && !empty($this->data['site'][$type])) :
            $url =  $this->data['site'][$type] . $path;
        else :
            $url = $path;
        endif;
        if ($_SERVER['HTTPS'] == 'on') :
            #http2 预加载
            if ($name = basename($url)) :
                $mime = 'images';
                if (strpos($name, '.css') !== false) :
                    $mime = 'style';
                elseif (strpos($name, '.js') !== false) :
                    $mime = 'script';
                endif;
                $this->setHeader('Link:<' . $url . '>;rel=preload;as=' . $mime, false);
            endif;
        endif;
        return $url;
    }
    public function setHeader(...$arg)
    {
        return $this->headlist[] = $arg;
        if (!empty($arg[0])) :
            $key = strtolower(trim(explode(':', $arg[0])[0]));
            if ($key == 'link') return $this->headlist[] = $arg;
            $this->headlist[$key] = $arg;
        endif;
    }
    function get_mail($toemail,$subject, $message,$username='',$charset = 'utf-8') {
        if(!empty($this->conf['smtp'])):
            $smtp = $this->conf['smtp'];
        elseif(!empty($this->data['settings']['smtp'])):
            $id = array_rand($this->data['settings']['smtp'],1);
            $smtp = $this->data['settings']['smtp'][$id];
        endif;
        if(!empty($smtp)):
            $class = '\\PHPMailer\\PHPMailer';
            if(!class_exists('\PHPMailer\\PHPMailer')):
                return;
            endif;
            $mail             = new $class();
            $host = empty($smtp['host'])?'127.0.0.1':$smtp['host'];
            if(empty($smtp['port'])):
                $smtp['port'] = 456;
            endif;
            if(strpos($smtp['host'],'ssl://') !=false):
                $host = substr($smtp['host'],6);
                $mail->SMTPSecure = 'ssl';
            endif;
            if($smtp['port']=='465'):
                $mail->SMTPSecure = 'ssl';
            endif;
            //$mail->PluginDir = FRAMEWORK_PATH.'lib/';
            if(empty($smtp['type'])|| $smtp['type']=='smtp'):
                $mail->IsSMTP(); // telling the class to use SMTP
            elseif($smtp['type']=='mail'):
                $mail->isMail(); // Send messages using PHP's mail() function.
            elseif($smtp['type']=='qmail'):
                $mail->isQmail(); // Send messages using qmail.
            elseif($smtp['type']=='sendmail'):
                $mail->isSendmail(); // Send messages using $Sendmail.
            endif;
            $mail->isHTML(true);
            $mail->SMTPDebug      = 0;   // enables SMTP debug information (for testing)
                                    // 1 = errors and messages
                                    // 2 = messages only
            $mail->Host           = $smtp['host']; // sets the SMTP server
            if(empty($smtp['pass'])):
                $mail->SMTPAuth   = TRUE;           // enable SMTP authentication
                $mail->Password   = $smtp['pass'];  // SMTP account password
            endif;
            $mail->Username       = $smtp['user'];  // SMTP account username
            $mail->Port           = $smtp['port'];                    // set the SMTP port for the GMAIL server
            $mail->Timeout        = 5;
            if(preg_match("/^\s*(.+?)<([\w\.@]+)>\s*$/",$toemail,$matches)):
                $username   = trim($matches[1]);
                $toemail  = trim($matches[2]);
            else:
                if(empty($username)):
                    $username   = strip_tags($this->getLang('system_email'));
                endif;
                $toemail  = $smtp['email'];
            endif;
            $fromname   = strip_tags($this->getLang('system_email'));
            $fromemail  = $smtp['email'];
            if(!empty($smtp['email'])):
                if(preg_match("/^\s*(.+?)<([\w\.@]+)>\s*$/",$smtp['email'],$matches)):
                    $fromname   = trim($matches[1]);
                    $fromemail  = trim($matches[2]);
                endif;
                $mail->SetFrom($fromemail,$fromname);
                $mail->AddReplyTo($fromemail,$fromname);
            endif;
            $mail->CharSet    = $charset;
            if(function_exists('mb_convert_encoding ')):
                $gbkmail = array(
                    'smtp.126.com',
                    'smtp.163.com'
                );
                if(in_array($host,$gbkmail)):
                    $mail->CharSet    = 'gbk';
                    $subject    = mb_convert_encoding($subject,$mail->CharSet,$charset);
                    $message    = mb_convert_encoding($message,$mail->CharSet,$charset);
                    $fromname   = mb_convert_encoding($fromname,$mail->CharSet,$charset);
                    $username   = mb_convert_encoding($username,$mail->CharSet,$charset);
                endif;
            endif;
            $mail->Encoding       = 'base64';
            $mail->Subject        = $subject;
            $mail->Body           = $message;
            $mail->AddAddress($toemail, $username);
            //$mail->AddAttachment("images/phpmailer.gif");      // attachment
            //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
            return $mail;
            #return $this->show_error($mail->ErrorInfo);
        endif;
    }
    public function safePath($str)
    {
        if(is_string($str))return str_replace(array(APPROOT,WEBROOT),'',$str);
        return array_map(fn($v)=>$this->safePath($v),$str);
    }
    public function ivcrypt($format=false)
    {
        if(empty($format))$format = date('Y-m');
        return substr(hash('sha256',$_SERVER['SERVER_NAME'].$format,true),0,openssl_cipher_iv_length($this->conf['encrypt_method']));
    }
    public function encrypt($txt,$buffer=false) 
    {
        if(!$buffer)$buffer = $this->ivcrypt();
        return openssl_encrypt(
            $txt, #明文
            $this->conf['encrypt_method'], #加密方式
            $this->conf['encrypt_key'], #口令
            OPENSSL_RAW_DATA, #标记
            #初始化向量
            $buffer,
        );
    }
    public function decrypt($hash,$buffer=false)
    {
        if(!$buffer)$buffer = $this->ivcrypt();
        return openssl_decrypt(
            $hash, #明文
            $this->conf['encrypt_method'], #加密方式
            $this->conf['encrypt_key'], #口令
            OPENSSL_RAW_DATA, #标记
            #初始化向量
            $buffer,
        );
    }
    public function pwcode($str)
    {
        return password_hash($str, PASSWORD_BCRYPT,array('cost'=>4));
    }
    public function pwinvalid($str,$hash)
    {
        return password_verify($str, $hash);
    }
    public function handler_headers()
    {
        foreach ($this->headlist as $arg) :
            call_user_func_array('header', $arg);
        endforeach;
        foreach ($this->cookielist as $arg) :
            call_user_func_array('setcookie', $arg);
        endforeach;
    }
    public function handler_error($errno, $errstr, $errfile, $errline)
    {
        if(strpos($errstr,'file_get_contents')===0||strpos($errstr,'include')===0||strpos($errstr,'fopen')===0):
            preg_match('/\((.+)\)/',$errstr,$matchs);
            if(!empty($matchs[1])):
                $str = $this->getLang('failed_open').$this->safePath($matchs[1]);
                $this->show_error($str,$this->getLang('error_file'),2001);
                return true;
            endif;
        endif;
        if(strpos($errstr,'file_put_contents')===0):
            $this->show_error($this->getLang('DIR_ERROR'),$this->getLang('error_privilege'),2002);
            return true;
        endif;
        if(strpos($errstr,'mysqli::')!==false):
            if(preg_match('/\(HY\d+\/(\d+)\)/is',$errstr,$matchs)):
                if(strpos($errstr,'mysqli::__construct')===0):
                    $message = 'mysqli::__construct('.APP::app()->safePath($errfile).':'.$errline.')';
                else:
                    $message = 'mysql::mysqli_errno('.$matchs[1].'):'.$errline;
                endif;
                $this->error_mysql(new \ErrorException($message,$matchs[1]));
            endif;
        endif;
        if (!defined('DEBUG')) return false;
        echo '<code title="'.$this->safePath($errfile).'('.$errline.')'.PHP_EOL.$errstr.'"><b>' .$errstr . '</b><b style="color:red;">(' . $errline . ')</b></code>';
        return true;
    }
    public function handler_exception(\Throwable  $exception)
    {
        ob_clean();
        if($exception instanceof \mysqli_sql_exception):
            return $this->error_mysql($exception);
        endif;
        if($exception instanceof \PDOException):
            return $this->error_mysql($exception);
        endif;
        $title = get_class($exception);
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $errorFile = $this->safePath($exception->getFile());
        $errorLine = $exception->getLine();
        $traceList = array();
        $includeList = array();
        $traceList[] = array(
            'file' =>$errorFile,
            'line' =>$errorLine.' '.get_class($exception),
        );     
        if($trace = $exception->getTrace()):
            $traceList = $this->get_backtrace($trace);
        endif;
        include $this->template('include/exception');
        $this->exit();
    }
    public function get_backtrace($trace)
    {
        $traceList = array();
        foreach ($trace as $v):
            $str = '';
            $param = '';
            if (!empty($v['function'])):
                $str .= str_replace('{closure}','{Anonymus functions}',$v['function']);
                if (isset($v['args'])&&is_array($v['args'])) {
                    $param .= implode(
                        ',',
                        array_map(
                            fn ($f) =>is_string($f)?($v['function']=='include'?$this->safePath($f):'String'):'Array',
                            $v['args']
                        )
                    );
                }
            endif;
            $traceList[] = array(
                'file' => isset($v['file'])?$this->safePath($v['file']):'',
                'class' =>isset($v['class'])?$v['class']:'',
                'func' =>$str,
                'param' => $param,
                'line' => isset($v['line'])?$v['line']:''
            );
        endforeach;
        return $traceList;
    }
    public function handler_shutdown()
    {
        if (!empty($_FILES)) :
            $tmp = $this->data['path']['tmp'];
            foreach ($_FILES as $file) :
                $file = $file['tmp_name'];
                if (!empty($file)) :
                    if (is_array($file)) :
                        foreach ($file as $subfile) :
                            if (is_file($subfile) and is_uploaded_file($subfile)) :
                                move_uploaded_file($subfile,$this->data['path']['tmp'] . basename($subfile));
                                unlink($this->data['path']['tmp']. basename($subfile));
                            endif;
                        endforeach;
                    elseif (is_file($file) and is_uploaded_file($file)) :
                        move_uploaded_file($file,$this->data['path']['tmp']. basename($file));
                        unlink($this->data['path']['tmp']. basename($file));
                    endif;
                endif;
            endforeach;
        endif;
    }
    /**
     * 致命错误 function
     *
     * @param string $errorstr
     * @param boolean $errtype
     * @param integer $code
     * @return void
     */
    public function show_error(string $errorstr,$errtype=false,int $code=0)
    {
        if(!is_string($errtype))$errtype   = $this->getlang('error_notice');
        $this->handler_exception(new \Exception('<b>'.$errtype.':</b>'.$errorstr,$code));
        exit;
    }
    /**
     * mysqli错误拦截 function
     *
     * @param type $error
     * @return void
     */
    public function error_mysql($error)
    {
        $message = $error->getMessage();
        $notice = false;        
        if(defined('DEBUG')):
            $message = str_replace('Incorrect string value:',$this->getLang('mysql_inncorrect_string'),$message);

            $message = str_replace('Duplicate entry ',$this->getLang('mysql_Duplicate_data'),$message);
            $message = str_replace(' column ',$this->getLang('mysql_column'),$message);
        endif;
        switch($error->getCode()):
            case 1045:{
                if(!defined('DEBUG'))$message = $this->getLang('error_user_or_password');
                $notice = $this->getLang('mysql_f_connect');
                break;
            }
            case 1049:{
                if(!defined('DEBUG'))$message = $this->getLang('mysql_e_input');
                $notice = $this->getLang('mysql_f_database');
                break;
            }
            case 2003:{
                if(!defined('DEBUG'))$message = $this->getLang('mysql_e_input');
                $notice = $this->getLang('mysql_f_host');
                break;
            }
            case 1146:{
                if(!defined('DEBUG'))$message = $this->getLang('mysql_e_table');
                $notice = $this->getLang('mysql_f_table');
                break;
            }
            case 1054:{
                if(!defined('DEBUG'))$message = $this->getLang('mysql_e_field');
                $notice = $this->getLang('mysql_f_field');
                break;
            }
            case 1064:{
                if(!defined('DEBUG'))$message = $this->getLang('mysql_e_sql');
                $notice = $this->getLang('mysql_f_sql');
                break;
            }
            default:{
                if(!defined('DEBUG')):
                    $message = $this->getLang('mysql_e_input');
                endif;
                $notice   = $this->getlang('mysql_error');
                break;
            }
        endswitch;
        $this->handler_exception(new \ErrorException('<b>'.$notice.':</b>'.$message,$error->getCode()));
    }
    public function __destruct()
    {
        $this->exit();
    }
}