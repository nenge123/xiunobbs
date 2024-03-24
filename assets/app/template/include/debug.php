<?php
if(empty($myapp)):
    $myapp = Nenge\APP::app();
endif;
?>

    <style type="text/css">
        details.debug-list summary{color:#1776c9;font-weight:bold;font-size:1.2rem;}
        details.debug-list summary b{color:red;}
        details.debug-list>ul{font-size:12px;color:blue;padding-bottom:1rem}
        details.debug-list>ul li .x{color:#eb991f;}
        details.debug-list>ul li .y{color:red;}
        details.debug-list>ul li .z{color:#1776c9;}
    </style>
    <div class="debug">
        <?php if(defined('DEBUG')&&!empty($sqldata = $myapp->getSql())): ?>

        <h5>[SQL]</h5>
        <ul><?php foreach($sqldata as $v): ?>

            <li><?php echo $v['time'];?>ms <?php echo $v['sql'];?></li><?php endforeach; ?>

        </ul><?php if(!empty($myapp->plugin['require'])):?>

        <h5>[<?=$myapp->getLang('require_plugin')?>]</h5>
        <ul><?php foreach($myapp->plugin['require'] as $key=>$value):?>
            
            <li><b><?=$key?></b><?=$value?></li><?php endforeach;?>

        </ul><?php endif;?><?php endif;if(isset($traceList)): ?>

        <details class="debug-list" open>
            <summary>Trace<b>(<?php echo count($traceList);?>)</b></summary>
            <ul><?php foreach($traceList as $list): ?>

                <li><?php if(!empty($list['file'])): ?>

                    <span><?php echo $list['file']; ?></span><?php if(!empty($list['line'])): ?>

                    <b class="x">(<?php echo $list['line']; ?>)</b><?php endif;endif;if(!empty($list['class'])): ?>

                    <b><?php echo $list['class']; ?></b>
                    <b class="y">::</b><?php endif;if(!empty($list['func'])): ?>

                    <span class="z"><?php echo $list['func']; ?></span>
                    <b>(</b><?php if(!empty($list['param'])): ?><?php echo $list['param']; ?><?php endif; ?><b>)</b><?php endif; ?>

                </li><?php endforeach; ?>

            </ul>
        </details>

        <?php endif; ?>
        <?php if(defined('DEBUG')&&$includeList = get_included_files()): ?>

        <details class="debug-list">
            <summary>File<b>(<?php echo count($includeList);?>)</b> <?php echo $myapp->getLang('const'),':',ceil(1000*(microtime(1) - $myapp->data['microtime'])).'ms'; ?></summary>    
            <ul><?php foreach($includeList as $list): ?>

                <li><?php echo $myapp->safePath($list); ?></li><?php endforeach; ?>

            </ul>
        </details><?php endif; ?><?php if(!empty($myapp->data['microtime'])): ?><?php endif;?>

    </div>
