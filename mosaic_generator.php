<?php

/*
Plugin Name: Mosaic Generator
Plugin URI: http://omelchuck.ru
Description: Creates mosaic from all images of the site and places it in any part of website.
Version: 1.0.5
Author: ODiN
Author URI: http://omelchuck.ru/mosaic-generator/
*/

/*  Copyright 2012  ODiN  (email : odn {at} live.ru)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (! defined('MOSAIC_GENERATOR_VERSION'))
    define('MOSAIC_GENERATOR_VERSION', '1.0.5.1');

if (! defined('MOSAIC_GENERATOR_PLUGIN_DIR'))
    define('MOSAIC_GENERATOR_PLUGIN_DIR', plugin_dir_path(__file__));

if (! defined('MOSAIC_GENERATOR_PLUGIN_CSS_DIR'))
    define('MOSAIC_GENERATOR_PLUGIN_CSS_DIR', MOSAIC_GENERATOR_PLUGIN_DIR.'css/');

if (! defined('MOSAIC_GENERATOR_FILE_BASENAME'))
    define('MOSAIC_GENERATOR_FILE_BASENAME', basename(__file__));

if (! defined('MOSAIC_GENERATOR_PLUGIN_BASENAME'))
    define('MOSAIC_GENERATOR_PLUGIN_BASENAME', plugin_basename(__file__));

if (! defined('MOSAIC_GENERATOR_PLUGIN_DIRNAME'))
    define('MOSAIC_GENERATOR_PLUGIN_DIRNAME', dirname(MOSAIC_GENERATOR_PLUGIN_BASENAME));

if (! defined('MOSAIC_GENERATOR_PLUGIN_URL'))
    define('MOSAIC_GENERATOR_PLUGIN_URL', plugin_dir_url(__file__));

if (! defined('MOSAIC_GENERATOR_PLUGIN_IMAGES_URL'))
    define('MOSAIC_GENERATOR_PLUGIN_IMAGES_URL', MOSAIC_GENERATOR_PLUGIN_URL.'images/');

if (! defined('MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR'))
    define('MOSAIC_GENERATOR_PLUGIN_IMAGES_DIR', MOSAIC_GENERATOR_PLUGIN_DIR.'images/');

global $mosaic_generator;
register_activation_hook(__file__, 'mosaic_generator_activate');
register_deactivation_hook(__file__, 'mosaic_generator_deactivate');

add_action('admin_init', 'mosaic_generator_admin_init');
add_action('admin_menu', 'mosaic_generator_admin_menu');
wp_register_style('mosaic_generator_view_style', plugins_url('css/style.css', __file__));
add_action('wp_enqueue_scripts', 'mosaic_generator_style');
add_shortcode('mosaic_generator', 'mosaic_generator_sc');

if (! class_exists('mosaic_generator_class'))
    require_once (MOSAIC_GENERATOR_PLUGIN_DIR.'mosaic_generator.class.php');
$mosaic_generator = new mosaic_generator_class();

function mosaic_generator_style()
{
    wp_enqueue_style('mosaic_generator_view_style');
}

function mosaic_generator($size = 50, $height_count = 3, $width_count = 3, $generating_type = "div", $border_size = 1, $blank_image_color = "FFFFFF", $use_link = 0)
{
    global $mosaic_generator;
    $user_options = array(
    'size' => $size,
    'height_count' => $height_count,
    'width_count' => $width_count,
    'generating_type' => $generating_type,
    'border_size' => $border_size,
    'blank_image_color' => $blank_image_color,
    'use_link' => $use_link);
    
    return $mosaic_generator->main_generate($user_options);
}

function mosaic_generator_sc($atts, $content = null)
{
    extract(shortcode_atts(array("s" => 50, "h" => 3, "w" => 3, "gt" => "div", "b" => 1, "c" => "FFFFFF", "l" => 0), $atts));
    return mosaic_generator($s, $h, $w, $gt, $b, $c, $l);
}

function mosaic_generator_admin_init()
{
    wp_register_style('mosaic_generator_admin_style', plugins_url('css/admin_style.css', __file__));
}

function mosaic_generator_activate()
{
    global $mosaic_generator;
    $mosaic_generator->activate();
}

function mosaic_generator_deactivate()
{
    global $mosaic_generator;
    $mosaic_generator->deactivate();
}

function mosaic_generator_admin_menu()
{
    if (function_exists('add_options_page')) {
        $mosaic_generator_admin_page = add_options_page('Mosaic Generator options', 'Mosaic Generator', 'manage_options', MOSAIC_GENERATOR_FILE_BASENAME, 'mosaic_generator_options_page');
        add_action('admin_print_styles-'.$mosaic_generator_admin_page, 'mosaic_generator_enqueue_admin_styles');
    }
}

function mosaic_generator_enqueue_admin_styles()
{
    wp_enqueue_style('mosaic_generator_admin_style');
    wp_enqueue_style('mosaic_generator_view_style');
}

function mosaic_generator_options_page()
{
    global $mosaic_generator;
    $mosaic_generator->view_options_page();
}

?>