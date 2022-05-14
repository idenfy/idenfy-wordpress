;(function($) {
	$(document).ready(function() {
		$(document).on("click", "a.idenfy-button", function(e) {
			e.preventDefault();
			if($(this).hasClass("doing-ajax")) return false;
			$(this).addClass("doing-ajax");
			$.ajax({
				url: WPIdenfyData.ajaxUrl + "?action=wp_idenfy_get_link",
				type: "POST",
				cache: false,
				dataType: "json",
				context: $(this)
			}).done(function(response) {
				if(!response.success) {
					alert(WPIdenfyData.i18n.error);
					$(this).removeClass("doing-ajax");
				} else {
					document.location.href = response.data;
				}
			}).fail(function() {
				alert(WPIdenfyData.i18n.NSError);
				$(this).removeClass("doing-ajax");
			});
		});
	});
})(jQuery);