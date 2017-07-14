=== Mosaic Generator ===
Contributors: ODiN
Donate link: http://omelchuck.ru/mosaic-generator/
Tags: logo, image, media, mosaic, header, head
Requires at least: 3.2
Tested up to: 3.9
Stable tag: 1.0.5.1

Plugin creates and places mosaic from all images on your blog in any part of website.

== Description ==
= About =
Plugin creates mosaic from all images of the site and places it in any part of website. You can configure all options. You can generate mosaic in html code with some images or in one big images.

= Коротко о плагине Mosaic generator =
Плагин создает мозайку из изображений Вашего сайта. Вы можете настраивать его под верстку.

= Recent Releases =
* Version 1.0.5 Fixed bug with generating mosaic

= Features =
* Using HTML &lt;map&gt; Tag in "gd" type of generation for links on post.
* Adding time settings to automatically remake mosaic

== Installation ==
= Installation =
* Visit http://omelchuck.ru/mosaic-generator/
* Upload plugin files to the `/wp-content/plugins/` directory.
* Activate the plugin through the 'Plugins' menu in WordPress.
* Paste code between the end of "header" and "content" or in another place of your templates: 
<pre><code class="php">&lt;?php mosaic_generator(size, height, width, genrating_type, border_size, color, use_link);?&gt;</code></pre>
- size - size of pictures (px)
- height - height in count of image
- widtht - widtht in count of image
- generating_type - type of generation (div or gd — in one image)
- border_size - size of border (px)
- color - color of blank image (in html format FFFFF)
- use_link - use link in images (1 or 0)

* Use shortcode [mosaic_generator s=20, h=3, w=3, gt='div', b=1, c='FFFFFF', l=1] for paste mosaic into post
s - size of pictures (px)
h - height in count of image
w - widtht in count of image
gt - type of generation (div or gd — in one image)
b - size of border (px)
c - color of blank image (in html format FFFFF)
l - use link in images (1 or 0)

= Установка =
* Посетите страницу http://omelchuck.ru/mosaic-generator/
* Загрузите файлы плагина в директорию /wp-content/plugins/
* Активируйте плагин в панели администратора.<br>
* Вставьте код в нужное место шаблона.
<pre><code class="php">&lt;?php mosaic_generator();?&gt;</code></pre>

* size — размер ячейки мозаики (в пикселях)
* height — высота мозаики (в количестве картинок)
* widtht — ширина мозаики (в количестве картинок)
* generating_type — тип генерации (div — в блоках или gd — одной картинкой)
* border_size — размер границы (в пикселях)
* color — цвет картинки заглушки (в HTML формате, например FFFFF без решетки)
* use_link — использование ссылок в картинках (1 или 0)

== Frequently Asked Questions ==

== Changelog ==

= Version 1.0.5 (April 21, 2014) =
* I woke up

= Version 1.0.5 (October 24, 2012) =
* Fixed bugs with generation of mosaic

= Version 1.0.5 (October 24, 2012) =
* Fixed bugs with generation of mosaic

= Version 1.0.4 (October 24, 2012) =
* Fixed bugs with generation of mosaic 

= Version 1.0.3 (October 23, 2012) =
* Fixed bugs: now images takes only from publish posts
* Fixed error on options page

= Version 1.0.2 (October 20, 2012) =
* Fixed many bugs related to various restrictions hosting
* Seriously optimized code
* Added the possibility to place on the same page several mosaics with different parameters
* Added ability to specify the parameters in the short code or function
* Added the ability to use the link to the mosaic image
* Added the ability to set the background color of empty images if not enough to create a complete image mosaic 

= Version 1.0.1 (August 23, 2012) =
* Fixed a bug with updating when changing mosaic of images in width or height
* Added shortcode [mosaic_generator] 

= Version 1.0 (August 21, 2012) =
* Initial release

== License ==
Mosaic Generator plugin is copyright © 2012 with by ODiN.