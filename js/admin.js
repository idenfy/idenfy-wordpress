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
			var $el = $(this);
			copyToClipboard($el.text());
			$el.addClass("flash");
			setTimeout(function() {
				$el.removeClass("flash");
			}, 1000);
		});

		$(".wp-idenfy-tabs .nav-tab").on("click", function(e) {
			e.preventDefault();
			var tab = $(this).data("tab");
			$(".wp-idenfy-tabs .nav-tab").removeClass("nav-tab-active");
			$(this).addClass("nav-tab-active");
			$(".tab-panel").removeClass("is-active");
			$(".tab-panel[data-tab='" + tab + "']").addClass("is-active");
			if (window.history && window.history.replaceState) {
				var url = new URL(window.location.href);
				url.searchParams.set("tab", tab);
				window.history.replaceState({}, "", url.toString());
			}
		});

		var $previewBtn = $("#wp-idenfy-preview-button");
		if ($previewBtn.length) {
			$("#wp-idenfy-preview-button").on("click", function(e) { e.preventDefault(); });

			var $previewStyle = $("<style id='wp-idenfy-preview-styles'></style>").appendTo("head");
			var cmInstance = null;

			function getCss() {
				return cmInstance ? cmInstance.codemirror.getValue() : $("#wp-idenfy-advanced-css").val();
			}

			function updatePreview() {
				var text     = $("#wp-idenfy-button-text").val() || "Verify me";
				var bg       = $("#wp-idenfy-bg-color").val();
				var color    = $("#wp-idenfy-text-color").val();
				var radius   = $("#wp-idenfy-border-radius").val();
				var py       = $("#wp-idenfy-padding-y").val();
				var px       = $("#wp-idenfy-padding-x").val();
				var fs       = $("#wp-idenfy-font-size").val();
				var advanced = getCss();

				$previewBtn.contents().filter(function() {
					return this.nodeType === 3;
				}).remove();
				$previewBtn.prepend(document.createTextNode(text));

				var css = (advanced ? advanced + "\n" : "") +
					"a.idenfy-button#wp-idenfy-preview-button, a.idenfy-button#wp-idenfy-preview-button:link, a.idenfy-button#wp-idenfy-preview-button:visited, a.idenfy-button#wp-idenfy-preview-button:hover, a.idenfy-button#wp-idenfy-preview-button:focus {" +
					"background-color: " + bg + " !important;" +
					"border-color: " + bg + " !important;" +
					"color: " + color + " !important;" +
					"border-radius: " + radius + "px !important;" +
					"padding: " + py + "px " + px + "px !important;" +
					"font-size: " + fs + "px !important;" +
					"}";
				$previewStyle.text(css);
			}

			$("#wp-idenfy-customization-form").on("input change", "input", updatePreview);

			if (typeof WPIdenfyAdminData !== "undefined" && WPIdenfyAdminData.codeEditor && typeof wp !== "undefined" && wp.codeEditor) {
				cmInstance = wp.codeEditor.initialize("wp-idenfy-advanced-css", WPIdenfyAdminData.codeEditor);
				cmInstance.codemirror.on("change", function() {
					cmInstance.codemirror.save();
					updatePreview();
				});
			} else {
				$("#wp-idenfy-advanced-css").on("input change", updatePreview);
			}

			updatePreview();
		}
	});
})(jQuery);
