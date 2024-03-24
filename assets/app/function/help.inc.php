<?php
function mini_path($dir)
{
    $files = array();
    $func = function($dir,&$files,$fn){
        foreach(scandir($dir) as $file):
            if ($file != '.' && $file != '..'):
                if(is_file($dir.$file)&&strpos($file,'.php')!=false):
                    $files[] = $dir.$file;
                elseif(is_dir($dir.$file)):
                    $fn($dir.$file.DIRECTORY_SEPARATOR,$files,$fn);
                endif;
            endif;
        endforeach;
    };
    $func($dir,$files,$func);
    rsort($files);
    return mini_file($files);
}
function mini_file(array $files){
    $namelist = array();
    foreach($files as $file):
        $php = preg_replace('/^\<\?php\s*/', '', php_strip_whitespace($file));
        $php = trim(preg_replace('/\s*\?\>$/','',$php));
        preg_match_all('/namespace\s*([\w\d\\\\]+)[;\{]/is', $php, $match);
        if (!empty($match[1])):
            $matchlist = $match[1];
            $maxnum = count($matchlist);
            for($i=0;$i<$maxnum;$i++){
                #if($matchlist[$i] == 'ScssPhp\ScssPhp\Exception') continue;
                if($i+1==$maxnum):
                    if(preg_match('/namespace\s+'.preg_quote($matchlist[$i]).'\s*\{(.*)\s*\}$/is',$php,$match2)):
                        $namelist[$matchlist[$i]][] = trim($match2[1]);
                    elseif(preg_match('/namespace\s+'.preg_quote($matchlist[$i]).'\s*;(.*)$/is',$php,$match2)):
                        $namelist[$matchlist[$i]][] = trim($match2[1]);
                    endif;
                else:
                    if(preg_match('/namespace\s+'.preg_quote($matchlist[$i]).'\s*\{(.*)\s*\}\s*namespace '.preg_quote($matchlist[$i+1]).'/is',$php,$match2)):
                        $namelist[$matchlist[$i]][] = trim($match2[1]);
                    elseif(preg_match('/namespace\s+'.preg_quote($matchlist[$i]).'\s*;(.*)\s*namespace '.preg_quote($matchlist[$i+1]).'/is',$php,$match2)):
                        $namelist[$matchlist[$i]][] = trim($match2[1]);
                    endif;
                endif;
            }
        endif;
    endforeach;
    $outlist = array();
    foreach($namelist as $name=>$value):
        $str = "namespace ".$name." {";
        $usedata = array();
        $classlist = array();
        foreach($value as $classdata):
            if(preg_match('/^\s*(use.+?)(?=class\s|abstract\s|trait\s|interface\s|final\s)/is', $classdata, $match3)):
                $rlist = explode(';',$match3[1]);
                foreach($rlist as $un):
                    $un = trim(substr(trim($un),3));
                    if(!empty($un)):
                        if(!isset($usedata[$un])):
                            $usedata[$un] = true;
                        endif;
                        $classdata = str_replace('use '.$un.';','',$classdata);
                    endif;
                endforeach;
            endif;
            if (preg_match('/class\s*([a-z0-9]+?)\s*implements\s([a-z0-9,\\\\\s]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
            elseif (preg_match('/class\s*([a-z0-9]+?)\s*extends\s*([a-z0-9\\\\]+?)\s*implements\s*([a-z0-9\\\\]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
            elseif (preg_match('/interface\s*([a-z0-9]+?)\s*extends\s*([a-z0-9\\\\]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
                
            elseif (preg_match('/class\s*([a-z0-9]+?)\s*extends\s*([a-z0-9\\\\]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
                
            elseif (preg_match('/class\s*([a-z0-9]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
                
            elseif (preg_match('/interface\s*([a-z0-9]+?)[\n\r\s]*\{/i', $classdata, $nameMatch)):
                $cname = $nameMatch[1];
            endif;
            if(!empty($cname)):
                $cname2 = $name.'\\'.$cname;
                $usedata[$cname2] = false;
                if(strpos($name,'ScssPhp\\ScssPhp')!==false):
                    if(in_array($cname,array('Debug','Crunched','Compact','Nested','ServerException'))):
                        #echo $cname.PHP_EOL;
                        continue;
                    endif;
                endif;
            endif;
            if(strpos($name,'ScssPhp\\ScssPhp')!==false):
                #if($name=='ScssPhp\\ScssPhp\\Exception'):
                    #$classdata = str_replace("@trigger_error(sprintf('The \"%s\" class is deprecated.', ServerException::class), E_USER_DEPRECATED);",'',$classdata);
                #endif;
                if($name=='ScssPhp\\ScssPhp'):
                    $classdata = str_replace("\$this->env->parent","\$this?->env?->parent",$classdata);
                endif;
                $classdata = str_replace("\$flags = []; for","\$flags=[];if(empty(\$lastNode))\$lastNode=[];for",$classdata);
                $classdata = str_replace("(SassException","(\Exception",$classdata);
                $classdata = str_replace("public function offsetExists(\$offset) ","public function offsetExists(\$offset):bool ",$classdata);
                $classdata = str_replace("public function offsetSet(\$offset, \$value) ","public function offsetSet(\$offset, \$value):void ",$classdata);
                $classdata = str_replace("public function offsetUnset(\$offset) ","public function offsetUnset(\$offset):void ",$classdata);
                $classdata = preg_replace("/[\n\r\s]*<<<EOL.+EOL;[\n\r\s]*/is",'"";',$classdata);
                $classdata = preg_replace("/@trigger_error\(.+?\);/is",'',$classdata);
                $classdata = str_replace("; ",";",$classdata);
                #$classdata = str_replace(" + ","+",$classdata);
                #$classdata = str_replace(" - ","-",$classdata);
                $classdata = str_replace(" : ",":",$classdata);
                $classdata = str_replace(" if ","if",$classdata);
                $classdata = str_replace(", ",",",$classdata);
                $classdata = str_replace(" = ","=",$classdata);
                $classdata = str_replace(" . ",".",$classdata);
                $classdata = str_replace(" == ","==",$classdata);
                $classdata = str_replace(" || ","||",$classdata);
                $classdata = str_replace(" && ","&&",$classdata);
                $classdata = str_replace(" => ","=>",$classdata);
                $classdata = str_replace(" === ","===",$classdata);
                $classdata = str_replace(") { ","){",$classdata);
                $classdata = str_replace("} ","}",$classdata);
            endif;
            $classdata = str_replace(" use ","\n\t\tuse ",$classdata);
            $classdata = str_replace(" const ","\n\t\tconst ",$classdata);
            $classdata = str_replace(";public function ",";\n\t\tpublic function ",$classdata);
            $classdata = str_replace(" public ","\n\t\tpublic ",$classdata);
            $classdata = str_replace(" protected ","\n\t\tprotected ",$classdata);
            $classdata = str_replace(" private ","\n\t\tprivate ",$classdata);
            $classdata = preg_replace("/\s*\}$/is"," \n\t}",trim($classdata));
            $classlist[] = "\n\t".$classdata;
        endforeach;
        foreach($usedata as $key=>$v):
            #if(strpos($key,'ScssPhp\ScssPhp\Exception')!==false)continue;
            if($v)$str .= "\n\tuse ".$key.";";
        endforeach;
        $str .= implode("",$classlist);
        $str .= "\n}";
        #$str = str_replace(" . \$e->getMessage()","",$str);
        #$str = str_replace("\$flags = [];","\$flags = [];\$lastNode=[];",$str);
        #$str = str_replace("@trigger_error(sprintf('The \"%s\" class is deprecated.', ServerException::class), E_USER_DEPRECATED);","",$str);
        $outlist[$name] = $str;
    endforeach;
    return implode("\n",$outlist);
}
function miniclass(string $class_path)
{
    $classpath = APPROOT.'class'.DIRECTORY_SEPARATOR;
    $str = "";
    $str .= "\n".mini_path($classpath.'Nenge'.DIRECTORY_SEPARATOR);
    $str .= "\n".mini_path($classpath.'lib'.DIRECTORY_SEPARATOR);
    $str .= "\n".mini_path($classpath.'table'.DIRECTORY_SEPARATOR);
    file_put_contents($class_path,"<?php\n/* all in one */".$str);
}
/**
 * 压缩一个类框架
 *  如 minipath('ScssPhp.php',WEBROOT.'scssphp-1.12.0\\src\\')
 * @param string $cache
 * @param string $path
 */
function mini_path2data(string $cache,string $path){
    file_put_contents(APPROOT.'cache'.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.$cache,"<?php\n".mini_path($path));
}