<?php
// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

if( !class_exists('base_acf_field_multisite_related_posts') ) :

abstract class base_acf_field_multisite_related_posts extends acf_field {

  function __construct($settings = null) 
  {
    parent::__construct();
  }


  

}



// class_exists check
endif;