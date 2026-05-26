jQuery(function ($) {
	var lastFieldPos = 0;
	var currFieldId = 0;
	var inpCont = $('#asp_acf_inputs_container');
	var maxFieldId = 0;
	var asp_acf_input_tpl = $('.asp_acf_input_tpl');
	var acfSingleItemRadioTpl = $('.asp_acf_single_item_radio').html();
	var acfDefDatepickerParams = {
		yearRange: "-100:+100",
		changeMonth: true,
		changeYear: true,
		constrainInput: true,
		firstDay: aspACFData.opts.firstDay,
		dateFormat: "dd/mm/yy"
	};

	$.each(aspACFData.fields, function (id, field) {
		if (field) {
			currFieldId = field.id;
			if (field.id > maxFieldId) {
				maxFieldId = field.id;
			}
			var acfInputTypeTpl = asp_acf_get_template(field.type);
			var acfInputName = $('select.asp-acf-cf-select').find('option[value="' + field.type + '"]').html();
			asp_acf_add_input(field.id, acfInputName, field.type, acfInputTypeTpl, field);
			lastFieldPos = field.pos;
		}
		currFieldId = maxFieldId;
		currFieldId++;
	});

	function asp_acf_get_template(type) {
		var acfInputTypeTpl = $('.asp_acf_content_tpl_default').clone();
		var acfTypeTpl = $('.asp_acf_content_tpl_' + type);
		if (acfTypeTpl.length !== 0) {
			acfInputTypeTpl.find('tbody').append(acfTypeTpl.find('tbody').html());
		}
		return acfInputTypeTpl.html();
	}

	function asp_acf_add_input(fieldId, name, type, content, values) {
		var asp_acf_input = asp_acf_input_tpl.clone();
		asp_acf_input.html(asp_acf_input.html().replace(/\%\_title\_\%/g, name));
		asp_acf_input.html(asp_acf_input.html().replace(/\%\_content\_\%/g, content));
		asp_acf_input.html(asp_acf_input.html().replace(/\%\_input\_type\_\%/g, type));
		asp_acf_input.html(asp_acf_input.html().replace(/\%\_field_id\_\%/g, fieldId));
		if (typeof acfInputAdditionalCont !== "undefined") {
			asp_acf_input.find('table').append(acfInputAdditionalCont);
		}

		$.each(values, function (id, value) {
			if (id === 'opts') {
				if (type === "dropdown") {
					var table = asp_acf_input.find('.asp-acf-dropdown-items-table');

					$.each(value.items, function (itemId, item) {
						var itemTpl = $($('.asp_acf_dropdown_item_tpl').html());
						itemTpl.html(itemTpl.html().replace(/\%\_field_id\_\%/g, fieldId));
						itemTpl.html(itemTpl.html().replace(/\%\_item_value\_\%/g, item));
						itemTpl.html(itemTpl.html().replace(/\%\_item_id\_\%/g, itemId));
						table.append(itemTpl.html());
					});
				}
				$.each(value, function (optName, optVal) {
					var replace = "\%\_opt\_" + optName + "\_\%";
					var re = new RegExp(replace, "g");
					asp_acf_input.html(asp_acf_input.html().replace(re, optVal));
				});
			} else {
				var replace = "\%\_field\_" + id + "\_\%";
				var re = new RegExp(replace, "g");
				asp_acf_input.html(asp_acf_input.html().replace(re, value));
			}
		});

		asp_acf_input.html(asp_acf_input.html().replace(/\%\_(.*)\_\%/g, ""));

		asp_acf_input.addClass('asp_acf_input');
		$('#asp_acf_inputs_container').append(asp_acf_input);
		asp_acf_input.slideDown('fast', function () {
			$(this).removeClass('asp_acf_input_tpl');
			asp_acf_input.find('.asp_acf_field_required').change();
		});
		asp_acf_input.find('input.asp-acf-datepicker').datepicker(acfDefDatepickerParams);
	}

	$('.asp-acf-add-new-btn').click(function (e) {
		e.preventDefault();
		var acfInputType = $('.asp-acf-cf-select').val();
		var acfNewInputName = $('.asp-acf-cf-select').find('option[value="' + acfInputType + '"]').html();
		var acfInputTypeTpl = asp_acf_get_template(acfInputType);
		lastFieldPos++;
		var values = {};
		values.pos = lastFieldPos;
		values.opts = {};
		if (aspACFData.defOpts[acfInputType]) {
			values.opts = aspACFData.defOpts[acfInputType];
		}
		asp_acf_add_input(currFieldId, 'New Input: ' + acfNewInputName, acfInputType, acfInputTypeTpl, values);
		currFieldId++;

		//			if (acfInputType === "radio") {
		//			    asp_acf_input.find('.asp-acf-radio-add-btn').click();
		//			}
	});

	inpCont.on('click', '.asp-acf-dropdown-new-item', function (e) {
		e.preventDefault();
		var table = $(this).siblings('.asp-acf-dropdown-items-table');
		var itemTpl = $($('.asp_acf_dropdown_item_tpl').html());
		itemTpl.html(itemTpl.html().replace(/\%\_field_id\_\%/g, $(this).data('field-id')));
		itemTpl.html(itemTpl.html().replace(/\%\_item_value\_\%/g, ''));
		itemTpl.html(itemTpl.html().replace(/\%\_item_id\_\%/g, ''));
		table.append($(itemTpl.html()).fadeIn(200));
	});

	inpCont.on('click', '.asp-acf-dropdown-delete-item', function (e) {
		e.preventDefault();
		if (confirm(aspACFData.str.confirmItemDelete)) {
			$(this).closest('tr').fadeOut('fast', function () {
				$(this).remove();
			});
		}
	});

	inpCont.on('click', 'a.asp_acf_del_input_btn', function (e) {
		e.preventDefault();
		if (confirm(aspACFData.str.confirmDelete)) {
			$(this).prop('disabled', true);
			$(this).parents('.asp_acf_input').slideUp('fast', function () {
				$(this).remove();
			});
		}
	});
	inpCont.on('click', '.asp_acf_up_input_btn', function (e) {
		e.preventDefault();
		if ($(this).parents('.asp_acf_input').prev()[0]) {
			$(this).parents('.asp_acf_input').fadeOut('fast', function () {
				$(this).insertBefore($(this).prev()).fadeIn('fast');
			});
			prev = $(this).parents('.asp_acf_input').prev()[0];
			curr = $(this).parents('.asp_acf_input');
			prev_val = $(prev).find('input[name^="asp_acf_field_pos"]').val();
			curr_val = $(curr).find('input[name^="asp_acf_field_pos"]').val();
			$(prev).find('input[name^="asp_acf_field_pos"]').val(curr_val);
			$(curr).find('input[name^="asp_acf_field_pos"]').val(prev_val);
		}
	});
	inpCont.on('click', '.asp_acf_down_input_btn', function (e) {
		e.preventDefault();
		if ($(this).parents('.asp_acf_input').next()[0]) {
			$(this).parents('.asp_acf_input').fadeOut('fast', function () {
				$(this).insertAfter($(this).next()).fadeIn('fast');
			});
			prev = $(this).parents('.asp_acf_input').next()[0];
			curr = $(this).parents('.asp_acf_input');
			prev_val = $(prev).find('input[name^="asp_acf_field_pos"]').val();
			curr_val = $(curr).find('input[name^="asp_acf_field_pos"]').val();
			$(prev).find('input[name^="asp_acf_field_pos"]').val(curr_val);
			$(curr).find('input[name^="asp_acf_field_pos"]').val(prev_val);
		}
	});
	inpCont.on('change', '.asp_acf_field_required', function () {
		if (!$(this).is(':visible')) {
			return;
		}
		if ($(this).is(':checked')) {
			$(this).siblings('span').html('');
		} else {
			$(this).siblings('span').html('<input type="hidden" name="asp_acf_field_required[]" value="">');
		}
	});
	$('input[data-wpspsc-cci-checked]').each(function () {
		if ($(this).attr('data-wpspsc-cci-checked') === '1') {
			$(this).prop('checked', true);
		}
		$(this).change();
	});
	inpCont.on('click', '.asp-acf-radio-add-btn', function (e) {
		e.preventDefault();
		$(this).parent().children().last().after(acfSingleItemRadioTpl);
	});
	inpCont.on('click', '.asp_acf_remove_item_btn', function (e) {
		e.preventDefault();
		$(this).parent().remove();
	});
}
);