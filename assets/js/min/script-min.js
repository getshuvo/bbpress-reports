!function($){$(function(){var t=$(".bbp-datepicker").datepicker({dateFormat:"yy-mm-dd",onSelect:function(e){var a=$(this).is("#bbp-report-start")?"minDate":"maxDate",i=$(this).data("datepicker"),r=$.datepicker.parseDate(i.settings.dateFormat||$.datepicker._defaults.dateFormat,e,i.settings);t.not(this).datepicker("option",a,r)}})})}(jQuery);