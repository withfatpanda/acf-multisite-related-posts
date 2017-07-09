(function($, B) {

	/**
	 * This collection knows how to fetch data from the WP AJAX endpoint
	 * using a POST instead of a GET; initialize with "action" config, e.g.,
	 * new AjaxCollection({ action: 'ajax_action_name' })
	 */
	var AjaxCollection = B.Collection.extend({
		initialize: function(models, options) {
			this.options = options;
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

	var Term = B.Model.extend({
		idAttribute: 'term_id',
		getLabel: function() {
			return this.get('name') + ' (' + this.get('taxonomy') + ')';
		}
	});

	var Taxonomy = B.Model.extend({
		idAttribute: 'name',
		getLabel: function() {
			return this.get('label');
		}
	});

	/**
	 * A Picker is a bit of UI that combines an <input type="search">
	 * and a <select multiple> with two Collections: one that syncs
	 * with the server to provide a source list, and the other that
	 * maintains a list of items that have been selected from the source list.
	 */
	var Picker = B.View.extend({
		initialize: function(options) {
			var $view = this;

			$view.$select = $view.$('select');

			$view.selected = options.selected;

			$view.source = options.source;

			$view.$select.attr('disabled', true);

			$view.$placeholder = $('<option></option>');
			$view.$placeholder.text($view.$select.attr('placeholder'));
			$view.$select.html($view.$placeholder);

			$view.$search = $view.$('[type="search"]');

			$view.options = options;

			$view.$wait = $view.$('.wait');

			$view.source.on('request', function() {
				$view.$wait.show();
			});

			$view.source.on('update', function() {
				$view._renderSelect();
			});

			$view.source.on('sync', function() {
				$view._renderSelect();
				$view.$wait.hide();
			});

			var selectedData = $view.$el.data('selected');
			if (selectedData) {
				$view.source.add(selectedData);
			}
		},
		_renderSelect: function() {
			var $view = this, source = $view.source;

			$view.$select.attr('disabled', source.length < 1);

			$view.$select.html('');

			if (source.length < 1) {
				var $noResults = $('<option value=""></option>');
				$noResults.text($view.$el.data('noResults'));
				$view.$select.append($noResults);

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
		},
		search: function(args) {
			var $view = this;

			clearTimeout($view._searchBounce);

			$view._searchBounce = setTimeout(function() {
				$view.options.doSearch.call($view, args);
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

				if (e.keyCode === 13) {
					e.stopPropagation();
					e.preventDefault();
				}
			},
			'search [type="search"]': function(e) {
				this.search();
			}
		}
	});
	
	function initialize_field( $field ) {

		var $settings = $field.find('[data-toggle="settings"]');

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
		var $limit = $field.find('[data-toggle="limit"]')
			.on('change', function() {
				settings.set('limit', $limit.val());
			});

		// order = the sort order for the output
		var $order = $field.find('[data-toggle="order"]')
			.on('change', function() {
				settings.set('order', $order.val());
			});

		// type = the post type to relate; defaults to "post"
		var $type = $field.find('[data-toggle="type"]')
			.on('change', function() {
				settings.set('type', $type.val());
			});
		
		// <select> element containing list of sites
		var $sites = $field.find('[data-toggle="sites"]')
			.on('change', function() {
				settings.set('site', $sites.val());
			});

		// <button> for clearing taxonomy selection
		var $clear = $field.find('[data-action="clear-taxonomies"]');

		// <button> for adding terms to the list of selected terms
	  var $pick = $field.find('[data-action="pick-terms"]');

	  // <button> for removing terms from list of selected terms
	  var $remove = $field.find('[data-action="remove-terms"]');

		// taxonomy pick list
		var $taxonomies = new Picker({ 
			el: $field.find('[data-toggle="taxonomies"]'),
			source: new AjaxCollection([], { 
				model: Taxonomy,
				action: 'acf_multisite_related_posts_taxonomies', 
			}),
			selected: new B.Collection([], {
				model: Taxonomy
			}),
			doSearch: function(args) {
				this.source.fetch($.extend({
					site: settings.get('site'),
					search: this.$search.val(),
					type: $type.val()
				}, args));
			}
	  });

	  settings.on('change:site', function(settings, id) {
	  	$taxonomies.$search.val('');
	  	$taxonomies.search();
	  });

	  $clear.click(function() {
	  	$taxonomies.selected.reset([]);
	  	$taxonomies.$select.val(null);
	  	return false;
	  });

		var $terms = new Picker({ 
			el: $field.find('[data-toggle="terms"]'),
			source: new AjaxCollection([], { 
				model: Term,
				action: 'acf_multisite_related_posts_terms'
			}),
			selected: new B.Collection([], {
				model: Term
			}),
			doSearch: function(args) {
				this.selected.reset([]);

				this.source.fetch($.extend({
					site: settings.get('site'),
					search: this.$search.val(),
					type: $type.val(),
					taxonomies: $taxonomies.selected.pluck('name')
				}, args));
			} 
		});

		$taxonomies.selected.on('update reset', function() {
			var selected = this;

			// update disabled state on the clear button
	  	$clear.attr('disabled', selected.length < 1);
	  	
	  	$terms.search();
	  });

	  $terms.selected.on('update reset', function() {
	  	var selected = this;

	  	$pick.attr('disabled', selected.length < 1);
	  });

		var $selected = new Picker({
			el: $field.find('[data-toggle="selected"]'),
			selected: new B.Collection([], {
				model: Term
			}),
			source: new B.Collection([], {
				model: Term
			})
		});

		$selected.selected.on('update reset', function() {
			var selected = this;

			$remove.attr('disabled', selected.length < 1);
		});

		$pick.click(function() {
			$terms.selected.each(function(term) {
				$selected.source.add(term);
			});
			return false;
		});

		$selected.source.on('update reset', function() {
			settings.set({
				terms: $selected.source.toJSON()
			});
		});

		$remove.click(function() {
			$selected.selected.each(function(term) {
				$selected.source.remove(term);
			});
			$selected.selected.reset([]);
			return false;
		});

		// init settings from rendered fields
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
