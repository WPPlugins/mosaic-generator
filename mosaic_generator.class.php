<?php
class mosaic_generator_class
{
    var $default_options;
    var $user_options;
    var $images_array;
    var $debug;

    function get_default_options()
    {
        if (!(is_array($this->default_options)))
        {
            $this->default_options = get_option('mosaic_generator_options');
        }
    }

    function get_images_array($options = null)
    {
        $images_array = null;
        $this->get_default_options();

        if (is_array($options))
        {
            $options = $this->correct_options($options);
        } else
        {
            $options = $this->default_options;
        }

        $common_images_array = get_option('mosaic_generator_images');
        if (is_array($common_images_array))
        {
            $cash_id = $options['size'];

            $images_array = $common_images_array[$cash_id];
            if (is_array($images_array))
            {
                if (count($images_array) < ($options['height_count'] * $options['width_count']))
                {
                    $images_array = $this->create_images_for_mosaic($options);
                }

            } else
            {
                $images_array = $this->create_images_for_mosaic($options);
            }
        } else
        {
            $images_array = $this->create_images_for_mosaic($options);
        }
        return $images_array;
    }

    function rgb2array($rgb)
    {
        //sscanf($color, "%2x%2x%2x", $red, $green, $blue);
        return array(base_convert(substr($rgb, 0, 2), 16, 10), base_convert(substr($rgb, 2, 2), 16, 10), base_convert(substr($rgb, 4, 2), 16, 10), );
    }

    function create_images_for_mosaic($options)
    {
        $this->get_default_options();
        if (is_array($options))
        {
            $options = $this->correct_options($options);
        } else
        {
            $options = $this->default_options;
        }

        $cash_dir = $this->construct_cash_dir($options, '/', 'img');
        $cash_dir_url = $this->construct_cash_dir($options, '/', 'img_url');
        $cash_dir_ext = $this->construct_cash_dir($options, '/', 'img_ext');
        clearstatcache();
        if (!is_dir(MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR))
        {
            if (!mkdir(MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR, 0777, true))
            {
                die('Failed to create folders for images...<br>Please check permissions on the directory image in plugin dir.');
            }
        }

        if (!is_dir($cash_dir))
        {
            if (!mkdir($cash_dir, 0777, true))
            {
                die('Failed to create folders for cash images...<br>Please check permissions on the directory image in plugin dir.');
            }
        }

        if (!is_dir($cash_dir_ext))
        {
            if (!mkdir($cash_dir_ext, 0777, true))
            {
                die('Failed to create folders for cash images...<br>Please check permissions on the directory image in plugin dir.');
            }
        }

        if (!is_file($cash_dir_ext . 'blank_img.jpg'))
        {
            $blank_img = imagecreatetruecolor($options['size'], $options['size']);
            $rgb_array = $this->rgb2array($options['blank_image_color']);
            $color = ImageColorAllocate($blank_img, $rgb_array[0], $rgb_array[1], $rgb_array[2]);
            imageFilledRectangle($blank_img, 0, 0, $options['size'], $options['size'], $color);
            if (imagejpeg($blank_img, $cash_dir_ext . 'blank_img.jpg'))
            {
            } else
            {
                die("Failed to create blank images... <br>Please check permissions on the directory image in plugin dir.");
            }
        } else
        {

        }

        $img_count = $options['height_count'] * $options['width_count'];

        $args = array('post_type' => 'attachment', 'numberposts' => -1, 'orderby' => 'rand', 'post_status' => null, 'post_parent' => null, 'post_mime_type' => array('image/jpeg'));
        //$posts_array = get_posts($args);

        //$args = array('post_type' => 'attachment', 'numberposts' => -1, 'orderby' => 'rand', 'post_status' => null, 'post_parent' => null);
        $attachment_array = get_posts($args); //        echo "<pre>";
        //echo count($attachment_array)."<br>";
        //die();
        $images_array = array();
        if ($attachment_array)
        {
            foreach ($attachment_array as $attachment)
            {
                $mosaic_generator_src_img_array = wp_get_attachment_image_src($attachment->ID, 'full');
                if ($attachment->post_parent > 0)
                {
                    $post_status = get_post_status($attachment->post_parent);
                    $title = get_the_title($attachment->post_parent);
                    $permalink = get_permalink($attachment->post_parent);
                } else
                {
                    $post_status = null;
                    $title = null;
                    $permalink = null;
                }
                if ($post_status == 'publish')
                {
                    if (!(empty($mosaic_generator_src_img_array[0])))
                    {
                        $tmp_path_parts = pathinfo($mosaic_generator_src_img_array[0]);
                        $tmp_abs_parts = $cash_dir . $tmp_path_parts['filename'] . '-' . $options['size'] . 'x' . $options['size'] . '.' . $tmp_path_parts['extension'];

                        if (!is_file($tmp_abs_parts))
                        {
                            $mosaic_generator_img_abs_path = $this->get_absolute_path($mosaic_generator_src_img_array[0]);
                            $mosaic_generator_new_img_abs_path = image_resize($mosaic_generator_img_abs_path, $options['size'], $options['size'], true, null, $cash_dir, 100);
                            if (is_wp_error($mosaic_generator_new_img_abs_path))
                            {
                                $error_string = $mosaic_generator_new_img_abs_path->get_error_message();
                            } else
                            {
                                $path_parts = pathinfo($mosaic_generator_new_img_abs_path);
                                $mosaic_generator_new_image_rel_path = $cash_dir_url . $path_parts['basename'];

                            }
                        } else
                        {
                            $path_parts = pathinfo($tmp_abs_parts);
                            $mosaic_generator_new_image_rel_path = $cash_dir_url . $path_parts['basename'];
                        }

                        $tmp_img_urls = array('img_url' => $mosaic_generator_new_image_rel_path);
                        if (!($permalink == null))
                        {
                            $tmp_img_urls['post_url'] = $permalink;
                        }
                        if (!($title == null))
                        {
                            $tmp_img_urls['post_title'] = $title;
                        }
                        array_push($images_array, $tmp_img_urls);
                    }
                }
            }
        }
        if (count($images_array) > 0)
        {
            $this->images_array[$options['size']] = $images_array;
            if (get_option('mosaic_generator_images'))
            {
                update_option('mosaic_generator_images', $this->images_array);
            } else
            {
                add_option('mosaic_generator_images', $this->images_array);
            }
        } else
        {
        }

        return $images_array; //die();
    }

