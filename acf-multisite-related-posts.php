<?php
/**
 * Plugin Name:     ACF Multisite Related Posts
 * Plugin URI:      https://github.com/withfatpanda/acf-multisite-related-posts
 * Description:     Adds a field to Advanced Custom Fields that allows for loading related posts across sites in a WordPress Multisite installation
 * Author:          Aaron Collegeman <aaron@withfatpanda.com>
 * Author URI:      https://www.withfatpanda.com
 * Text Domain:     acf-multisite-related-posts
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         FatPanda_ACF_MultisiteRelatedPosts
 */

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_plugin_multisite_related_posts') ) :

class acf_plugin_multisite_related_posts {
  
  /*
  *  __construct
  *
  *  This function will setup the class functionality
  *
  *  @type  function
  *  @date  17/02/2016
  *  @since 1.0.0
  *
  *  @param n/a
  *  @return  n/a
  */
  
  function __construct() {
    
    // vars
    $this->settings = array(
      'version' => '1.0.0',
      'url'   => plugin_dir_url( __FILE__ ),
      'path'    => plugin_dir_path( __FILE__ )
    );
    
    
    // set text domain
    // https://codex.wordpress.org/Function_Reference/load_plugin_textdomain
    load_plugin_textdomain( 'acf-multisite_related_posts', false, plugin_basename( dirname( __FILE__ ) ) . '/lang' ); 
    
    
    // include field
    add_action('acf/include_field_types',   array($this, 'include_field_types')); // v5
    add_action('acf/register_fields',     array($this, 'include_field_types')); // v4

    add_action('wp_ajax_acf_multisite_related_posts_taxonomies', array($this, 'ajax_taxonomies') );
    add_action('wp_ajax_acf_multisite_related_posts_terms', array($this, 'ajax_terms') );
    
  }

  /**
   * Print some JSON output, set status header appropriately
   * @param  mixed  $contents Either a WP_Error, a Throwable, or arbitrary data;
   * if contents is an error-type object, automatically force status to 500.
   * @param  integer $status   [description]
   * @param  boolean $exit     [description]
   * @return [type]            [description]
   */
  protected function json($contents, $status = 200, $exit = true)
  {
    $out = [];

    if (is_wp_error($contents)) {
      $out['error'] = (object) [
        'code' => $contents->get_error_code(),
        'message' => $contents->get_error_message(),
      ];
      if ($status < 300) {
        $status = 500;
      }

    } else if ($contents instanceof \Throwable) {
      $out['error'] = (object) [
        'code' => $contents->getCode(),
        'message' => $contents->getMessage(),
      ];
      if ($status < 300) {
        $status = 500;
      }

    } else {
      $out = $contents;
    }

    status_header($status);

    $encoded = json_encode($out);

    echo $encoded;

    if ($exit) {
      exit;
    }

    return $encoded;
  }

  protected function get($name, $default = null)
  {
    return !empty($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
  }

  function ajax_taxonomies()
  {
    if (!current_user_can('edit_posts')) {
      $this->json(new WP_Error('auth', 'You are not authorized'), 403);
    }

    if (!$site = $this->get('site')) {
      $this->json(new \InvalidArgumentException("Argument missing: site"));
    }

    if (!$type = $this->get('type')) {
      $this->json(new \InvalidArgumentException("Argument missing: type")); 
    }

    switch_to_blog($site);

    $taxonomies = get_taxonomies(null, 'objects');

    $search = $this->get('search');
      
    $taxonomies = array_values(array_filter($taxonomies, function($tax) use ($type, $search) {
      if ($search) {
        if (stripos($tax->label, $search) === false && stripos($tax->name, $search) === false) {
          return false;
        } 
      }

      if (!in_array($type, $tax->object_type)) {
        return false;
      }

      return true;
    }));

    // XXX: this isn't working:
    usort($taxonomies, function($a, $b) {
      return strncasecmp($a->label, $b->label);
    });

    $this->json($taxonomies);
  }

  function ajax_terms()
  {
    if (!current_user_can('edit_posts')) {
      $this->json(new WP_Error('auth', 'You are not authorized'), 403);
    }

    if (!$taxonomies = $this->get('taxonomies')) {
      $this->json([]);
    }

    if (!$site = $this->get('site')) {
      $this->json(new \InvalidArgumentException("Argument missing: site"));
    }

    switch_to_blog($site);

    $terms = get_terms([ 'taxonomy' => $taxonomies ]);

    $search = $this->get('search');
      
    $terms = array_values(array_filter($terms, function($term) use ($search) {
      if ($search) {
        if (stripos($term->slug, $search) === false && stripos($term->name, $search) === false) {
          return false;
        } 
      }

      return true;
    }));
    
    $this->json($terms);
  }
  
  /*
  *  include_field_types
  *
  *  This function will include the field type class
  *
  *  @type  function
  *  @date  17/02/2016
  *  @since 1.0.0
  *
  *  @param $version (int) major ACF version. Defaults to false
  *  @return  n/a
  */
  
  function include_field_types( $version = false ) {
    
    // support empty $version
    if( !$version ) $version = 4;
    
    
    // include
    include_once('lib/acf-multisite-related-posts-utility.php');
    include_once('fields/base-acf-multisite-related-posts.php');
    include_once('fields/acf-multisite-related-posts-v' . $version . '.php');
    
  }
  
}


// initialize
new acf_plugin_multisite_related_posts();


// class_exists check
endif;