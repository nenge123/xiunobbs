<?php
echo admin_tab_active(array(
    'set' => array('url' => url('plugin-setting-zls_sitemap-set'), 'text' => '基本设置'),
    'generate' => array('url' => url('plugin-setting-zls_sitemap-generate'), 'text' => '站点地图生成'),
    'about' => array('url' => url('plugin-setting-zls_sitemap-about'), 'text' => '插件说明')
), $action);
?>