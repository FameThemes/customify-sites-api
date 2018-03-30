<?php
/*
Plugin Name: Customify Sites REST API
Plugin URI:  http://customifysites.com/
Description: Add REST API end point for customify demo importer
Author: shrimp2t
Author URI: http://customifysites.com/
Version: 0.0.1
Text Domain: customify-sites-api
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

function add_api_end_point(){
    register_rest_route( 'wp/v2', '/sites', array(
        'methods' => 'GET',
        'callback' => 'customify_site_get_sites',
    ) );
}
add_action( 'rest_api_init', 'add_api_end_point' );

/**
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#arguments
 *
 * @param $request
 * @return array
 */
function customify_site_get_sites ( $request ){

    $paged = absint( $request->get_param( 'paged' ) );
    $cat = sanitize_text_field( $request->get_param( 'cat' ) );
    $tag = sanitize_text_field( $request->get_param( 'tag' ) );
    $search = sanitize_text_field( $request->get_param( 's' ) );
    //$parameters = $request->get_query_params();

    $the_query = new WP_Query( array(
        'posts_per_page' => 20,
        'paged' => $paged,
        'post_parent' => 0,
        'category_name' => $cat,
        'tag' => $tag,
        's' => $search
    ) );

    $_posts = $the_query->get_posts();
    $posts =  array();
    foreach( $_posts as $p ) {
        $thumbnail_url=  get_the_post_thumbnail_url( $p );
        $post_tags =  wp_get_post_tags( $p->ID, array( 'fields' => 'slug' ) );
        $post_cats =  wp_get_post_terms( $p->ID, 'post_category', array( 'fields' => 'slug' ) );
        $posts[] = array(
            'id' => $p->ID,
            'title' => $p->post_title,
            'slug' => $p->post_name,
            'desc' => $p->post_content,
            'excerpt' => $p->post_excerpt,
            'thumbnail_url' => $thumbnail_url,
            'plugins' => get_post_meta( $p->ID, '_site_plugins', true ),
            'demo_url' => get_post_meta( $p->ID, '_site_demo_url', true ),
            'tags' => is_wp_error( $post_tags ) ?  array() : $post_tags,
            'categories' =>  is_wp_error( $post_cats ) ?  array() : $post_cats,
        );
    }

    $categories = array();
    $tags = array();
    $terms = get_terms( 'category', array(
        'hide_empty' => true,
        'lang' => 'en', // use language slug in the query
        'number' => 5,
    ) );
    if ( ! is_wp_error( $terms ) ) {
        foreach ($terms as $t) {
            $categories[$t->slug] = array(
                'slug'  => $t->slug,
                'name'  => $t->name,
                'id'    => $t->term_id,
                'count' => $t->count,
            );
        }
    }

    $terms = get_terms( 'post_tag', array(
        'hide_empty' => true,
        'number' => 5,
        'lang' => 'en', // use language slug in the query
    ) );
    if ( ! is_wp_error( $terms ) ) {
        foreach ($terms as $t) {
            $tags[$t->slug] = array(
                'slug'  => $t->slug,
                'name'  => $t->name,
                'id'    => $t->term_id,
                'count' => $t->count,
            );
        }
    }

    $data = array(
        'total' => $the_query->found_posts,
        'max_num_pages' => $the_query->max_num_pages,
        'paged' => $paged,
        'categories' => $categories,
        'tags' => $tags,
        'posts' => $posts
    );
    return $data;
}