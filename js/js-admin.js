jQuery(document).ready(function ($) {
	
	//Buttons change values
	$('.package-type .button').on('click', function() {
		value = Number( $(this).siblings('.package-type-value').val() );
		if ($(this).hasClass('plus')) {
			newValue = value + 1;
		} else if ($(this).hasClass('minus')) {
			newValue = Math.max( value - 1, 0);
		}
		$(this).siblings('.package-type-value').val(newValue);
	});

	
	//Get sticker
	
	$('.transsped_generate_sticker').on('click', function() {
		event.preventDefault();
		
		var data = {};
		data['nonce'] = ajax_object_admin.nonce;
		data['action'] = 'get_minimo_sticker';
		data['order_id'] = $(".order_id").val();
		
		var allIsZero = true;
		$('.package-type').each(function() {
			name = $(this).find('.package-type-value').attr('id');
			value = $(this).find('.package-type-value').val();
			data[name] = value;
			if ( value != 0 ) {
				allIsZero = false;
			}
		});
		
		thisbutton = $(this);
		thisspinner = $(this).nextAll('.spinner');
		thisresponse = $(this).nextAll('.response');
		
		if (allIsZero) {
			thisresponse.text('Válassz ki legalább egy csomagtípust!');
			return;
		}
		
		thisbutton.prop('disabled', true);
		thisspinner.addClass('is-active');
		thisresponse.text('');
		
		$.ajax({
			url:    ajax_object_admin.ajax_url, // /wp-admin/admin-ajax.php
			type:   'post',
			data:   $.param(data),
		})
		
		.done( function( response ) {
			thisbutton.prop('disabled', false);
			thisspinner.removeClass('is-active');
			thisresponse.html(response);
			if ( response.indexOf('sticker created') >= 0 ||
				 response.indexOf('címke létrehozva') >= 0	) {
				$('.transsped_print_sticker').removeAttr('disabled');
			}
		});		
	});

});
