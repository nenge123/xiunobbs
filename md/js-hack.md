javascript API
========

- 图片压缩为webp,GIF只会压缩第一帧 优点处理迅速
> `buff` 属性 `Uint8ClampedArray`,`ImageBitmap`,`Uint8Array`
```javascript
await Nenge.addJS(Nenge.JSpath+'common_webp.js?'+Nenge.time);
var buff = await Nenge.FetchItem('test.gif');
Nenge.image2webp(buff,500,200);
```

- 图片imageMagic 支持GIF转webp动画 处理动画很慢
> `buff` 属性 `Uint8Array`,`maxWidth`,`maxHeight`为缩放参数,就是大于这个大小就按比例缩放
```javascript
await Nenge.addJS(Nenge.JSpath+'common_gif2webp.js?'+Nenge.time);
var buff = await Nenge.FetchItem('test.gif');
Nenge.gif2webp(buff,200,200);
Nenge.gif2webp(buff);
```