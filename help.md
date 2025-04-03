### event-stream
> 默认情况下除了IIS,其他服务器软件都支持,  
> IIS:网站:配置编辑器->切换:system.webServer/handlers->编辑项->找到执行的PHP->responseBufferLimit->设置0,缺点是网站就彻底没了程序缓冲(content-length)  
> 浏览器情况下,除了IE不支持.