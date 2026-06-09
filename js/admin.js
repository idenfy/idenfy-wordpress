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
		var cmInstances = [];

		$(".shortcode-copy").on("click", function() {
			var $el = $(this);
			copyToClipboard($el.text());
			$el.addClass("flash");
			setTimeout(function() {
				$el.removeClass("flash");
			}, 1000);
		});

		var adminData = (typeof WPIdenfyAdminData !== "undefined") ? WPIdenfyAdminData : {};
		var i18n = adminData.i18n || {};

		// Show/hide the API secret.
		var $secret = $("#wp-idenfy-api-secret");
		var $secretToggle = $("#wp-idenfy-secret-toggle");
		if ($secret.length && $secretToggle.length) {
			$secretToggle.on("click", function() {
				var reveal = $secret.attr("type") === "password";
				$secret.attr("type", reveal ? "text" : "password");
				$secretToggle.attr("aria-pressed", reveal ? "true" : "false");
				$secretToggle.attr("aria-label", reveal ? (i18n.hide || "Hide secret") : (i18n.show || "Show secret"));
				$secretToggle.find(".dashicons")
					.toggleClass("dashicons-visibility", !reveal)
					.toggleClass("dashicons-hidden", reveal);
			});
		}

		// Credentials status badge + AJAX save/test (progressive enhancement).
		var $credForm = $("#wp-idenfy-credentials-form");
		var $credStatus = $("#wp-idenfy-cred-status");

		function setCredStatus(state, message) {
			if (!$credStatus.length) return;
			var icons = {
				valid: "dashicons-yes-alt",
				invalid: "dashicons-dismiss",
				error: "dashicons-dismiss",
				empty: "dashicons-dismiss",
				testing: "dashicons-update"
			};
			$credStatus
				.removeClass("is-valid is-invalid is-error is-empty is-testing")
				.addClass("is-" + state);
			$credStatus.find(".dashicons")
				.attr("class", "dashicons " + (icons[state] || "dashicons-dismiss"));
			$credStatus.find(".wp-idenfy-cred-status-text").text(message || "");
		}

		if ($credForm.length && adminData.ajaxUrl) {
			$credForm.on("submit", function(e) {
				e.preventDefault();
				var $submit = $credForm.find('button[type="submit"]');
				$submit.prop("disabled", true);
				setCredStatus("testing", i18n.testing || "Verifying credentials…");

				$.post(adminData.ajaxUrl, {
					action: "wp_idenfy_save_api",
					wp_idenfy_nonce: $credForm.find('input[name="wp_idenfy_nonce"]').val(),
					api_key: $("#wp-idenfy-api-key").val(),
					api_secret: $secret.val()
				}).done(function(res) {
					if (res && res.success) {
						setCredStatus("valid", (res.data && res.data.message) || i18n.valid || "Credentials are valid.");
					} else {
						var data = (res && res.data) || {};
						var state = data.status === "error" ? "error" : "invalid";
						setCredStatus(state, data.message || i18n[state] || i18n.invalid || "Invalid credentials.");
					}
				}).fail(function() {
					setCredStatus("error", i18n.error || "Could not reach iDenfy. Please try again.");
				}).always(function() {
					$submit.prop("disabled", false);
				});
			});
		}

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
			if (tab === "customization") {
				cmInstances.forEach(function(cm) {
					if (cm && cm.codemirror) cm.codemirror.refresh();
				});
			}
		});

		function initCustomizationForm($form) {
			var ctype      = $form.data("cust-type") === "kyb" ? "kyb" : "kyc";
			var prefix     = ctype === "kyb" ? "wp-idenfy-kyb-" : "wp-idenfy-";
			var btnSel     = ctype === "kyb" ? "a.idenfy-kyb-button" : "a.idenfy-button";
			var previewId  = ctype === "kyb" ? "wp-idenfy-kyb-preview-button" : "wp-idenfy-preview-button";
			var defaultTxt = ctype === "kyb" ? "Verify business" : "Verify me";
			var cssId      = prefix + "advanced-css";

			var $previewBtn = $("#" + previewId);
			if (!$previewBtn.length) return;
			$previewBtn.on("click", function(e) { e.preventDefault(); });

			var $previewStyle = $("<style></style>").appendTo("head");
			var cmInstance = null;

			function getCss() {
				return cmInstance ? cmInstance.codemirror.getValue() : $("#" + cssId).val();
			}

			function updatePreview() {
				var text     = $("#" + prefix + "button-text").val() || defaultTxt;
				var bg       = $("#" + prefix + "bg-color").val();
				var color    = $("#" + prefix + "text-color").val();
				var radius   = $("#" + prefix + "border-radius").val();
				var py       = $("#" + prefix + "padding-y").val();
				var px       = $("#" + prefix + "padding-x").val();
				var fs       = $("#" + prefix + "font-size").val();
				var advanced = getCss();

				$previewBtn.contents().filter(function() {
					return this.nodeType === 3;
				}).remove();
				$previewBtn.prepend(document.createTextNode(text));

				var sel = btnSel + "#" + previewId;
				var css = (advanced ? advanced + "\n" : "") +
					sel + ", " + sel + ":link, " + sel + ":visited, " + sel + ":hover, " + sel + ":focus {" +
					"background-color: " + bg + " !important;" +
					"border-color: " + bg + " !important;" +
					"color: " + color + " !important;" +
					"border-radius: " + radius + "px !important;" +
					"padding: " + py + "px " + px + "px !important;" +
					"font-size: " + fs + "px !important;" +
					"}";
				$previewStyle.text(css);
			}

			$form.on("input change", "input", updatePreview);

			if (typeof WPIdenfyAdminData !== "undefined" && WPIdenfyAdminData.codeEditor && typeof wp !== "undefined" && wp.codeEditor) {
				cmInstance = wp.codeEditor.initialize(cssId, WPIdenfyAdminData.codeEditor);
				cmInstance.codemirror.on("change", function() {
					cmInstance.codemirror.save();
					updatePreview();
				});
				cmInstances.push(cmInstance);
			} else {
				$("#" + cssId).on("input change", updatePreview);
			}

			updatePreview();
		}

		$(".wp-idenfy-customization-form").each(function() {
			initCustomizationForm($(this));
		});

		// Switch between the KYC and KYB button designs without scrolling.
		function activateCustSection(ctype) {
			$(".wp-idenfy-cust-switch-btn").each(function() {
				var on = $(this).data("cust-target") === ctype;
				$(this).toggleClass("is-active", on).attr("aria-selected", on ? "true" : "false");
			});
			$(".wp-idenfy-cust-section").each(function() {
				$(this).toggleClass("is-active", $(this).data("cust-type") === ctype);
			});
			// The newly shown editor was hidden, so its layout needs a refresh.
			cmInstances.forEach(function(cm) {
				if (cm && cm.codemirror) cm.codemirror.refresh();
			});
		}

		$(".wp-idenfy-cust-switch-btn").on("click", function() {
			activateCustSection($(this).data("cust-target"));
		});

		// Honor a #wp-idenfy-cust-<type> hash (e.g. after saving) to open that design.
		var custHash = (window.location.hash || "").replace("#wp-idenfy-cust-", "");
		if (custHash === "kyc" || custHash === "kyb") {
			activateCustSection(custHash);
		}
	});
})(jQuery);
