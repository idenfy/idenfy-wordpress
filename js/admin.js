function copyToClipboard(text) {
    var sampleTextarea = document.createElement("textarea");
    document.body.appendChild(sampleTextarea);
    sampleTextarea.value = text;
    sampleTextarea.select();
    document.execCommand("copy");
    document.body.removeChild(sampleTextarea);
}

;(function($) {
	$(document).ready(function() {
		$(".shortcode-copy").on("click", function() {
			var _parent = $(this).closest(".span-padding");
			copyToClipboard("[IDENFY]");
			_parent.addClass("flash");
			setTimeout(function() {
				_parent.removeClass("flash");
			}, 1000);
		});
	});
})(jQuery);