    function get_absolute_path($relative_path)
    {
        $array_first_path = explode('wp-content', MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR);
        $array_second_path = explode('wp-content', $relative_path);
        $absolute_path = $array_first_path[0] . 'wp-content' . $array_second_path[1];

        return $absolute_path;
    }

    function view_options_page()
    {
        $this->get_default_options();

        if (isset($_POST['submit']))
        {
            $need_update = false;
            $error = false;
            $error_array = array(); // Check size
            $check_size_array = $this->check_size($_POST['mosaic_generator_options_size']);
            if ($check_size_array["error"])
            {
                $error = true;
                $error_array["size"] = $check_size_array["error_text"];
                $_POST['mosaic_generator_options_size'] = $this->default_options['size'];
            }

            // Check height
            $check_height_array = $this->check_height($_POST['mosaic_generator_options_height_count']);
            if ($check_height_array["error"])
            {
                $error = true;
                $error_array["height_count"] = $check_height_array["error_text"];
                $_POST['mosaic_generator_options_height_count'] = $this->default_options['height_count'];
            }

            // Check width
            $check_width_array = $this->check_width($_POST['mosaic_generator_options_width_count']);
            if ($check_width_array["error"])
            {
                $error = true;
                $error_array["width_count"] = $check_width_array["error_text"];
                $_POST['mosaic_generator_options_width_count'] = $this->default_options['width_count'];
            }

            // Check generating type
            $check_gt_array = $this->check_gt($_POST['mosaic_generator_options_generating_type']);
            if ($check_gt_array["error"])
            {
                $error = true;
                $error_array["generating_type"] = $check_gt_array["error_text"];
                $_POST['mosaic_generator_options_generating_type'] = $this->default_options['generating_type'];
            }

            if (isset($_POST['mosaic_generator_options_use_link']))
            {
                if ($_POST['mosaic_generator_options_use_link'] == 'on')
                {
                    $_POST['mosaic_generator_options_use_link'] = 1;
                } else
                {
                    $_POST['mosaic_generator_options_use_link'] = 0;
                }
            }

            // Check border size
            $check_border_size_array = $this->check_border_size($_POST['mosaic_generator_options_border_size']);
            if ($check_border_size_array["error"])
            {
                $error = true;
                $error_array["border_size"] = $check_border_size_array["error_text"];
                $_POST['mosaic_generator_options_border_size'] = $this->default_options['border_size'];
            }

            if ($error === false)
            {
                $this->update_options($_POST);
            }
            //die();
        } elseif (isset($_POST['create_images']))
        {
            update_option('mosaic_generator_options', $this->default_options);
            delete_option('mosaic_generator_images'); //update_option('mosaic_generator_images',array());
            $this->main_generate($this->default_options, true);
        } elseif (isset($_POST['regenerating']))
        {
            update_option('mosaic_generator_options', $this->default_options);
            $this->main_generate($this->default_options, true);
        } elseif (isset($_POST['save_view_css']))
        {
            $this->save_view_css($_POST['mosaic_generator_view_css']);
        } else
        {
            $this->update_options();
        }

?>
        <h1>Options page for Mosaic-generator v<?php echo MOSAIC_GENERATOR_VERSION;?></h1>
		<p>Official site: <a href="http://omelchuck.ru/mosaic-generator/">http://omelchuck.ru/mosaic-generator/</a></p>
        <?php

        echo $this->main_generate();

?>
        <br />
        <div>
        Paste this code between the end of "header" and "content" or in another place of template:<br>
        <pre><code>&lt;?php mosaic_generator();?&gt;</code></pre>
        </div>
        
        <div style="clear: both;"></div>
        If you specify a function without parameters it will use the default settings from this page.
        
        <div>
        <pre><code>&lt;?mosaic_generator(size, height, widtht, generating_type, border_size, color, use_link);?&gt;</code></pre>
        </div>
        <b>Function takes several parameters:</b>
<li> size - the size of a cell mosaic </li>
<li> height - the height of the mosaic (in the number of pictures) </li>
<li> widtht - mosaic width (in number of pictures) </li>
<li> generating_type - type of generation (div - in blocks or gd - one photo) </li>
<li> border_size - border size (in pixels) </li>
<li> color - color picture covers (in HTML format, for example FFFFF without #) </li>
<li> use_link - the use of references in pictures (1 or 0) </li>
        
        
        For example:</br>
        
        <pre><code class="php">&lt;div id=&quot;header&quot;&gt;any code...&lt;/div&gt;
&lt;?php echo mosaic_generator();?&gt;
&lt;div id=&quot;content&quot;&gt;any code...&lt;/div&gt;</code></pre>
        
        You can also paste shortcode <strong>&#091;mosaic_generator s=30 w=4 h=4 gt=div b=1 c=FFFFFF l=1&#093;</strong> in post.
        If you specify a shortcode without parameters it will use the default settings from this page.
        
<li> s - cell size tiles (in pixels) </li>
<li> h - the height of the mosaic (in the number of pictures) </li>
<li> w - the width of the mosaic (in the number of pictures) </li>
<li> gt - the type of generation (div - in blocks or gd - one photo) </li>
<li> b - border size (in pixels) </li>
<li> c - are color covers (in HTML format, for example FFFFF without grid) </li>
<li> l - the use of references in pictures (1 or 0) </li>
        
        <h2>Mosaic Generator default options.</h2>
        <div style="clear: both;"></div>        
        <div class="mosaic_generator_admin_forms">       
        <form name="mosaic_generator_options_form" method="post" action="<?php

        echo $_SERVER['PHP_SELF'] . '?page=' . MOSAIC_GENERATOR_FILE_BASENAME;

?>&updated=true">
                <div class="mosaic_generator_label">Image size:</div>
        		<input name="mosaic_generator_options_size" type="text" id="mosaic_generator_options_size" value="<?php

        echo $this->default_options['size'];

?>" size="3" /> px;
                <div style="clear: both;"></div>
                
<?php

        if (isset($error_array['size']))
        {
            echo '<div class="mosaic_generator_error_text">' . $error_array['size'] . '</div>';
        }

?>                                
                <div class="mosaic_generator_label">Number of images in height:</div>
        		<input name="mosaic_generator_options_height_count" type="text" id="mosaic_generator_options_height_count" value="<?php

        echo $this->default_options['height_count'];

?>" size="3" />
               <div style="clear: both;"></div>

<?php

        if (isset($error_array['height_count']))
        {
            echo '<div class="mosaic_generator_error_text">' . $error_array['height_count'] . '</div>';
        }

?> 
                
                <div class="mosaic_generator_label">Number of images in width:</div>
        		<input name="mosaic_generator_options_width_count" type="text" id="mosaic_generator_options_width_count" value="<?php

        echo $this->default_options['width_count'];

?>" size="3" />                                                            
                <div style="clear: both;"></div>

<?php

        if (isset($error_array['width_count']))
        {
            echo '<div class="mosaic_generator_error_text">' . $error_array['width_count'] . '</div>';
        }

?> 
                
                <div class="mosaic_generator_label">Border size (for 'image' generating type, for 'div' generating type use style.css):</div>
        		<input name="mosaic_generator_options_border_size" type="text" id="mosaic_generator_options_border_size" value="<?php

        echo $this->default_options['border_size'];

?>" size="3" /> px;                                                            
                <div style="clear: both;"></div>

<?php

        if (isset($error_array['border_size']))
        {
            echo '<div class="mosaic_generator_error_text">' . $error_array['border_size'] . '</div>';
        }

?> 

                <div class="mosaic_generator_label">Use link to posts from image:</div>
        		<input name="mosaic_generator_options_use_link" type="checkbox" id="mosaic_generator_options_use_link" 
                <?php

        if ($this->default_options['use_link'] == 1)
        {
            echo 'CHECKED';
        }

?>
                />

<div style="clear: both;"></div>

                <div class="mosaic_generator_label">Blank image color:</div>
        		<input name="mosaic_generator_options_blank_image_color" type="text" id="mosaic_generator_options_blank_image_color" value="<?php

        echo $this->default_options['blank_image_color'];

?>" size="6" />                                                            
                <div style="clear: both;"></div>
                                
                <hr />
                <div class="mosaic_generator_label">Type of generation:</div><div style="clear: both;"></div>        		    
                    <input name="mosaic_generator_options_generating_type" type="radio" value="div" <?php

        if ($this->default_options['generating_type'] == 'div')
        {
            echo 'checked="true"';
        }

?>/>Mosaic in div
                    <div style="clear: both;"></div>
<?php

        if (isset($error_array['generating_type']))
        {
            echo '<div class="mosaic_generator_error_text">' . $error_array['generating_type'] . '</div>';
        }

?> 
                                                                             
                    <input name="mosaic_generator_options_generating_type" type="radio" value="gd" <?php

        if ($this->default_options['generating_type'] == 'gd')
        {
            echo 'checked="true"';
        }

?>/>Mosaic in Image
                    <div style="clear: both;"></div>                     
                <div style="clear: both;"></div>                                
                <hr />
        		<input class="mosaic_generator_button" type="submit" name="submit" value="<?php

        _e('Save options')

?>" />
            </form>                
            <form name="mosaic_generator_create_img_form" method="post" action="<?php

        echo $_SERVER['PHP_SELF'] . '?page=' . MOSAIC_GENERATOR_FILE_BASENAME;

?>">            
                <input class="mosaic_generator_button" type="submit" name="create_images" value="<?php

        _e('Recreate the images')

?>" />            
            </form>
            
            <form name="mosaic_generator_regenerating_form" method="post" action="<?php

        echo $_SERVER['PHP_SELF'] . '?page=' . MOSAIC_GENERATOR_FILE_BASENAME;

?>">            
                <input class="mosaic_generator_button" type="submit" name="regenerating" value="<?php

        _e('Re-Generate Mosaic')

?>" />            
            </form>            
        </div>
        
        <div class="mosaic_generator_css_forms">
            <h2>Edit "style.css".</h2>            
            <form name="mosaic_generator_view_css_forms" method="post" action="<?php

        echo $_SERVER['PHP_SELF'] . '?page=' . MOSAIC_GENERATOR_FILE_BASENAME;

?>&updated=true">
                <textarea class="mosaic_generator_textarea" name="mosaic_generator_view_css" id="mosaic_generator_admin_css"><?php

        echo $this->get_view_css();

?></textarea>
                
                <div style="clear: both;"></div>
                <input class="mosaic_generator_button" type="submit" name="save_view_css" value="<?php

        _e('Save style.css')

?>" />
            </form>            
        </div>                                           
        <?php

    }

    function main_generate($options = null, $flag_regenerate = false)
    {
        $this->get_default_options();
        if (is_array($options))
        {

            $options = $this->correct_options($options); //            echo "<pre>";
            //            print_r($options);
            //            echo "</pre>";
        } else
        {
            $options = $this->default_options; //            echo "<pre>";
            //            print_r($options);
            //            echo "</pre>";
        }

        $images_array = $this->get_images_array($options);
        if ($flag_regenerate)
        {
            $this->create_images_for_mosaic($options);
        }

        $cash_dir = $this->construct_cash_dir($options, '/', 'img');
        $cash_dir_url = $this->construct_cash_dir($options, '/', 'img_url');
        $cash_dir_ext = $this->construct_cash_dir($options, '/', 'img_ext');
        $cash_dir_ext_url = $this->construct_cash_dir($options, '/', 'url_img_ext');
        $blank_img_url = $this->construct_cash_dir($options, '', 'blank_url');
        $blank_img_abs = $this->construct_cash_dir($options, '', 'blank_abs');

        if ($this->debug)
        {
            echo "<b>main_generate(): </b> Абсолютный Каталог кэша: <b>$cash_dir</b><br>";
            echo "<b>main_generate(): </b> URL картинок: <b>$cash_dir_url</b><br>";
            echo "<b>main_generate(): </b> Каталог служебных файлов: <b>$cash_dir_ext</b><br>";
            echo "<b>main_generate(): </b> Абсолютный адрес картинки-пустышки: <b>$blank_img_abs</b><br>";
        }

        if ($this->default_options['generating_type'] == 'div')
        {
            $main_img_width = $options['size'] * $options['width_count'] + ($options['width_count'] + 1) * $options['border_size'];
            $main_img_height = $options['size'] * $options['height_count'] + ($options['height_count'] + 1) * $options['border_size'];
            $div_size = 'height: ' . ($options['size'] + $options['border_size']) . 'px; width: ' . ($options['size'] + $options['border_size']) . 'px;';
            $div_size_left = $div_size . ' margin-left: ' . $options['border_size'] . 'px;';
            if ($flag_regenerate)
            {

                $code = "<div class='mosaic_generate_main' style='width: " . $main_img_width . "px; height: " . $main_img_height . "px'>" . "\n";
                for ($j = 1; $j <= $options['height_count']; $j++)
                {
                    for ($i = 1; $i <= $options['width_count']; $i++)
                    {
                        if (count($images_array) == 0)
                        {
                            if ($i == 1)
                            {
                                if (!(empty($blank_img_url)))
                                {
                                    $code .= '<div class = "mosaic_generator_last_in_line mosaic_generator_img" style="' . $div_size_left . '"><img src="' . $blank_img_url . '"/></div>' . "\n";
                                }
                            } else
                            {
                                if (!(empty($blank_img_url)))
                                {
                                    $code .= '<div class = "mosaic_generator_img" style="' . $div_size . '"><img src="' . $blank_img_url . '"/></div>' . "\n";
                                }
                            }
                        } else
                        {
                            $mosaic_generator_rnd_index = mt_rand(0, count($images_array) - 1);
                            $tmp_image_src = $images_array[$mosaic_generator_rnd_index];
                            unset($images_array[$mosaic_generator_rnd_index]);
                            $images_array = array_values($images_array);
                            if ($i == 1)
                            {
                                if (!(empty($tmp_image_src)))
                                {
                                    if ($options['use_link'] == 1)
                                    {
                                        if (isset($tmp_image_src['post_url']) && isset($tmp_image_src['post_title']))
                                        {
                                            $code .= '<div class = "mosaic_generator_last_in_line mosaic_generator_img" style="' . $div_size_left . '"><a href="' . $tmp_image_src['post_url'] . '"><img src="' . $tmp_image_src['img_url'] .
                                                '" title="' . $tmp_image_src['post_title'] . '"/></a></div>' . "\n";
                                        } elseif (isset($tmp_image_src['post_url']))
                                        {
                                            $code .= '<div class = "mosaic_generator_last_in_line mosaic_generator_img" style="' . $div_size_left . '"><a href="' . $tmp_image_src['post_url'] . '"><img src="' . $tmp_image_src['img_url'] .
                                                '"/></a></div>' . "\n";
                                        }

                                    } else
                                    {
                                        if (isset($tmp_image_src['post_title']))
                                        {
                                            $code .= '<div class = "mosaic_generator_last_in_line mosaic_generator_img" style="' . $div_size_left . '"><img src="' . $tmp_image_src['img_url'] . '" title="' . $tmp_image_src['post_title'] .
                                                '"/></div>' . "\n";
                                        } else
                                        {
                                            $code .= '<div class = "mosaic_generator_last_in_line mosaic_generator_img" style="' . $div_size_left . '"><img src="' . $tmp_image_src['img_url'] . '"/></div>' . "\n";
                                        }
                                    }
                                }
                            } else
                            {
                                if (!(empty($tmp_image_src)))
                                {
                                    if ($options['use_link'] == 1)
                                    {
                                        if (isset($tmp_image_src['post_url']) && isset($tmp_image_src['post_title']))
                                        {
                                            $code .= '<div class = "mosaic_generator_img" style="' . $div_size . '"><a href="' . $tmp_image_src['post_url'] . '"><img src="' . $tmp_image_src['img_url'] . '" title="' . $tmp_image_src['post_title'] .
                                                '"/></a></div>' . "\n";

                                        } elseif (isset($tmp_image_src['post_url']))
                                        {
                                            $code .= '<div class = "mosaic_generator_img" style="' . $div_size . '"><a href="' . $tmp_image_src['post_url'] . '"><img src="' . $tmp_image_src['img_url'] . '"/></a></div>' . "\n";
                                        }

                                    } else
                                    {
                                        if (isset($tmp_image_src['post_title']))
                                        {
                                            $code .= '<div class = "mosaic_generator_img" style="' . $div_size . '"><img src="' . $tmp_image_src['img_url'] . '" title="' . $tmp_image_src['post_title'] . '"/></div>' . "\n";
                                        } else
                                        {
                                            $code .= '<div class = "mosaic_generator_img" style="' . $div_size . '"><img src="' . $tmp_image_src['img_url'] . '"/></div>' . "\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $code .= '</div>' . "\n";
                $code .= '<div style="clear: both;"></div>';

                $f = fopen($cash_dir_ext . 'code.txt', "w");
                fwrite($f, $code);
                fclose($f); //die();
            } else
            {
                if (is_file($cash_dir_ext . 'code.txt'))
                {
                    $code = $this->get_ready_code($options);
                } else
                {
                    $code = $this->main_generate($options, true);
                }
            }
        } else
        {
            if ($flag_regenerate)
            {

                $border_size = $options['border_size'];
                $main_img_width = $options['size'] * $options['width_count'] + ($options['width_count'] + 1) * $border_size;
                $main_img_height = $options['size'] * $options['height_count'] + ($options['height_count'] + 1) * $border_size; //$blank_image_src_abs = $this->get_absolute_path($blank_img_url);
                $blank_src = imagecreatefromjpeg($blank_img_abs);
                if ($this->debug)
                {
                    echo "<b>main_generate(): </b>Размер gd (Ш,В): <b>$main_img_width, $main_img_height</b><br>";
                }

                $main_img = imagecreatetruecolor($main_img_width, $main_img_height);
                $white_color = ImageColorAllocate($main_img, 255, 255, 255);
                imageFilledRectangle($main_img, 0, 0, $main_img_width, $main_img_height, $white_color); //                echo "<pre>";

                if (count($images_array) > 0)
                {
                    for ($j = 1; $j <= $options['height_count']; $j++)
                    {
                        for ($i = 1; $i <= $options['width_count']; $i++)
                        {
                            if (count($images_array) == 0)
                            {
                                imagecopy($main_img, $blank_src, ($i - 1) * $options['size'] + ($i * $border_size), ($j - 1) * $options['size'] + ($j * $border_size), 0, 0, $options['size'], $options['size']);
                            } else
                            {
                                $mosaic_generator_rnd_index = mt_rand(0, count($images_array) - 1);
                                $tmp_image_src = $images_array[$mosaic_generator_rnd_index]['img_url'];
                                $tmp_image_src_abs = $this->get_absolute_path($tmp_image_src);
                                unset($images_array[$mosaic_generator_rnd_index]);
                                $images_array = array_values($images_array);
                                $src = imagecreatefromjpeg($tmp_image_src_abs);
                                imagecopy($main_img, $src, ($i - 1) * $options['size'] + ($i * $border_size), ($j - 1) * $options['size'] + ($j * $border_size), 0, 0, $options['size'], $options['size']);
                            }
                        }
                    }
                    $code = $this->paste_code($code, $main_img, $options);
                }


                imagejpeg($main_img, $cash_dir_ext . 'main_mosaic_img.jpg'); //echo $cash_dir_ext_url.'main_mosaic_img.jpg';
                $code = '<div class = "mosaic_generate_main">';
                $code .= '<img src="' . $cash_dir_ext_url . 'main_mosaic_img.jpg' . '" alt="Mosaic Generator (http://omelchuck.ru/mosaic-generator/)"/>';
                $code .= '</div>';
                $f = fopen($cash_dir_ext . 'code.txt', "w");
                fwrite($f, $code);
                fclose($f);
            } else
            {
                if (is_file($cash_dir_ext . 'code.txt'))
                {
                    $code = $this->get_ready_code($options);
                } else
                {
                    $code = $this->main_generate($options, true);
                }
            }
        }
        ;
        return $code;
    }

    function get_ready_code($options)
    {
        $cash_dir_ext = $this->construct_cash_dir($options, '/', 'img_ext'); //$blank_img_url = $this->construct_cash_dir($options, '', 'blank_url');
        $file_array = file($cash_dir_ext . 'code.txt');
        if (is_array($file_array))
        {
            $code = implode("", $file_array);
        } else
        {
            $code = $this->main_generate($options, true); //$code = $this->get_ready_code($options);
        }
        return $code;
    }

    //check and correct user_options
    function correct_options($user_options)
    {
        $this->get_default_options(); //check and correct size
        $new_options = array(); //Временно без проверки цвета
        if (isset($user_options["blank_image_color"]))
        {
            $new_options["blank_image_color"] = $user_options["blank_image_color"];
        } else
        {
            $new_options["blank_image_color"] = $this->default_options["blank_image_color"];
        }

        if (isset($user_options["size"]))
        {
            $check_size_array = $this->check_size($user_options["size"]);
            if ($check_size_array["error"] === true)
            {
                $new_options["size"] = $this->default_options["size"];
            } else
            {
                $new_options["size"] = $user_options["size"];
            }
        } else
        {
            $new_options["size"] = $this->default_options["size"];
        }

        //check and correct height_count
        if (isset($user_options["height_count"]))
        {
            $check_height_array = $this->check_height($user_options["height_count"]);
            if ($check_height_array["error"] === true)
            {
                $new_options["height_count"] = $this->default_options["height_count"];
            } else
            {
                $new_options["height_count"] = $user_options["height_count"];
            }
        } else
        {
            $new_options["height_count"] = $this->default_options["height_count"];
        }

        //check and correct width_count
        if (isset($user_options["width_count"]))
        {
            $check_widtht_array = $this->check_width($user_options["width_count"]);
            if ($check_widtht_array["error"] === true)
            {
                $new_options["width_count"] = $this->default_options["width_count"];
            } else
            {
                $new_options["width_count"] = $user_options["width_count"];
            }
        } else
        {
            $new_options["width_count"] = $this->default_options["width_count"];
        }

        //check and correct generating type
        if (isset($user_options["generating_type"]))
        {
            $check_gt_array = $this->check_gt($user_options["generating_type"]);
            if ($check_gt_array["error"] === true)
            {
                $new_options["generating_type"] = $this->default_options["generating_type"];
            } else
            {
                $new_options["generating_type"] = $user_options["generating_type"];
            }
        } else
        {
            $new_options["generating_type"] = $this->default_options["generating_type"];
        }

        //check and correct border size for gd type of generating
        if (isset($user_options["border_size"]))
        {
            $check_border_size_array = $this->check_border_size($user_options["border_size"]);
            if ($check_border_size_array["error"] === true)
            {
                $new_options["border_size"] = $this->default_options["border_size"];
            } else
            {
                $new_options["border_size"] = $user_options["border_size"];
            }
        } else
        {
            $new_options["border_size"] = $this->default_options["border_size"];
        }

        if (isset($user_options["use_link"]))
        {
            if (is_numeric($user_options["use_link"]))
            {
                $new_options["use_link"] = $user_options["use_link"];
            } else
            {
                $new_options["use_link"] = $this->default_options["use_link"];
            }
        } else
        {
            $new_options["use_link"] = $this->default_options["use_link"];
        }

        return $new_options;
    }

    private function paste_code($code, $main_img, $options)
    {
        @imagejpeg($main_img, MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR . $this->construct_cash_dir($options, '/') . $options['generating_time'] . 'main_mosaic_img.jpg');
        $code .= '<img src="' . MOSAIC_GENERATOR_PLUGIN_IMAGES_URL . $this->construct_cash_dir($options, '/') . $options['generating_time'] . 'main_mosaic_img.jpg' .
            '" alt="Mosaic Generator (http://omelchuck.ru/mosaic-generator/)"/>';
        return $code;
    }

    // Check size
    function check_size($size)
    {
        $error = false;
        $error_text = '';
        if (is_numeric($size))
        {
            if ($size > 0)
            {
                //$need_update = true;
            } else
            {
                $error = true;
                $error_text = ' - Size must be more then zero';
            }
        } else
        {
            $error = true;
            $error_text = ' - Size must be numeric' . '<br>';
        }
        $check_array = array("error" => $error, "error_text" => $error_text);
        if ($this->debug)
        {
            if ($check_array['error'] === false)
            {
                echo "<b>check_size(): </b>Проверка размера пройдена...<br>";
            } else
            {
                echo "<b>check_size(): </b>Проверка размера не пройдена...<br>";
            }
        }

        return $check_array;
    }

    // Check height
    function check_height($height)
    {
        $error = false;
        $error_text = ''; //$need_update = false;

        if (is_numeric($height))
        {
            if ($height > 0)
            {

            } else
            {
                $error = true;
                $error_text = ' - Height count must be more then zero';
            }
        } else
        {
            $error = true;
            $error_text = ' - Height count must be numeric';
        }
        $check_array = array("error" => $error, "error_text" => $error_text);
        if ($this->debug)
        {
            if ($check_array['error'] === false)
            {
                echo "<b>check_height(): </b>Проверка высоты пройдена...<br>";
            } else
            {
                echo "<b>check_height(): </b>Проверка высоты не пройдена...<br>";
            }
        }

        return $check_array;
    }

    // Check width
    function check_width($width)
    {
        $error = false;
        $error_text = '';
        if (is_numeric($width))
        {
            if ($width > 0)
            {

            } else
            {
                $error = true;
                $error_text = ' - Width count must be more then zero';
            }
        } else
        {
            $error = true;
            $error_text = ' - Width count must be numeric';
        }
        $check_array = array("error" => $error, "error_text" => $error_text);
        if ($this->debug)
        {
            if ($check_array['error'] === false)
            {
                echo "<b>check_width(): </b>Проверка ширины пройдена...<br>";
            } else
            {
                echo "<b>check_width(): </b>Проверка ширины не пройдена...<br>";
            }
        }

        return $check_array;
    }

    // Check generating type
    function check_gt($gt)
    {
        $error = false;
        $error_text = ''; //$need_update = false;

        if ($gt == 'div' || $gt == 'gd')
        {

        } else
        {
            $error = true;
            $error_text = ' - Generating type = div or gd';
        }
        $check_array = array("error" => $error, "error_text" => $error_text);
        if ($this->debug)
        {
            if ($check_array['error'] === false)
            {
                echo "<b>check_gt(): </b>Проверка типа генерации пройдена...<br>";
            } else
            {
                echo "<b>check_gt(): </b>Проверка типа генерации не пройдена...<br>";
            }
        }

        return $check_array;
    }

    // Check border size
    function check_border_size($border_size)
    {

        $error = false;
        $error_text = ''; //$need_update = false;

        if (is_numeric($border_size))
        {
            if ($border_size >= 0)
            {
                //$need_update = true;
            } else
            {
                $error = true;
                $error_text = ' - Border size count must be more or equal zero';
            }
        } else
        {
            $error = true;
            $error_text = ' - Border size count must be numeric';
        }
        $check_array = array("error" => $error, "error_text" => $error_text);
        if ($this->debug)
        {
            if ($check_array['error'] === false)
            {
                echo "<b>check_border_size(): </b>Проверка размера границы пройдена...<br>";
            } else
            {
                echo "<b>check_border_size(): </b>Проверка размера границы не пройдена...<br>";
            }
        }

        return $check_array;
    }


    function get_admin_css()
    {
        $file_array = @file(MOSAIC_GENERATOR_PLUGIN_CSS_DIR . 'admin_style.css');
        if (is_array($file_array))
        {
            $content = implode("", $file_array);
        } else
        {
            $content = 'Error, no file or no access to admin_style.css';
        }
        return $content;
    }

    function save_admin_css($text)
    {
        $f = fopen(MOSAIC_GENERATOR_PLUGIN_CSS_DIR . 'admin_style.css', "w");
        fwrite($f, $text);
        fclose($f);
    }

    function get_view_css()
    {
        $file_array = @file(MOSAIC_GENERATOR_PLUGIN_CSS_DIR . 'style.css');
        if (is_array($file_array))
        {
            $content = implode("", $file_array);
        } else
        {
            $content = 'Error, no file or no access to style.css';
        }
        return $content;
    }

    function save_view_css($text)
    {
        $f = fopen(MOSAIC_GENERATOR_PLUGIN_CSS_DIR . 'style.css', "w");
        fwrite($f, $text);
        fclose($f);
        $this->main_generate($this->default_options, true);
    }

    function construct_cash_dir($options, $end_string = "", $type = 'none')
    {
        if ($type == 'img')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR . $options['size'] . $end_string;
        } elseif ($type == 'img_url')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_URL . $options['size'] . $end_string;
        } elseif ($type == 'blank_url')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_URL . $options['size'] . '/' . $options['generating_type'] . '_' . $options['height_count'] . '_' . $options['width_count'] . '_' . $options['border_size'] . '_' .
                $options['use_link'] . '_' . $options['blank_image_color'] . '/' . 'blank_img.jpg';
        } elseif ($type == 'blank_abs')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR . $options['size'] . '/' . $options['generating_type'] . '_' . $options['height_count'] . '_' . $options['width_count'] . '_' . $options['border_size'] . '_' .
                $options['use_link'] . '_' . $options['blank_image_color'] . '/' . 'blank_img.jpg';
        } elseif ($type == 'id_img_array')
        {
            $res = $options['size'];
        } elseif ($type == 'img_ext')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR . $options['size'] . '/' . $options['generating_type'] . '_' . $options['height_count'] . '_' . $options['width_count'] . '_' . $options['border_size'] . '_' .
                $options['use_link'] . '_' . $options['blank_image_color'] . $end_string;
        } elseif ($type == 'url_img_ext')
        {
            $res = MOSAIC_GENERATOR_PLUGIN_IMAGES_URL . $options['size'] . '/' . $options['generating_type'] . '_' . $options['height_count'] . '_' . $options['width_count'] . '_' . $options['border_size'] . '_' .
                $options['use_link'] . '_' . $options['blank_image_color'] . $end_string;
        } else
        {
            $res = $options['generating_type'] . '_' . $options['size'] . '_' . $options['height_count'] . '_' . $options['width_count'] . '_' . $options['border_size'] . $end_string;
        }
        return $res;
    }

    function delete_all_blanks($dir)
    {
        $files = scandir($dir); //echo $dir;
        foreach ($files as & $value)
        {

            $pos = strpos($value, 'blank_img_');
            if (!($pos === false))
            {
                $this->remove_dir($dir . $value);
            }
        }
    }

    function remove_dir($dir_name)
    {
        if (!file_exists($dir_name))
        {
            return false;
        }
        if (is_file($dir_name))
        {
            return unlink($dir_name);
        }
        $dir = dir($dir_name);
        while (false !== $entry = $dir->read())
        {
            if ($entry == '.' || $entry == '..')
            {
                continue;
            }
            $this->remove_dir($dir_name . DIRECTORY_SEPARATOR . $entry);
        }
        $dir->close();
        return rmdir($dir_name);
    }

    function update_options($post_array = null)
    {

        if ($this->debug)
        {
            echo "------------------update_options----------------------------------<br>";
        }
        $this->get_default_options();
        $need_update_options = false; //$need_regenerate_img = false;
        $new_default_options = $this->default_options;
        if (is_array($post_array))
        {

            if (isset($post_array['mosaic_generator_options_size']))
            {
                $new_default_options['size'] = $post_array['mosaic_generator_options_size'];
            }

            if (isset($post_array['mosaic_generator_options_height_count']))
            {
                $new_default_options['height_count'] = $post_array['mosaic_generator_options_height_count'];
            }

            if (isset($post_array['mosaic_generator_options_width_count']))
            {
                $new_default_options['width_count'] = $post_array['mosaic_generator_options_width_count'];
            }

            if (isset($post_array['mosaic_generator_options_generating_type']))
            {
                $new_default_options['generating_type'] = $post_array['mosaic_generator_options_generating_type'];
            }

            if (isset($post_array['mosaic_generator_options_border_size']))
            {
                $new_default_options['border_size'] = $post_array['mosaic_generator_options_border_size'];
            }

            if (isset($post_array['mosaic_generator_options_blank_image_color']))
            {
                $new_default_options['blank_image_color'] = $post_array['mosaic_generator_options_blank_image_color'];
            }

            if (isset($post_array['mosaic_generator_options_use_link']))
            {
                if ($post_array['mosaic_generator_options_use_link'] == '1')
                {
                    $new_default_options['use_link'] = 1;
                } else
                {
                    $new_default_options['use_link'] = 0;
                }
            } else
            {
                $new_default_options['use_link'] = 0;
            }

            $new_default_options = $this->correct_options($new_default_options); //            echo "<pre>";

            if ($new_default_options['height_count'] != $this->default_options['height_count'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['size'] != $this->default_options['size'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['width_count'] != $this->default_options['width_count'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['generating_type'] != $this->default_options['generating_type'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['blank_image_color'] != $this->default_options['blank_image_color'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['use_link'] != $this->default_options['use_link'])
            {
                $need_update_options = true;
            }

            if ($new_default_options['border_size'] != $this->default_options['border_size'])
            {
                $need_update_options = true;
            }

            if ($need_update_options)
            {
                $this->default_options = $new_default_options;
                $need_update_options = true;
            }

        } else
        {

            $need_update_options = false;
            if (!(isset($this->default_options['size'])))
            {
                $this->default_options['size'] = 10;
                $need_update_options = true;
            }
            if (!(isset($this->default_options['height_count'])))
            {
                $this->default_options['height_count'] = 3;
                $need_update_options = true;
            }
            if (!(isset($this->default_options['width_count'])))
            {
                $this->default_options['width_count'] = 10;
                $need_update_options = true;
            }
            if (!(isset($this->default_options['generating_type'])))
            {
                $this->default_options['generating_type'] = 'div';
                $need_update_options = true;
            }
            if (!(isset($this->default_options['border_size'])))
            {
                $this->default_options['border_size'] = 1;
                $need_update_options = true;
            }
            if (!(isset($this->default_options['blank_image_color'])))
            {
                $this->default_options['blank_image_color'] = 'ECECEC';
                $need_update_options = true;
            }
            if (!(isset($this->default_options['use_link'])))
            {
                $this->default_options['use_link'] = false;
                $need_update_options = true;
            }
        }
        //die();
        if ($need_update_options)
        {

            update_option('mosaic_generator_options', $this->default_options);
            $cash_dir_ext = $this->construct_cash_dir($this->default_options, '/', 'img_ext');
            $this->remove_dir($cash_dir_ext . 'code.txt');
            $this->create_images_for_mosaic($this->default_options);
        } else
        {

        }
    }

    function deactivate()
    {
        delete_option('mosaic_generator_options');
        delete_option('mosaic_generator_images');
        $this->remove_dir(MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR);
    }

    function activate()
    {
        if (get_option('mosaic_generator_options'))
        {
            delete_option('mosaic_generator_options');
        }
        $mosaic_generator_options = array();
        $mosaic_generator_options['size'] = 50;
        $mosaic_generator_options['height_count'] = 3;
        $mosaic_generator_options['width_count'] = 3;
        $mosaic_generator_options['generating_type'] = 'div';
        $mosaic_generator_options['border_size'] = 1;
        $mosaic_generator_options['use_link'] = 0;
        $mosaic_generator_options['blank_image_color'] = 'ECECEC';
        $mosaic_generator_options['generating_time'] = time();
        add_option('mosaic_generator_options', $mosaic_generator_options);
    }
}

?>