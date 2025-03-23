ipaccess_inc($longip, 'read_thread');
if( ipaccess_check($longip, 'read_thread') === false ) {
message(1,'您今日的查看帖子数量已达到上限。');
}