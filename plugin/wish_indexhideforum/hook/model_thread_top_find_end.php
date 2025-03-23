//<?
global $wish_indexhideforum;
if(!empty($threadlist) && !empty($wish_indexhideforum) && !empty($wish_indexhideforum['hide_forums']) && $wish_indexhideforum['also_hide_tops']=='yes'){
    //这种方法可以避免$key和fid不一致的意外
    foreach ($threadlist as $key => $item){
        if(in_array($item['fid'], $wish_indexhideforum['hide_forums'])){
            unset($threadlist[$key]);
        }
    }
    //也可以用这种方法，性能高了一点点
    /*foreach ($wish_indexhideforum['hide_forums'] as $hide_id){
        unset($threadlist[$hide_id]);
    }*/
}