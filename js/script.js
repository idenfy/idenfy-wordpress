;(function($) {
	var KYC_ORIGIN = "https://ui.idenfy.com";
	var KYB_ORIGIN = "https://kyb.ui.idenfy.com";

	function attrIsTrue($el, name) {
		var v = $el.attr(name);
		return v === "true" || v === "1";
	}

	// Build the shared modal shell and inject an iframe pointing at `src`.
	// `iframeClass` lets the postMessage routing tell KYC and KYB iframes apart.
	function buildIdenfyModal(src, iframeClass) {
		var $overlay = $('<div class="idenfy-kyc-modal"></div>');
		var $inner = $('<div class="idenfy-kyc-modal-inner"></div>');
		var $close = $('<button type="button" class="idenfy-kyc-modal-close" aria-label="Close">&times;</button>');

		var iframe = document.createElement("iframe");
		iframe.src = src;
		iframe.className = iframeClass;
		iframe.setAttribute("allow", "camera; fullscreen");
		iframe.setAttribute("allowfullscreen", "");
		iframe.setAttribute("webkitallowfullscreen", "");
		iframe.setAttribute("mozallowfullscreen", "");

		$inner.append($close).append(iframe);
		$overlay.append($inner);
		$("body").append($overlay);
		$("body").addClass("idenfy-kyc-modal-open");

		$close.on("click", function() { closeIdenfyModal($overlay); });
		return $overlay;
	}

	function closeIdenfyModal($overlay) {
		$overlay.remove();
		if ($(".idenfy-kyc-modal").length === 0) {
			$("body").removeClass("idenfy-kyc-modal-open");
		}
	}

	function appendModalCloseButton($overlay, $btn) {
		var $inner = $overlay.find(".idenfy-kyc-modal-inner");
		if ($inner.find(".idenfy-kyb-close").length) return;
		var closeText = $btn.attr("data-close-button-text") || WPIdenfyData.i18n.close;
		var $closeBtn = $('<button type="button" class="idenfy-kyb-close"></button>').text(closeText);
		$closeBtn.on("click", function() { closeIdenfyModal($overlay); });
		$inner.append($closeBtn);
	}

	function openIdenfyKyc($btn, token) {
		var src = KYC_ORIGIN + "/?authToken=" + encodeURIComponent(token);
		var $overlay = buildIdenfyModal(src, "idenfy-kyc-iframe");
		$overlay.data("idenfy-btn", $btn);
	}

	function openIdenfyKyb($btn, token) {
		var src = KYB_ORIGIN + "/welcome?authToken=" + encodeURIComponent(token);
		var $overlay = buildIdenfyModal(src, "idenfy-kyb-iframe");
		$overlay.data("idenfy-btn", $btn);
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
				closeIdenfyModal($overlay);
			} else {
				appendModalCloseButton($overlay, $btn);
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

		appendModalCloseButton($overlay, $btn);
	}

	function handleKybResult($btn, $overlay, data) {
		$btn[0].dispatchEvent(new CustomEvent("idenfy:kyb:complete", {
			detail: { status: data.status, raw: data },
			bubbles: true
		}));

		if (data.status !== "success") return;

		$btn.addClass("idenfy-kyb-complete");

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
			closeIdenfyModal($overlay);
		} else {
			appendModalCloseButton($overlay, $btn);
		}
	}

	$(document).ready(function() {
		// KYC: button opens the identity verification flow in a modal.
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

		// KYB: button creates a business verification session and opens it in a modal.
		$(document).on("click", "a.idenfy-kyb-button", function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass("doing-ajax")) return false;
			$btn.addClass("doing-ajax");

			var payload = { action: "wp_idenfy_get_kyb_token" };
			var keys = ["client_id", "external_ref", "flow", "theme", "locale", "lifetime", "questionnaire", "questionnaire_required", "tags"];
			$.each(keys, function(_, key) {
				var val = $btn.attr("data-" + key.replace(/_/g, "-"));
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
				$btn.removeClass("doing-ajax");
				if (!response.success || !response.data || !response.data.token) {
					var msg = (response.data && response.data.message) ? response.data.message : WPIdenfyData.i18n.error;
					alert(msg);
					return;
				}
				openIdenfyKyb($btn, response.data.token);
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
			if (event.origin !== KYB_ORIGIN) return;

			var data = event.data;
			if (typeof data === "string") {
				try { data = JSON.parse(data); } catch (e) { return; }
			}
			if (!data || typeof data !== "object" || !data.status) return;

			var $overlay = null;
			$(".idenfy-kyc-modal").each(function() {
				var iframe = $(this).find("iframe.idenfy-kyb-iframe")[0];
				if (iframe && iframe.contentWindow === event.source) {
					$overlay = $(this);
					return false;
				}
			});
			if (!$overlay) return;

			handleKybResult($overlay.data("idenfy-btn"), $overlay, data);
		}, false);
	});
})(jQuery);
