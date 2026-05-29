;(function($) {
	var KYC_ORIGIN = "https://ui.idenfy.com";

	function attrIsTrue($el, name) {
		var v = $el.attr(name);
		return v === "true" || v === "1";
	}

	function openIdenfyKyc($btn, token) {
		var src = KYC_ORIGIN + "/?authToken=" + encodeURIComponent(token);

		var $overlay = $('<div class="idenfy-kyc-modal"></div>');
		var $inner = $('<div class="idenfy-kyc-modal-inner"></div>');
		var $close = $('<button type="button" class="idenfy-kyc-modal-close" aria-label="Close">&times;</button>');

		var iframe = document.createElement("iframe");
		iframe.src = src;
		iframe.className = "idenfy-kyc-iframe";
		iframe.setAttribute("allow", "camera; fullscreen");
		iframe.setAttribute("allowfullscreen", "");
		iframe.setAttribute("webkitallowfullscreen", "");
		iframe.setAttribute("mozallowfullscreen", "");

		$inner.append($close).append(iframe);
		$overlay.append($inner);
		$("body").append($overlay);
		$("body").addClass("idenfy-kyc-modal-open");

		$overlay.data("idenfy-btn", $btn);
		$close.on("click", function() { closeIdenfyKyc($overlay); });
	}

	function closeIdenfyKyc($overlay) {
		$overlay.remove();
		if ($(".idenfy-kyc-modal").length === 0) {
			$("body").removeClass("idenfy-kyc-modal-open");
		}
	}

	function appendKycCloseButton($overlay, $btn) {
		var $inner = $overlay.find(".idenfy-kyc-modal-inner");
		if ($inner.find(".idenfy-kyb-close").length) return;
		var closeText = $btn.attr("data-close-button-text") || WPIdenfyData.i18n.close;
		var $closeBtn = $('<button type="button" class="idenfy-kyb-close"></button>').text(closeText);
		$closeBtn.on("click", function() { closeIdenfyKyc($overlay); });
		$inner.append($closeBtn);
	}

	function handleKycResult($btn, $overlay, data) {
		var status = String(data.status || "").toUpperCase();
		var suspected = data.autoSuspected === true || data.manualSuspected === true ||
			data.autoSuspected === "true" || data.manualSuspected === "true";

		// outcome: "approved" | "suspected" | "unverified" | "failed"
		var outcome;
		if (status === "APPROVED") {
			outcome = suspected ? "suspected" : "approved";
		} else if (status === "UNVERIFIED") {
			outcome = "unverified";
		} else {
			outcome = "failed";
		}

		var passed = outcome === "approved" ||
			(outcome === "suspected" && attrIsTrue($btn, "data-accept-suspected")) ||
			(outcome === "unverified" && attrIsTrue($btn, "data-accept-unverified"));

		$btn[0].dispatchEvent(new CustomEvent("idenfy:kyc:complete", {
			detail: { status: status.toLowerCase(), outcome: outcome, passed: passed, raw: data },
			bubbles: true
		}));

		if (passed) {
			$btn.addClass("idenfy-kyc-complete");

			if (attrIsTrue($btn, "data-hide-button-on-complete")) {
				$btn.hide();
			}

			var enableSel = $btn.attr("data-on-complete-enable");
			if (enableSel) {
				$(enableSel).prop("disabled", false).removeAttr("disabled").removeClass("idenfy-disabled");
			}

			var syncSel = $btn.attr("data-sync-field");
			if (syncSel) {
				$(syncSel).val("completed").trigger("change");
			}

			var redirectUrl = $btn.attr("data-redirect");
			if (redirectUrl) {
				window.location.href = redirectUrl;
				return;
			}

			if ($btn.attr("data-hide-on-complete") === "true") {
				closeIdenfyKyc($overlay);
			} else {
				appendKycCloseButton($overlay, $btn);
			}
			return;
		}

		// Not passed: failed -> redirect_failed; unverified/suspected -> redirect_unverified.
		var redirUrl = ( outcome === "failed" )
			? $btn.attr("data-redirect-failed")
			: $btn.attr("data-redirect-unverified");
		if (redirUrl) {
			window.location.href = redirUrl;
			return;
		}

		appendKycCloseButton($overlay, $btn);
	}

	$(document).ready(function() {
		$(document).on("click", "a.idenfy-button", function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass("doing-ajax")) return false;
			$btn.addClass("doing-ajax");
			$.ajax({
				url: WPIdenfyData.ajaxUrl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: {
					action: "wp_idenfy_get_kyc_token",
					client_id: $btn.attr("data-client-id") || ""
				}
			}).done(function(response) {
				$btn.removeClass("doing-ajax");
				if (!response.success || !response.data || !response.data.token) {
					alert(WPIdenfyData.i18n.error);
					return;
				}
				openIdenfyKyc($btn, response.data.token);
			}).fail(function() {
				$btn.removeClass("doing-ajax");
				alert(WPIdenfyData.i18n.NSError);
			});
		});

		window.addEventListener("message", function(event) {
			if (event.origin !== KYC_ORIGIN) return;

			var data = event.data;
			if (typeof data === "string") {
				try { data = JSON.parse(data); } catch (e) { return; }
			}
			if (!data || typeof data !== "object" || !data.status) return;

			var $overlay = null;
			$(".idenfy-kyc-modal").each(function() {
				var iframe = $(this).find("iframe.idenfy-kyc-iframe")[0];
				if (iframe && iframe.contentWindow === event.source) {
					$overlay = $(this);
					return false;
				}
			});
			if (!$overlay) return;

			handleKycResult($overlay.data("idenfy-btn"), $overlay, data);
		}, false);

		window.addEventListener("message", function(event) {
			if (event.origin !== "https://kyb.ui.idenfy.com") return;

			var data = event.data;
			if (typeof data === "string") {
				try { data = JSON.parse(data); } catch (e) { return; }
			}
			if (!data || typeof data !== "object" || !data.status) return;

			var $match = null;
			$(".idenfy-kyb").each(function() {
				var iframe = $(this).find("iframe.idenfy-kyb-iframe")[0];
				if (iframe && iframe.contentWindow === event.source) {
					$match = $(this);
					return false;
				}
			});
			if (!$match) return;

			$match[0].dispatchEvent(new CustomEvent("idenfy:kyb:complete", {
				detail: { status: data.status, raw: data },
				bubbles: true
			}));

			if (data.status !== "success") return;

			$match.addClass("idenfy-kyb-complete");

			var enableSel = $match.attr("data-on-complete-enable");
			if (enableSel) {
				$(enableSel).prop("disabled", false).removeAttr("disabled").removeClass("idenfy-disabled");
			}

			var syncSel = $match.attr("data-sync-field");
			if (syncSel) {
				$(syncSel).val("completed").trigger("change");
			}

			var redirectUrl = $match.attr("data-redirect");
			if (redirectUrl) {
				window.location.href = redirectUrl;
				return;
			}

			if ($match.attr("data-hide-on-complete") === "true") {
				$match.hide();
			} else {
				var closeText = $match.attr("data-close-button-text") || WPIdenfyData.i18n.close;
				var $closeBtn = $('<button type="button" class="idenfy-kyb-close"></button>').text(closeText);
				$closeBtn.on("click", function() { $match.hide(); });
				$match.append($closeBtn);
			}
		}, false);

		$(".idenfy-kyb").each(function() {
			var $container = $(this);
			if ($container.data("kyb-initialized")) return;
			$container.data("kyb-initialized", true);

			var payload = { action: "wp_idenfy_get_kyb_token" };
			var keys = ["client_id", "external_ref", "flow", "theme", "locale", "lifetime", "questionnaire", "questionnaire_required", "tags"];
			$.each(keys, function(_, key) {
				var val = $container.attr("data-" + key.replace(/_/g, "-"));
				if (val !== undefined && val !== "") {
					payload[key] = val;
				}
			});

			$.ajax({
				url: WPIdenfyData.ajaxUrl,
				type: "POST",
				cache: false,
				dataType: "json",
				data: payload
			}).done(function(response) {
				if (!response.success) {
					var msg = (response.data && response.data.message) ? response.data.message : WPIdenfyData.i18n.error;
					$container.html('<p class="idenfy-kyb-error"></p>');
					$container.find(".idenfy-kyb-error").text(msg);
					return;
				}
				var iframe = document.createElement("iframe");
				iframe.src = "https://kyb.ui.idenfy.com/welcome?authToken=" + encodeURIComponent(response.data.token);
				iframe.className = "idenfy-kyb-iframe";
				iframe.setAttribute("allow", "camera; fullscreen");
				iframe.setAttribute("allowfullscreen", "");
				iframe.setAttribute("webkitallowfullscreen", "");
				iframe.setAttribute("mozallowfullscreen", "");
				$container.empty().append(iframe);
			}).fail(function() {
				$container.html('<p class="idenfy-kyb-error"></p>');
				$container.find(".idenfy-kyb-error").text(WPIdenfyData.i18n.NSError);
			});
		});
	});
})(jQuery);