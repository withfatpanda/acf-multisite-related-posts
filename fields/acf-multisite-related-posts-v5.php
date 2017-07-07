<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_field_multisite_related_posts') ) :


class acf_field_multisite_related_posts extends base_acf_field_multisite_related_posts {
	
	
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct( $settings ) {
		
		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/
		
		$this->name = 'multisite_related_posts';
		
		
		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/
		
		$this->label = __('Related Posts (Multisite)', 'acf-multisite_related_posts');
		
		
		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/
		
		$this->category = 'relational';
		
		
		/*
		*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
		*/
		
		$this->defaults = array(
			'font_size'	=> 14,
		);
		
		
		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('multisite_related_posts', 'error');
		*/
		
		$this->l10n = array(
			'error'	=> __('Error! Please enter a higher value', 'acf-multisite_related_posts'),
		);
		
		
		/*
		*  settings (array) Store plugin settings (url, path, version) as a reference for later use with assets
		*/
		
		$this->settings = $settings;
		
		
		// do not delete!
   	parent::__construct();
    	
	}
	
	
	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field_settings( $field ) {
		
		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name (font_size)
		*/
		
		// acf_render_field_setting( $field, array(
		// 	'label'			=> __('Font Size','acf-multisite_related_posts'),
		// 	'instructions'	=> __('Customise the input font size','acf-multisite_related_posts'),
		// 	'type'			=> 'number',
		// 	'name'			=> 'font_size',
		// 	'prepend'		=> 'px',
		// ));

	}
	
	
	
	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field( $field ) {

		global $post;
		
		$sites = get_sites([ 'number' => 10000 ]);

		$currentPostLabels = get_post_type_labels($post);

		$fieldValue = !empty($field['value']) ? json_decode($field['value']) : new stdClass;

		$orderOptions = [
			'post_date_gmt,desc' => 'descending order, by post date',
			'post_date_gmt,asc' => 'ascending order, by post date',
			'post_title,asc' => 'ascending order, by title',
			'post_title,desc' => 'descending order, by title',
			'random' => 'random order',
		];
		
		// echo '<pre>';
		// 	print_r( $labels );
		// echo '</pre>';
		
		if (empty($fieldValue->limit)) {
			$fieldValue->limit = 3;
		}

		if (empty($fieldValue->order)) {
			$fieldValue->order = 'post_date_gmt,desc';
		}

		if (empty($fieldValue->site)) {
			$fieldValue->site = $sites[0]->blog_id;
		}

		?>
			<input data-toggle="settings" type="hidden" name="<?php echo esc_attr($field['name']) ?>" value="<?php echo esc_attr($field['value']) ?>">

			<p>
				Browse <b>Taxonomies</b> and select <b>Terms</b> from
					<select class="inline" data-toggle="sites">
						<?php foreach($sites as $site): switch_to_blog($site->blog_id); ?>
							<option value="<?php echo esc_attr($site->blog_id) ?>" <?php if ($site->blog_id == $fieldValue->site) echo 'selected' ?>>
								<?php echo esc_html(get_bloginfo('name')) ?>
							</option>
						<?php restore_current_blog(); endforeach; ?>	
					</select>
				to relate to this <b><?php echo $currentPostLabels->singular_name ?></b>.
			</p>

			<table style="width:100%; border-spacing:0; border:0; margin: 0;">
				<tbody>
					<tr>
						<td width="33%" valign="top">
							<div data-toggle="taxonomies" class="filterable">
								<img class="wait" src="<?php echo $this->image('wait.gif') ?>">
								<input type="search" placeholder="Search Taxonomies...">
								<select multiple size="8" disabled placeholder="Choose a site"></select>
							</div>
						</td>
						<td width="33%" valign="top">	
							<div data-toggle="terms" class="filterable">
								<img class="wait" src="<?php echo $this->image('wait.gif') ?>">
								<input type="search" placeholder="Search Terms...">
								<select multiple size="8" disabled placeholder="Choose at least one taxonomy"></select>
							</div>
						</td>
						<td width="33%" valign="top">	
							<div data-toggle="selected" class="filterable">
								<input type="text" value="Selected:" readonly style="border-color:transparent; background-color:transparent; box-shadow:none; font-weight:bold;">
								<select multiple size="8" disabled placeholder="0 terms selected"></select>
							</div>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<td>
						<button class="button" data-action="clear-taxonomies" disabled>
							<svg class="icon icon-blocked"><use xlink:href="#icon-blocked"></use></svg>
							Clear Selection
						</button>
					</td>
					<td>
						<button class="button" data-action="pick" disabled>
							<svg class="icon icon-arrow-right"><use xlink:href="#icon-arrow-right"></use></svg>
							Pick
						</button>
					</td>
					<td>
						<button class="button" data-action="trash" disabled>
							<svg class="icon icon-bin"><use xlink:href="#icon-bin"></use></svg>
							Remove
						</button>
					</td>
				</tfoot>
			</table>

			<p style="margin-bottom:0; padding-bottom:0;">
				Display 
				<select class="inline" data-toggle="limit">
					<?php for($i = 1; $i <= 100; $i++) { ?>
						<option value="<?php echo $i ?>" <?php if ($i == $fieldValue->limit) echo 'selected' ?>>
							<?php echo $i ?>
						</option>
					<?php } ?>
				</select>
				<select class="inline" data-toggle="type">
					<option value="post">Posts</option>
				</select>
				sorted in
				<select class="inline" data-toggle="order">
					<?php foreach($orderOptions as $value => $label) { ?>
						<option value="<?php echo esc_attr($value) ?>" <?php if ($value === $fieldValue->order) echo 'selected' ?>>
							<?php echo esc_html($label) ?>
						</option>
					<?php } ?>
				</select>
			</p>

			<svg style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
				<defs>
					<symbol id="icon-bin" viewBox="0 0 32 32">
						<title>bin</title>
						<path d="M4 10v20c0 1.1 0.9 2 2 2h18c1.1 0 2-0.9 2-2v-20h-22zM10 28h-2v-14h2v14zM14 28h-2v-14h2v14zM18 28h-2v-14h2v14zM22 28h-2v-14h2v14z"></path>
						<path d="M26.5 4h-6.5v-2.5c0-0.825-0.675-1.5-1.5-1.5h-7c-0.825 0-1.5 0.675-1.5 1.5v2.5h-6.5c-0.825 0-1.5 0.675-1.5 1.5v2.5h26v-2.5c0-0.825-0.675-1.5-1.5-1.5zM18 4h-6v-1.975h6v1.975z"></path>
					</symbol>
					<symbol id="icon-arrow-right" viewBox="0 0 32 32">
						<title>arrow-right</title>
						<path d="M31 16l-15-15v9h-16v12h16v9z"></path>
					</symbol>
					<symbol id="icon-arrow-left" viewBox="0 0 32 32">
						<title>arrow-left</title>
						<path d="M1 16l15 15v-9h16v-12h-16v-9z"></path>
					</symbol>
					<symbol id="icon-blocked" viewBox="0 0 32 32">
						<title>blocked</title>
						<path d="M27.314 4.686c-3.022-3.022-7.040-4.686-11.314-4.686s-8.292 1.664-11.314 4.686c-3.022 3.022-4.686 7.040-4.686 11.314s1.664 8.292 4.686 11.314c3.022 3.022 7.040 4.686 11.314 4.686s8.292-1.664 11.314-4.686c3.022-3.022 4.686-7.040 4.686-11.314s-1.664-8.292-4.686-11.314zM28 16c0 2.588-0.824 4.987-2.222 6.949l-16.727-16.727c1.962-1.399 4.361-2.222 6.949-2.222 6.617 0 12 5.383 12 12zM4 16c0-2.588 0.824-4.987 2.222-6.949l16.727 16.727c-1.962 1.399-4.361 2.222-6.949 2.222-6.617 0-12-5.383-12-12z"></path>
					</symbol>
				</defs>
			</svg>


			<?php /*

			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
							<input id="cb-select-all-1" type="checkbox">
						</td>
						<th scope="col" id="relationship" class="manage-column column-relationship column-primary desc">
							Relationship
						</th>
						<th scope="col" id="site" class="manage-column column-site">
							Site
						</th>
					</tr>
				</thead>

				<tbody id="the-list">
					<tr id="post-52" class="iedit author-self level-0 post-52 type-post status-draft format-standard hentry category-uncategorized">
						<th scope="row" class="check-column">			
							<label class="screen-reader-text" for="cb-select-52">Select bl</label>
							<input id="cb-select-52" type="checkbox" name="relationship[]" value="">
						</th>
						<td class="relationship column-relationship has-row-actions column-primary" data-colname="Relationship">
							<strong>
								<a class="row-title" href="#">Relationship Name</a>
							</strong>
						</td>
						<td class="site column-site" data-colname="Site">
							Shenandoah University
						</td>
					</tr>
				</tbody>

				<tfoot>
					<tr>
						<td id="cb" class="manage-column column-cb check-column">
							<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
							<input id="cb-select-all-1" type="checkbox">
						</td>
						<th scope="col" id="relationship" class="manage-column column-relationship column-primary desc">
							Relationship
						</th>
						<th scope="col" id="site" class="manage-column column-site">
							Site
						</th>
					</tr>
				</tfoot>

			</table>

			*/ ?>

		<?php
	}
	
		
	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function input_admin_enqueue_scripts() {
		
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];
		
		
		// register & include JS
		wp_register_script( 'acf-input-multisite_related_posts', "{$url}assets/js/input.js", array('acf-input', 'backbone'), $version );
		wp_enqueue_script('acf-input-multisite_related_posts');
		
		
		// register & include CSS
		wp_register_style( 'acf-input-multisite_related_posts', "{$url}assets/css/input.css", array('acf-input'), $version );
		wp_enqueue_style('acf-input-multisite_related_posts');
		
	}

	function image($src)
	{
		$url = $this->settings['url'];

		return "{$url}assets/images/{$src}";
	}
	
	
	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_head() {
	
		
		
	}
	
	*/
	
	
	/*
   	*  input_form_data()
   	*
   	*  This function is called once on the 'input' page between the head and footer
   	*  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and 
   	*  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
   	*  seen on comments / user edit forms on the front end. This function will always be called, and includes
   	*  $args that related to the current screen such as $args['post_id']
   	*
   	*  @type	function
   	*  @date	6/03/2014
   	*  @since	5.0.0
   	*
   	*  @param	$args (array)
   	*  @return	n/a
   	*/
   	
   	/*
   	
   	function input_form_data( $args ) {
	   	
		
	
   	}
   	
   	*/
	
	
	/*
	*  input_admin_footer()
	*
	*  This action is called in the admin_footer action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_footer)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_footer() {
	
		
		
	}
	
	*/
	
	
	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_enqueue_scripts() {
		
	}
	
	*/

	
	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_head() {
	
	}
	
	*/


	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	/*
	
	function load_value( $value, $post_id, $field ) {
		
		return $value;
		
	}
	
	*/
	
	
	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	/*
	
	function update_value( $value, $post_id, $field ) {
		
		return $value;
		
	}
	
	*/
	
	
	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/
		
	/*
	
	function format_value( $value, $post_id, $field ) {
		
		// bail early if no value
		if( empty($value) ) {
		
			return $value;
			
		}
		
		
		// apply setting
		if( $field['font_size'] > 12 ) { 
			
			// format the value
			// $value = 'something';
		
		}
		
		
		// return
		return $value;
	}
	
	*/
	
	
	/*
	*  validate_value()
	*
	*  This filter is used to perform validation on the value prior to saving.
	*  All values are validated regardless of the field's required setting. This allows you to validate and return
	*  messages to the user if the value is not correct
	*
	*  @type	filter
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$valid (boolean) validation status based on the value and the field's required setting
	*  @param	$value (mixed) the $_POST value
	*  @param	$field (array) the field array holding all the field options
	*  @param	$input (string) the corresponding input name for $_POST value
	*  @return	$valid
	*/
	
	/*
	
	function validate_value( $valid, $value, $field, $input ){
		
		// Basic usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = false;
		}
		
		
		// Advanced usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = __('The value is too little!','acf-multisite_related_posts'),
		}
		
		
		// return
		return $valid;
		
	}
	
	*/
	
	
	/*
	*  delete_value()
	*
	*  This action is fired after a value has been deleted from the db.
	*  Please note that saving a blank value is treated as an update, not a delete
	*
	*  @type	action
	*  @date	6/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (mixed) the $post_id from which the value was deleted
	*  @param	$key (string) the $meta_key which the value was deleted
	*  @return	n/a
	*/
	
	/*
	
	function delete_value( $post_id, $key ) {
		
		
		
	}
	
	*/
	
	
	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0	
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	/*
	
	function load_field( $field ) {
		
		return $field;
		
	}	
	
	*/
	
	
	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	/*
	
	function update_field( $field ) {
		
		return $field;
		
	}	
	
	*/
	
	
	/*
	*  delete_field()
	*
	*  This action is fired after a field is deleted from the database
	*
	*  @type	action
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	n/a
	*/
	
	/*
	
	function delete_field( $field ) {
		
		
		
	}	
	
	*/
	
	
}


// initialize
new acf_field_multisite_related_posts( $this->settings );


// class_exists check
endif;