jQuery(document).ready(function($) {

window.listTable = {

	init: function() {
		this.loading = false;

		$('form').each(function() {
			this.reset();
		});

		if ( '' == $.query.GET('paged') )
			$.query.SET('paged', 1);
		this.set_total_pages();

		this.$tbody = $('#the-list, #the-comment-list');

		this.$overlay = $('<div id="loading-items>')
			.html(listTableL10n.loading)
			.hide()
			.prependTo($('body'));
	},

	// paging
	set_total_pages: function(num) {
		var last_page_url = $('.last-page').attr('href');

		if ( last_page_url )
			this.total_pages = num || $.query.load( last_page_url ).get('paged');
	},

	get_total_pages: function() {
		return this.total_pages;
	},

	change_page: function(paged) {
		if ( paged < 1 || paged > this.total_pages )
			return false;

		this.update_rows({'paged': paged});
	},

	htmlencode: function(value) {
		return $('<div/>').text(value).html();
	},

	update_rows: function(args, reset_paging, callback) {
		if ( this.loading )
			return false;

		var different = false, data = {};

		$.each(args, function(key, val) {
			if ( val != $.query.GET(key) ) {
				$.query.SET(key, val);
				different = true;
			}
		});

		if ( !different )
			return false;

		this.show_overlay();

		if ( reset_paging )
			$.query.SET('paged', 1);

		$.each( $.query.get(), function(key, value) {
			if ( true === value )
				data[key] = '';
			else
				data[key] = value;
		});

		this._callback = callback;

		this.fetch_list(
			data,
			$.proxy(this, 'handle_success'),
			$.proxy(this, 'handle_error')
		);

		return true;
	},

	fetch_list: function(data, success_callback, error_callback) {
		data = $.extend(data, {
			'action': 'fetch-list',
			'list_args': list_args
		});

		$.ajax({
			url: ajaxurl,
			global: false,
			dataType: 'json',
			data: data,
			success: success_callback,
			error: error_callback
		});
	},

	handle_success: function(response) {
		if ( 'object' != typeof response ) {
			this.handle_error();
		} else {
			this.hide_overlay();

			this.$tbody.html(response.rows);

			$('.displaying-num').html(response.total_items_i18n);
			$('.total-pages').html(response.total_pages_i18n);

			this.set_total_pages(response.total_pages);

			$('.current-page').val($.query.GET('paged'));

			$('th.column-cb :input').attr('checked', false);

			if ( this._callback )
				this._callback();
		}
	},

	handle_error: function() {
		this.hide_overlay();

		$('h2').after('<div class="error ajax below-h2"><p>' + listTableL10n.error + '</p></div>');
	},

	show_overlay: function() {
		this.loading = true;

		$('.error.ajax').remove();

		this.$overlay
			.css({
				width: this.$tbody.width() + 'px',
				height: this.$tbody.height() - 20 + 'px'
			})
			.css(this.$tbody.offset())
			.show();
	},

	hide_overlay: function() {
		this.loading = false;
		this.$overlay.hide();
	}
}

listTable.init();

// Ajaxify various UI elements

	// pagination
	$('.tablenav-pages a').click(function() {
		var paged = $.query.GET('paged');

		switch ( $(this).attr('class') ) {
			case 'first-page':
				paged = 1;
				break;
			case 'prev-page':
				paged -= 1;
				break;
			case 'next-page':
				paged += 1;
				break;
			case 'last-page':
				paged = listTable.get_total_pages();
				break;
		}

		listTable.change_page(paged);

		return false;
	});

	$('.current-page').keypress(function(e) {
		if ( 13 != e.keyCode )
			return;

		listTable.change_page(parseInt($(this).val()));

		return false;
	});

	// sortable columns
	$('th a').click(function() {
		var orderby = $.query.GET('orderby'),
			order = $.query.GET('order'),
			$th = $(this).parent('th');

		if ( $th.hasClass('sortable') ) {
			orderby = $.query.load($(this).attr('href')).get('orderby');
			order = 'asc';

			$('th.sorted-desc, th.sorted-asc')
				.removeClass('sorted-asc')
				.removeClass('sorted-desc')
				.addClass('sortable');

			$th.removeClass('sortable').addClass('sorted-asc');
		}
		else if ( $th.hasClass('sorted-asc') ) {
			order = 'desc';
			$th.removeClass('sorted-asc').addClass('sorted-desc');
		}
		else if ( $th.hasClass('sorted-desc') ) {
			order = 'asc';
			$th.removeClass('sorted-desc').addClass('sorted-asc');
		}

		listTable.update_rows({'orderby': orderby, 'order': order}, true);

		return false;
	});

	// searchbox
	function change_search(ev) {
		if ( 'keypress' == ev.type && 13 != ev.keyCode )
			return;

		ev.preventDefault();
		ev.stopImmediatePropagation();

		var data = $(this).parent('.search-box').find(':input').serializeObject();

		listTable.update_rows(data, true, function() {
			$('h2 .subtitle').remove();

			if ( data.s )
				$('h2').append($('<span class="subtitle">').html(
					listTableL10n.search.replace('%s', this.htmlencode(data.s))
				));
		});
	}
	$('.search-box :submit').click(change_search);
	$('.search-box :text').keypress(change_search);

	// tablenav dropdowns
	$('#post-query-submit').click(function() {
		var args = {};

		$(this).parents('.actions').find('select[name!="action"]').each(function() {
			var $el = $(this);

			args[$el.attr('name')] = $el.val();
		});

		listTable.update_rows(args, true);

		return false;
	});

	// view switch
	$('.view-switch a').click(function() {
		var $this = $(this);

		listTable.update_rows({'mode': $.query.load($this.attr('href')).get('mode')}, false, function() {
			$('.view-switch .current').removeClass('current');
			$this.addClass('current');
		});

		return false;
	});
});

