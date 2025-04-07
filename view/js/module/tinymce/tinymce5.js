import {myeditorbase} from './tinymce_base.js';
import LANG from './tinymce5_langs_zh-cn.js';

/**
 * @var {NengeCommon}
 */
export class myeditor extends myeditorbase {
	LANG = LANG;
	langName = 'en';
	/**
	 * 编辑器全屏是9999,因此限制他10
	 */
	csstext = [
		'.tox .tox-menu{z-index:99999999999;}',
		'.tox-statusbar .tox-statusbar__branding{display:none !important;}'
	];
	tinymce_dir = 'https://unpkg.com/tinymce@5.10.9/';
	csslinks = [
		//new URL('plugin/huux_tinymce/tinymce/style.css', location).href
	];
	/**
	 * @param {HTMLTextAreaElement} elm 
	 */
	constructor(elm) {
		super(elm);
		this.inclueFile = [
			'tinymce.min.js',
			this.isMobile ? 'themes/mobile/theme.min.js' : 'themes/silver/theme.min.js',
			'icons/default/icons.js',
			//'plugins/emoticons/js/emojis.js'
		]
		this.plugins = [
			"advlist", "anchor", "autolink", "autoresize", "autosave","charmap",
			"code", "codesample", "directionality",
			"fullscreen", "help", "hr",
			"image", "imagetools",
			"insertdatetime", "link", "lists",
			"media", "nonbreaking", "noneditable", "pagebreak", "paste", "preview", "quickbars", "save", "searchreplace","tabfocus", "table","textpattern",
			"visualblocks", "visualchars",
			"wordcount"
		];
		for (let link of this.plugins) {
			this.inclueFile.push('plugins/' + link + '/plugin.min.js');
		}
		this.init();
	}
	async init() {
		const E = this;
		this.theme = document.documentElement.getAttribute('data-bs-theme');
		const skin = E.theme == 'dark' ? 'oxide-dark' : 'oxide';
		const tinyMCE = await this.initEditor();
		const toolbar_groups = {
			hlist: {
				text: '标题',
				items: 'h1 h2 h3 h4 h5 h6',
			},
			ziti: {
				text: '字体',
				items: 'bold superscript subscript formatselect | fontselect | fontsizeselect',
			},
			duiqi: {
				text: '对齐',
				items: 'alignleft aligncenter alignright alignjustify',
			}
		};
		return await tinyMCE.init({
			target: this.elm,
			plugins: this.plugins,
			toolbar_mode: 'floating',
			base_url: this.tinymce_dir,
			promotion: false,
			license_key: 'gpl',
			language:E.langName,
			contextmenu: false, // 禁用编辑器的右键菜单@c
			skin,
			skin_url: E.tinymce_dir + 'skins/ui/' + skin,
			mobile: {
				menubar: true,
				toolbar_mode: 'floating',
			},
			toolbar_groups,
			toolbar: ['myattach  hlist ziti duiqi link table forecolor backcolor | numlist removeformat hr blockquote fullscreen'], // 界面按钮
			async images_upload_handler(blobInfo, success, failure) {
				console.log(blobInfo, success, failure);
				const url = await E.file_upload(blobInfo.blob(),()=>{}).catch(e=>'');
				//不能返回警告  因为他会不删除插入的BASE54图片
				if(!url)E.alert('上传失败');
				success(url);
			},
			fontsize_formats: '12px 14px 16px 18px 24px 36px 48px 56px 72px',
			font_formats: '微软雅黑=Microsoft YaHei,Helvetica Neue,PingFang SC,sans-serif;苹果苹方=PingFang SC,Microsoft YaHei,sans-serif;宋体=simsun,serif;仿宋体=FangSong,serif;黑体=SimHei,sans-serif;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats;知乎配置=BlinkMacSystemFont, Helvetica Neue, PingFang SC, Microsoft YaHei, Source Han Sans SC, Noto Sans CJK SC, WenQuanYi Micro Hei, sans-serif;小米配置=Helvetica Neue,Helvetica,Arial,Microsoft Yahei,Hiragino Sans GB,Heiti SC,WenQuanYi Micro Hei,sans-serif',
			paste_data_images: true, // 粘贴图片必须开启
			quickbars_insert_toolbar: true,
			min_height: 500, // 最小高度
			setup: (editor) =>E.setup(editor),
		});
	}
};
