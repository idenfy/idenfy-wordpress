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
				$(enableSel).prop("disabled", false).removeAttr("disabled");
			}

			var syncSel = $match.attr("data-sync-field");
			if (syncSel) {
				$(syncSel).val("completed").trigger("change");
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