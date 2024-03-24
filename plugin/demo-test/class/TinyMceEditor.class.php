<?php

namespace plugin;
use Nenge\APP;

class tinymceEditor
{
    public function router_thread_allowpost()
    {
        $myapp = APP::app();
        if (isset($_GET['attachlist'])) {
            if (!empty($myapp->data['user']['uid'])) $myapp->json(DB::t('attach')->get_unuse_list($myapp->data['user']['uid']));
        }else{
            $time = defined('DEBUG')?$myapp->data['time']:$myapp->data['version'];
            $attach = 0;
            $access = $myapp->get_access($myapp->data['router'][1]);
            if(!empty($access['allowattach'])){
                $attach = 1;
            }
            $myapp->data['header_js'][] = $myapp['site']['plugin'] . 'TinyMceEditor/js/TinyMceEditor.js?mode=fastpost&attach='.$attach.'&_t'.$time;
        }
    }
    public function router_thread_allowpost_post()
    {
        $myapp = APP::app();
        if(!empty($_POST['removeaid'])){
            $result = DB::t('attach')->fetch(array('aid'=>intval($_POST['removeaid']),'uid'=>$myapp->data['user']['uid']));
            if(empty($result)){
                return array('result'=>'error','attach'=>-1);
            }else{
                return DB::t('attach')->remove($result);
            }
        }else if (!empty($_FILES['attchfile'])) {
            $insertData = array('uid' => $myapp->data['user']['uid']);
            if (!empty($settings['attach_rmbs']) && !empty($_POST['rmbs'])) {
                $insertData['rmbs'] = intval($settings['attach_rmbs'] > $_POST['rmbs'] ? $_POST['rmbs'] : $settings['attach_rmbs']);
            }
            if (!empty($settings['attach_golds']) && !empty($_POST['golds'])) {
                $insertData['golds'] = intval($settings['attach_golds'] > $_POST['golds'] ? $_POST['golds'] : $settings['attach_golds']);
            }
            if (!empty($settings['attach_credits']) && !empty($_POST['credits'])) {
                $insertData['credits'] = intval($settings['attach_credits'] > $_POST['credits'] ? $_POST['credits'] : $settings['attach_credits']);
            }
            if (!empty($_POST['comment'])) {
                $insertData['comment'] = substr($_POST['comment'], 0, 100);
            }
            return DB::t('attach')->save_attach($_FILES['attchfile'], $insertData);
        }else if(!empty($_POST['message'])){

        }
    }
}
