
$(document).ready(function() {

	$(opencart_ajax_search.selector).after('<div class="onencart-ajax-search"><ul></ul><div class="result-text"></div></div><div aria-hidden="true" class="onencart-ajax-search__page-overlay"></div>');

	const searchContainer = $('.onencart-ajax-search');

	function getContentSection(items, type, title = '') {
		let $html = '';

		if ( $.isEmptyObject(items) ) return false;

		if ( title ) {
			$html += '<li class="items-title">' + title + '</li>';
		}

		$.each(items, function(index,item) {
			let $icoClass;
			switch ( type ) {
				case 'products':
					$icoClass = 'fa fa-file-o';
					break;
				case 'categories':
					$icoClass = 'fa fa-search'
					break;
				case 'information':
					$icoClass = 'fa fa-file-text-o'
					break
				case 'news':
					$icoClass = 'fa fa-info-circle'
					break
				default:
					$icoClass = 'fa fa-file-o';
			}


			$html += '<li id="item-' + type + '-' + item.id + '" class="item item--' + type + '">';
			$html += '<a href="' + item.url + '" title="' + item.name + '">';

			$html += '<div class="item-image">';
			if ( item.image ) {
				$html += '<img alt="' + item.name + '" src="' + item.image + '">';
			} else {
				$html += '<i class="' + $icoClass+ '"></i>'
			}

			$html += '</div>';

			$html += '	<div class="item-name">' + item.name ;

			if ( item.extra_info ){
				$html += '<p>' + item.extra_info + '</p>';
			}

			$html += '</div>';

			if( item.price ){
				if (item.special) {
					$html += '<div class="item-price"><span class="special">' + item.price + '</span><span class="price">' + item.special + '</span></div>';
				} else {
					$html += '<div class="item-price"><span class="price">' + item.price + '</span></div>';
				}
			}

			$html += '</a>';
			$html += '</li>';

		});

		$html += '<li class="hr"></li>';

		return  $html;
	}
	function hideAjaxSearch() {
		searchContainer.removeClass('onencart-ajax-search--showed');
	}

	function showAjaxSearch() {
		searchContainer.addClass('onencart-ajax-search--showed');
	}

	$(opencart_ajax_search.selector).autocomplete({
		'source': function(request, response) {
			const resultText = $('.result-text');
			const filter_name = filterInput.val();

			resultText.html('');

			if ( filter_name && filter_name.length >= opencart_ajax_search_min_length ) {

				const searchElementContainer = $('.onencart-ajax-search ul');
				const data = {
					'search' : filter_name
				};

				$.ajax({
					url: 'index.php?route=module/opencart_ajax_search',
					type: 'post',
					data: data,
					dataType: 'json',
					beforeSend: function () {
						searchElementContainer.html('<li style="text-align: center;height:10px;"><img class="loading" src="catalog/view/theme/default/image/loading.gif" /></li>');
						showAjaxSearch();
					},
					success: function(result) {

						const content = result.content;

						searchElementContainer.html('');

						if ( content.length ) {

							$.each(content, function(index, section) {
								searchElementContainer.append(getContentSection(section.items, section.type, section.title));
							});

							resultText.html('<a href="' + result.opencart_ajax_search_href + '" class="view-all-results">' + result.text_view_all_results +'</a>');

						} else {

							searchElementContainer.html('<li style="text-align: center;height:10px;">' + opencart_ajax_search.text_no_matches + '</li>');

						}

						return false;
					},
					error: function(xhr, ajaxOptions, thrownError) {
						console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
					}
				});

			} else {
				hideAjaxSearch()
			}
		},
		'select': function(product) {
			$(opencart_ajax_search.selector).val(product.name);
		}
	});

	$(document).bind( "mouseup touchend", function(e){
		if ( !searchContainer.is(e.target) && searchContainer.has(e.target).length === 0 ) {
			hideAjaxSearch()
		}
	});

	$( window ).on( "scroll", function() {
		hideAjaxSearch()
	} );
});