<?php
// page-templates/cities-list.php
defined('ABSPATH') || exit;

/**
 * Template Name: Cities List
 * Template Post Type: page
 */

 get_header();
 ?>
 <div class="cities-list-container">
     <div class="container">
         <h1><?php _e('Cities by Country', 'storefront-child'); ?></h1>
         <?php
         do_action('cities_list_before_table');
         
         $results = Cities_Repository::get_cities_with_countries();
 
         get_template_part('template-parts/cities/list', null, ['results' => $results]);
 
         do_action('cities_list_after_table');
         ?>
     </div>
 </div>
 <?php
 get_footer();
