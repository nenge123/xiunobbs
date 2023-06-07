<?php
    defined('XIUNO')||die();
    if(empty($myapp->data['settings']['attach_radio'])) $myapp->json(array('code'=>-1,'msg'=>$language['upload_unlock']));
    if(!empty($action[1])){
        if($action[1]=='post'&&!empty($myapp->data['user']['uid'])){
            #帖子图片附件
            $actionDo = !empty($action[2])?$action[2]:'';
            #登录用户
            if(!empty($myapp->data['group']['allowattach'])&&!empty($_FILES['image'])){
                #检查上传权限
                if(!empty($_FILES['image']['tmp_name'])){
                    #检查上传文件是否存在
                    $attachPath = $myapp->path->upload.'attach/';
                    $attachSite = $myapp->site->upload.'attach/';
                    $datapath = date($myapp['settings']['attach_dir_save_rule']).'/';
                    $myapp->mkdir($attachPath.$datapath);
                    $filePre = $datapath.$myapp->data['user']['uid'].'_';
                    $imageTypeArray = array(
                        1=>'.gif',
                        2=>'.jpg',
                        3=>'.png',
                        18=>'.webp'  
                    );
                    $imageList = [];
                    if(is_array($_FILES['image']['tmp_name'])){
                        #多文件上传
                        foreach($_FILES['image']['tmp_name'] as $a=>$b){
                            if(is_uploaded_file($b)){
                                #是否来自上传的文件
                                $imageInfo = getimagesize($b);
                                #检查是不是图片文件
                                if(!empty($imageInfo)&&!empty($imageTypeArray[$imageInfo[2]])){
                                    #确保文件类型在指定范围内
                                    $fileId = $filePre.uniqid().$imageTypeArray[$imageInfo[2]];
                                    if(move_uploaded_file($b,$attachPath.$fileId)){
                                        #移动文件
                                        $result = $myapp->DB('Insert','attach',array(
                                            'tid'=>0,
                                            'pid'=>0,
                                            'uid'=>$myapp->data['user']['uid'],
                                            'filesize'=>$_FILES['image']['size'][$a],
                                            'width'=>$imageInfo[0],
                                            'height'=>$imageInfo[1],
                                            'filename'=>$fileId,
                                            'orgfilename'=>$_FILES['image']['name'][$a],
                                            'filetype'=>'image',
                                            'create_date'=>$myapp->data['time'],
                                            'downloads'=>0,
                                            'credits'=>0,
                                            'golds'=>0,
                                            'rmbs'=>0,
                                            'isimage'=>1
                                        ));
                                        $_FILES['image']['tmp_name'][$a] = "";
                                        if(!empty($result)&&$result['line']){
                                            #返还我上传文明信息
                                            $imageList[$_FILES['image']['name'][$a]] = $attachSite.$fileId;
                                        }
                                    }
                                }
                            }
                        }
                        $myapp->json($imageList);
                    }else{
                        if(is_uploaded_file($_FILES['image']['tmp_name'])){
                            #是否来自上传的文件
                            $imageInfo = getimagesize($_FILES['image']['tmp_name']);
                            #检查是不是图片文件
                            if(!empty($imageInfo)&&!empty($imageTypeArray[$imageInfo[2]])){
                                #确保文件类型在指定范围内
                                $fileId = $filePre.uniqid().$imageTypeArray[$imageInfo[2]];
                                if(move_uploaded_file($_FILES['image']['tmp_name'],$attachPath.$fileId)){
                                    #移动文件
                                    $result = $myapp->DB('Insert','attach',array(
                                        'tid'=>0,
                                        'pid'=>0,
                                        'uid'=>$myapp->data['user']['uid'],
                                        'filesize'=>$_FILES['image']['size'],
                                        'width'=>$imageInfo[0],
                                        'height'=>$imageInfo[1],
                                        'filename'=>$fileId,
                                        'orgfilename'=>$_FILES['image']['name'],
                                        'filetype'=>'image',
                                        'create_date'=>$myapp->data['time'],
                                        'downloads'=>0,
                                        'credits'=>0,
                                        'golds'=>0,
                                        'rmbs'=>0,
                                        'isimage'=>1
                                    ));
                                    $_FILES['image']['tmp_name'] = "";
                                    if(!empty($result)&&$result['line']){
                                        #返还我上传文明信息
                                        $imageList[$_FILES['image']['name']] = $attachSite.$fileId;
                                        $myapp->json($imageList);
                                    }
                                }
                            }
                        }
                    }
                }
            }elseif($actionDo=='list'){
                #无文件上传返回未使用文件
                $result = $myapp->DB('fetchAll','attach',array('uid'=>$myapp->data['user']['uid'],'tid'=>0,'pid'=>0));
                if(!empty($result)){
                    $imageList = array();
                    foreach($result as $k=>$v){
                        $imageList[$v['orgfilename']] = array(
                            'src'=>$myapp->site->upload.'attach/'.$v['filename'],
                            'width'=>$v['width'],
                            'height'=>$v['height'],
                            'type'=>$v['filetype'],
                            'date'=>$myapp->F('date_format_local',$v['create_date']),
                            'pid'=>$v['pid'],
                        );
                    }
                    $myapp->json($imageList);
                }
            }
            $myapp->json(array('code'=>-1,'msg'=>$language['upload_unlock']));
        }
    }
    $myapp->exit();