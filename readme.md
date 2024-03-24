使用说明
========
### 模块手册 ###
- [插件模块](doc/plugin.md)
- [数据库类处理](doc/db.md)
- [SCSS样式](doc/scss.md)

### 安装与注意事项 ###
- 数据库
    - settings 字段名如果含有`array_` 且值为字符串,那么值会被为是数组并且用`,`隔开.
- 插件
	- 命名空间,插件目录不要同时存在`aa-bb`,`aa_bb`,`aa#bb`,因为三者命名空间均为`aa_bb`,
	> 避免冲突.以作者ID_插件名
	- 类初始化,除了`common.class.php`会自动调用,其他独立类原则上创建
		```php
			#plugin\demo_test\test.class.php
			namespace plugin\demo_test;
			class test {
				use \lib\static_app;
				#函数P('demo_test/test')调用此方法必须含有 否则产生调用异常
			}
		```
- PHP扩展与安装
	- mysqli
	- xml
	- mbstring
	- curl
	- openssl
	```sh
	#WSL/Linux/Ubuntu
	#安装apache
	apt install apache2
	a2enmod rewrite
	a2enmod ssl
	#安装php
	apt install software-properties-common
	add-apt-repository ppa:ondrej/php
	apt install php8.0 php8.0-
	#apt install [php版本]-[扩展名]
	apt install php8.0-mysqli
	apt install php8.0-xml
	apt install php8.0-mbstring
	apt install php8.0-curl
	apt install php8.0-openssl
	apt install php8.0-fpm libapache2-mod-fcgid
	a2enmod proxy_fcgi sentenvif
	a2enconf php8.0-fpm
	a2enmod php8.0
	service apache2 restart
	#绑定一个IPV6域名AAAA记录 ::1 就可访问本地网络
	```
- 权限需求
	- Linux/Ubuntu
	```sh
	#sudo chmod 755 [目录]
	sudo chmod 755 -R .
	sudo chmod 755 ./assets/css/
	sudo chmod 755 ./assets/app/cache/data/
	sudo chmod 755 ./assets/app/cache/template/
	#可选 插件允许网站修改
	sudo chmod 777 -R ./assets/plugin/
	```
	- IIS 7.5++
	```cmd
	#icacls [目录] /c /grant "IIS AppPool\[应用程序池名称]":(CI)(R,W,D)
	#全局权限
	icacls . /c /grant "IIS AppPool\DefaultAppPool":(CI)(OI)(R,RD)
	icacls ./assets/css /c /grant "IIS AppPool\DefaultAppPool":(CI)(OI)(W)
	icacls ./assets/app/cache /c /grant "IIS AppPool\DefaultAppPool":(CI)(OI)(W)
	icacls ./upload /t /c /grant "IIS AppPool\DefaultAppPool":(CI)(OI)(W)
	icacls ./plugin /t /c /grant "IIS AppPool\DefaultAppPool":(CI)(OI)(W)
	#内部需要写权限的(R)->(R,W,D) 读写删,
	#如果文件权限继承混乱,在/c 前加入/t可以遍历目录添加权限

	```
### 常用技巧 ###

- HTML脚本:原则上不要`JavaScript`代码片段出现在页面中.  
临时操作`css->style='color:red;...'`.  
而是应该用`scss`精准定位其样式.  
    > `JavaScript`有限的为其增删一个css名.通过`not`语法切换样式效果!
    ```scss
    /* .active 的样式*/
    tag.active{}
    /*不含特定属性[class] 多个之间逗号隔开*/
    tag:not([class]){}
    /*特定属性[class]不含,不含active 的样式*/
    tag:not([class~='active']){}
    /*特定属性[data-class]不等于active 的样式*/
    tag:not([data-class='active']){}
    /*不含 active 的样式*/
    tag:not(.active){}
    /*不含active .abc 的样式*/
    tag:not(.active,.abc){}
    ```
- `css`编写原则,能用`tag>tag2`就优先使用.
    > 最大程度上抑制`CSS`之间`交叉污染`.通过`not`语法确保样式唯一性.
- `html`规则,如果特定对象总是要初始化,建议使用自定义`html`,如`<my-elm>`,然后`JavaScript`注册一个自定义`html`注册函数.
    > 这样`<my-elm>`一旦出现就会自动初始化,而无需检测判断.
    ```javascript
    customElements.define('my-elm',class extends HTMLElement {        
        connectedCallback(...a) {
            /**
             * dom文档树中载入时触发一次
             * 特别注意如果dom没完全加载 this.innerHTML值为空,仅仅可读取attributes
             * 避免dom没有完全加载问题,脚本第一在my-elm之后出现,或者dom加载完毕才注册.
             */
        }
    });
    ```

- 反盗版规则:开发模式下用`scss`,而发布时完全可以使用生成的`css`,这样可以最大限度防止被窃取成功.
    > 如果支持zend加密,甚至可以把生成的模板加密. 


> 前台

- [Javascript模块手册](md/javascript.md)


## 使用资源
- JavaScript
    - tinymce.zip 编辑器
    > https://tiny.cloud/
    - wasm-im.zip javscript版ImageMagic
    > https://github.com/mk33mk333/wasm-im/
    - webp_encoder.zip gif to webp  
    > https://github.com/xiaozhuai/webp_encoder/ 
    - modernGif.js gif分解出每帧
    > https://github.com/qq15725/modern-gif 
    - zip.min.js zip解压压缩
    > https://github.com/gildas-lormeau/zip.js/
    - extract7z.zip 7zip解压
    - libunrar.min.zip rar4解压

- PHP类
    - PHPMailer 邮件发送
    > https://github.com/PHPMailer/PHPMailer  
    - ScssPhp scss转css
    > https://github.com/scssphp/scssphp 