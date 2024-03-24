javascript API
========
> common.js 核心函数

- `Nenge.FetchItem({...})` 读取网站数据,偏向持久存储下载,二进制处理
    - `url` 目标地址
    - `type` 返回类型 blob:文件,text:文本,json,head:仅仅读取http文件头,默认值:arrayBuffer,返回一个Uint8Array二进制数据.
    - `unpack` 如果下载目标是rar4/zip/7z文件,将会进行解压
    - `store` indexedDB数据库的表名,设置后将会储存下载数据,再次使用会从数据库读取,而不会再次下载
    - `key` 当store启用,此项为表中键名.否则根据url参数获取,对于非文件路径,最好设置
    - `version` 当使用store,如果数据库版本不一致,会重新下载网站数据.
    - `progress(per, current, total)` 下载进度回调函数
    - `success(data,headers)` 成功下载函数回调,此方法data与 `await Nenge.FetchItem`结果一样.
- `Nenge.ajax({...})` ajax方式读取网站html/json数据以及上传数据,获取数据,json处理,此函数自动附加get参数:inajax
    - `url` 目标地址
    - `type` ***(可选)***,一般情况无需设置,会根据网站的返回的`content-type:mime`变化而变化.
    - `progress(per, current, total)` 下载进度回调函数基本上与上面一致,默认值为text,
    - `success(data,headers)` 成功下载函数回调,此方法data与 `await Nenge.ajax`结果一样.
    - `postProgress(current, total)` 上传数据进度回调函数 
- `Nenge.addJS(data,cb,isCss)` 异步导入一个js或者css
- `Nenge.getStore('libjs')`打开一个indexedDB表,默认有两个表`libjs,myfile`
    - `get(name)` 获取一个表中键名中的数据
    - `data(name)` 在get返回数据中只返回contents
    - `put(name,data,option)` 写入一个数据
    - `save(name,data,option)` 写入一个含有信息的数据,如{contents:data,type:...,version:...},option中可以设置version,version作用为版本管理.一边更新
- `Nenge.ROOT` 网站目录
- `Nenge.JSpath` 网站js目录