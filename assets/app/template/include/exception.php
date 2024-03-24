<style type="text/css">
    body{margin: 0px;background-color: #c5d6e5;padding: 1em;}
    .exception>.message b{color:#000;}
</style>
<div class="exception">
    <h1><?php echo Nenge\APP::app()->getLang($title); ?>(<?php if(!empty($code)): echo $code; endif;?>)</h1>
    <div class="message" style="background-color: #4096df;color:#fff;padding:1rem;">
        <?php echo $message;?>
    </div>
    <?php include(__DIR__.DIRECTORY_SEPARATOR.'debug.php'); ?>
</div>