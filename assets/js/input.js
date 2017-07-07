(function($, B){

	/**
	 * This collection knows how to fetch data from the WP AJAX endpoint
	 * using a POST instead of a GET; initialize with "action" config, e.g.,
	 * new AjaxCollection({ action: 'ajax_action_name' })
	 */
	var AjaxCollection = B.Collection.extend({
		initialize: function(options) {
			this.options = options;
			this.model = this.options.model || B.Model;
		},
		url: ajaxurl,
		fetch: function() {
			var args = Array.prototype.slice.apply(arguments);
			return B.Collection.prototype.fetch.call(this, {
				data: $.extend({}, { 'action': this.options.action }, args[0]),
				type: 'POST'
			});
		}
	});
	
	function initialize_field( $container ) {

		var $settings = $container.find('[data-toggle="settings"]');

		var settings = new B.Model();

		// anytime settings are updated, encode to JSON and put into hidden input
		// which will ultimately be stored in the meta table
		settings.on('change', function() {
			$settings.val(JSON.stringify(settings.attributes));
			if (window.console) {
				console.log('settings: ', settings.attributes);
			}
		});

		// limit = max number of posts to display
		var $limit = $container.find('[data-toggle="limit"]')
			.on('change', function() {
				settings.set('limit', $limit.val());
			});

		// order = the sort order for the output
		var $order = $container.find('[data-toggle="order"]')
			.on('change', function() {
				settings.set('order', $order.val());
			});

		// type = the post type to relate; defaults to "post"
		var $type = $container.find('[data-toggle="type"]')
			.on('change', function() {
				settings.set('type', $type.val());
			});


		var Picker = B.View.extend({
			initialize: function(options) {
				var $view = this;

				$view.selected = new B.Collection();

				$view.$select = $view.$('select');

				$view.$select.attr('disabled', true);

				$view.$placeholder = $('<option></option>');
				$view.$placeholder.text($view.$select.attr('placeholder'));
				$view.$select.html($view.$placeholder);

				$view.$search = $view.$('[type="search"]');

				$view.options = options;

				$view.source = options.source;

				$view.$wait = $view.$('.wait');

				if ($view.source) {

					var Selection = B.Collection.extend({
						model: $view.source.model
					});

					this.selected = new Selection();

					$view.source.on('request', function() {
						$view.$wait.show();
					});

					$view.source.on('sync', function() {
						var source = this;

						$view.$wait.hide();

						$view.$select.attr('disabled', source.length < 1);

						$view.$select.html('');

						if (source.length < 1) {
							$view.$select.append('<option value="">No results found</option>');

						} else {
							source.each(function(item) {
								var $option = $('<option></option>');
								$option.attr('value', item.id);
								if ($view.selected.get(item.id)) {
									$option.attr('selected', true);
								}
								$option.text(item.getLabel());
								$option.data('item', item);
								$view.$select.append($option);
							});
						}

					});
				}
			},
			search: function(args) {
				var $view = this;

				clearTimeout($view._searchBounce);

				$view._searchBounce = setTimeout(function() {
					$view.source.fetch($.extend({
						site: settings.get('site'),
						search: $view.$search.val(),
						type: $type.val()
					}, args));
				}, 300);
			},
			events: {
				'change select': function() {
					var $view = this;

					$view.selected.reset([]);

					var values = $view.$select.val();

					for(i in values) {
						var tax = $view.source.get(values[i]);
						if (tax) {
							$view.selected.add(tax);
						}
					}
				},
				'keydown [type="search"]': function(e) {
					this.search();
				},
				'search [type="search"]': function(e) {
					this.search();
				}
			}
		});

		var $sites = $container.find('[data-toggle="sites"]');

		$sites.on('change', function() {
			settings.set('site', $sites.val());
		});

		var $taxonomies = new Picker({ 
			el: $container.find('[data-toggle="taxonomies"]'),
			source: new AjaxCollection({ 
				model: B.Model.extend({
					idAttribute: 'name',
					getLabel: function() {
						return this.get('label');
					}
				}),
				action: 'acf_multisite_related_posts_taxonomies' 
			})
	  });

	  settings.on('change:site', function(settings, id) {
	  	$taxonomies.$search.val('');
	  	$taxonomies.search();
	  });

	  var $clear = $container.find('[data-action="clear-taxonomies"]');

	  $clear.click(function() {
	  	$taxonomies.selected.reset([]);
	  	$taxonomies.$select.val(null);
	  	return false;
	  });

		var $terms = new Picker({ 
			el: $container.find('[data-toggle="terms"]'),
			source: new AjaxCollection({ 
				model: B.Model.extend({
					idAttribute: 'term_id',
					getLabel: function() {
						return this.get('name') + ' (' + this.get('taxonomy') + ')';
					}
				}),
				action: 'acf_multisite_related_posts_terms' 
			})
		});

		$taxonomies.selected.on('update reset', function() {
			// update disabled state on the clear button
	  	$clear.attr('disabled', this.length < 1);
	  	
	  	$terms.search();
	  });

		var $selected = new Picker({
			el: $container.find('[data-toggle="selected"]')
		});

		settings.set({
			limit: $limit.val(),
			order: $order.val(),
			type: $type.val(),
			site: $sites.val()
		});
	};
	
	
	if( typeof acf.add_action !== 'undefined' ) {
	
		/*
		*  ready append (ACF5)
		*
		*  These are 2 events which are fired during the page load
		*  ready = on page load similar to $(document).ready()
		*  append = on new DOM elements appended via repeater field
		*
		*  @type	event
		*  @date	20/07/13
		*
		*  @param	$el (jQuery selection) the jQuery element which contains the ACF fields
		*  @return	n/a
		*/
		
		acf.add_action('ready append', function( $el ){
			
			// search $el for fields of type 'multisite_related_posts'
			acf.get_fields({ type : 'multisite_related_posts'}, $el).each(function(){
				
				initialize_field( $(this) );
				
			});
			
		});
		
		
	} else {
		
		
		/*
		*  acf/setup_fields (ACF4)
		*
		*  This event is triggered when ACF adds any new elements to the DOM. 
		*
		*  @type	function
		*  @since	1.0.0
		*  @date	01/01/12
		*
		*  @param	event		e: an event object. This can be ignored
		*  @param	Element		postbox: An element which contains the new HTML
		*
		*  @return	n/a
		*/
		
		$(document).on('acf/setup_fields', function(e, postbox){
			
			$(postbox).find('.field[data-field_type="multisite_related_posts"]').each(function(){
				
				initialize_field( $(this) );
				
			});
		
		});
	
	
	}


})(jQuery, Backbone);
