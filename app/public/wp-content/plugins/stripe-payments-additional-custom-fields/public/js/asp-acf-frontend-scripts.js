jQuery(document).ready(function ($) {
    $.each(aspACFFrontendData.fields, function (id, field) {
	if (field.type === 'datepicker') {
	    var datepickerOpts = {
		yearRange: "-100:+100",
		changeMonth: true,
		changeYear: true,
		constrainInput: true,
		firstDay: aspACFFrontendData.opts.firstDay,
		dateFormat: field.opts.date_format
	    };
	    if (field.opts.date_start) {
		datepickerOpts.minDate = field.opts.date_start;
	    }
	    if (field.opts.date_end) {
		datepickerOpts.maxDate = field.opts.date_end;
	    }
	    $("input.asp_product_custom_input_" + field.id).datepicker(datepickerOpts);
	}
    });
});