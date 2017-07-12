<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

class acf_multisite_related_posts_utility {

  /**
   * Query and attach to the given field value the posts
   * that are specified by the other settings therein.
   * 
   * @param  [type] $value   [description]
   * @param  [type] $post_id [description]
   * @param  [type] $field   [description]
   * @return [type]          [description]
   */
  function format_value( $value, $post_id, $field ) {

    $value->posts = [];

    if (!empty($value->terms)) {

      @list($orderBy, $order) = explode(',', $value->order);

      $args = [
        'posts_per_page' => $value->limit,
        'orderby' => $orderBy,
        'order' => $orderBy !== 'rand' ? $order : null,
        'tax_query' => [ 'relation' => $value->relation ],
        'post_type' => $value->type,
      ];

      $typed = [];

      foreach($value->terms as $term) {
        if (empty($typed[$term->taxonomy])) {
          $typed[$term->taxonomy] = [ $term->term_id ];
        } else {
          $typed[$term->taxonomy][] = $term->term_id;
        }
      }

      foreach($typed as $taxonomy => $list) {
        $args['tax_query'][] = [
          'taxonomy' => $taxonomy,
          'field' => 'term_id',
          'terms' => $list
        ];
      }

      switch_to_blog($value->site);

      $value->query = new WP_Query($args);

      $value->posts = $value->query->get_posts();

      restore_current_blog();

    }
    
    // return
    return $value;

  }


}