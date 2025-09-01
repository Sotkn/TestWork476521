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
         
         $cities_repo_with_temp = new CitiesRepositoryWithTemp();
         $results = $cities_repo_with_temp->get_cities_with_countries_and_temp();
 
         get_template_part('template-parts/cities/list-with-search', null, ['results' => $results]);
 
         do_action('cities_list_after_table');
         ?>
     </div>
 </div>
 <?php
 get_footer();
