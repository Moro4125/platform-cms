require(["jquery", "mustache", "bootstrap"], function(jQuery, Mustache) {
	var fileInputFlag = false,
		templates = [],
		listCheckboxCount = 0,
		checkedListCheckboxCount = 0,
		match, i,
		updateListCheckbox = function(target) {
		if (target.checked) {
			jQuery(target).closest("tr").addClass("highlight");
		}
		else {
			jQuery(target).closest("tr").removeClass("highlight");
		}
	};

	jQuery("script[type='text/x-mustache']").each(function() {
		var script = jQuery(this), template = script.html();
		templates[script.attr("id")] = template;
		Mustache.parse(template);
	});

	jQuery(".table.with-highlight input.sr-only").each(function(index, element) {
		jQuery(element).on("change", function(event) {
			updateListCheckbox(event.target);
			checkedListCheckboxCount += jQuery(event.target).prop("checked") ? 1 : -1;
			jQuery("#select_all_checkbox").prop("checked", checkedListCheckboxCount == listCheckboxCount);
		});

		jQuery(element).closest("tr").find("td").on("click", function(event) {
			var target = jQuery(event.target);

			if (!target.closest("a").length && !target.closest("button").length) {
				element.checked = !element.checked;
				jQuery(element).trigger("change");
			}
		});

		updateListCheckbox(element);
		checkedListCheckboxCount += jQuery(element).is(":checked") + 0;
		listCheckboxCount++;
	});

	jQuery("#select_all_checkbox").each(function(index, element) {
		jQuery(element).on("change", function(event) {
			jQuery(event.target).closest('form').find("input.sr-only").each(function(index, input) {
				if (input != element) {
					jQuery(input).prop("checked", jQuery(element).prop("checked"));
					updateListCheckbox(input);
				}
			});
		});

		jQuery(element).prop("checked", checkedListCheckboxCount == listCheckboxCount);
	});

	jQuery("input.file-loading[type=file]").each(function() {
		if (!fileInputFlag) {
			require(["bootstrap-fileinput"], function() {
				$("input.file-loading[type=file]").fileinput({
					language: "ru",
					allowedFileExtensions: ["jpg", "jpeg", "png", "gif"]
				});
			});
		}

		fileInputFlag = true;
	});

	jQuery("div.img-cropper[role=tabpanel]").each(function() {
		var tabpanel = this;
		require(["bootstrap-cropper"], function() {
			var cropper = jQuery(".cropper", tabpanel),
				panelId = jQuery(tabpanel).attr("id"),
				preview = jQuery(".img-preview-container", tabpanel),
				prLabel = preview.closest(".panel").find(".label-default"),
				prSizeR = preview.closest(".panel").find(".label-info"),
				showTab = function(tab) {
					var kind = tab.data("kind"),
						ratio = parseFloat(tab.data("ratio")),
						prefix = tab.data("id-prefix");

					cropper.cropper("destroy");
					prLabel.html(kind);
					preview.attr("class", "img-preview-container img-preview-" + kind).find(".img-preview").attr("style", null);
					preview.toggleClass("hide-mask", jQuery("#admin_update_hide_mask"+kind).is(":checked"));
					preview.toggleClass("w-" + jQuery("#admin_update_watermark"+kind).val(), true);

					cropper.cropper({
						aspectRatio: ratio,
						autoCrop: true,
						autoCropArea: 1,
						responsive: true,
						rotatable: false,
						preview: "#" + panelId + " .img-preview",
						data: {
							x:      parseInt(jQuery("#" + prefix + "_x").val()),
							y:      parseInt(jQuery("#" + prefix + "_y").val()),
							width:  parseInt(jQuery("#" + prefix + "_w").val()),
							height: parseInt(jQuery("#" + prefix + "_h").val()),
							rotate: 0
						},
						crop: function(data) {
							prSizeR.html(Math.round(data.width) + "x" + Math.round(data.height));
							jQuery("#" + prefix + "_x").val(Math.round(data.x));
							jQuery("#" + prefix + "_y").val(Math.round(data.y));
							jQuery("#" + prefix + "_w").val(Math.round(data.width));
							jQuery("#" + prefix + "_h").val(Math.round(data.height));
						}
					});

					tab.tab("show");
				};

			jQuery("ul[role=tablist] a", tabpanel).click(function(event) {
				var tab = jQuery(event.currentTarget);
				if (tab.parent().hasClass("disabled")) {
					return false;
				}
				event.preventDefault();
				window.location.hash = $(this).attr('href').replace(/^./, "#tab");
				showTab(tab);
			}).each(function() {
				var tab = jQuery(this);
				tab.find("input[type=checkbox]").each(function() {
					var checkbox = jQuery(this);
					tab.parent().toggleClass("disabled", !checkbox.is(':checked'));
					checkbox.on("change", function() {
						tab.parent().toggleClass("disabled", !checkbox.is(':checked'));
					});
				});
			});

			jQuery("ul[role=tablist] label", tabpanel).click(function(event) {
				event.stopPropagation();
			});

			jQuery(".tab-pane input[type=checkbox]", tabpanel).on("change", function() {
				preview.toggleClass("hide-mask", jQuery(this).is(":checked"));
			});

			jQuery(".tab-pane select", tabpanel).on("change", function() {
				for (var i = 0; i <= 4; i++) {
					preview.toggleClass("w-" + i, false);
				}
				preview.toggleClass("w-" + jQuery(this).val(), true);
			});

			(function(){
				var target, search = "a[href=\""+(window.location.hash || "").replace(/^.tab/, "#")+"\"]";

				jQuery("ul[role=tablist] li", tabpanel).each(function() {
					if (jQuery(this).is(":first-child") || jQuery(search, this).length){
						target = jQuery("a", this)[0];
					}
				});

				target && showTab(jQuery(target));
			})();
		});
	});

	jQuery("textarea").each(function() {
		var element = this;
		require(["textarea_autosize"], function(autosize) {
			autosize(element);
		});
	});

	jQuery("ul[role=tablist] a").click(function(event) {
		jQuery("#" + (jQuery(event.currentTarget).attr("aria-controls") || "id") + " textarea").each(function() {
			var element = this;
			setTimeout(function() {
				require(["textarea_autosize"], function(autosize) {
					var event = document.createEvent('Event');
					event.initEvent('autosize:update', true, false);
					element.dispatchEvent(event);
				});
			}, 100);
		});
	});

	jQuery(".m-select2").each(function() {
		var element = this;
		require(["select2"], function() {
			var template = templates[jQuery(element).data("template")];

			require(["/assets/select2/dist/js/i18n/ru.js"], function() {
				jQuery(element).select2(jQuery(element).is("*[data-ajax--url]") ? {
					language: "ru",
					ajax: {
						delay: 200,
						data: function (params) {
							return {
								q: params.term, // search term
								page: params.page || 1
							};
						},
						processResults: function (data) {
							return {
								pagination: { more: (data.total > data.page * data.chunk) },
								results: jQuery.map(data.list, function(item) {
									return item;
								})
							};
						}
					},
					templateSelection: function(item) {
						item.isList = false;

						if (typeof item.name != "undefined") {
							return Mustache.render(template, item);
						}
						else if (typeof item.element.text != "undefined") {
							return Mustache.render(template, jQuery(element).data("json")[item.id]);
						}

						return item;
					},
					templateResult: function(item) {
						item.isList = true;

						if (typeof item.id != "undefined") {
							return Mustache.render(template, item);
						}

						return item;
					},
					escapeMarkup: function(html) {
						return html;
					},
					allowClear: true,
					placeholder: {
						id: "00000000000000000000000000000000",
						name: null
					}
				} : {
					language: "ru"
				});
				setTimeout(function() {
					jQuery(element).removeClass("m-select2");
				}, 100);
			});
		});
	});

	jQuery("*[data-lock]:first").each(function() {
		var handler,
			check = function() {
			jQuery.ajax({
				url: window.location.href.split('?', 1)[0] + "?lock=Y"
			}).done(function() {
				handler = setTimeout(check, 15000);
			}).fail(function() {
				handler = null;
				alert("Блокировка с материала снята!");
			});
		};

		handler = setTimeout(function() {
			check();
		}, 15000);

		jQuery(window).unload(function() {
			handler && clearTimeout(handler);
			handler = null;
			jQuery.ajax({
				async: false,
				url: window.location.href.split('?', 1)[0] + "?lock=N"
			})
		});
	});

	if (window.location.hash && (match = window.location.hash.match(/(?:[#&])selected=(\d+(?:,\d+)*)/))) {
		match = match[1].split(",");

		for (i = match.length - 1; i >= 0; i--) {
			jQuery("#admin_list_id" + match[i]).each(function(index, element) {
				element.checked = true;
				jQuery(element).trigger("change");
			});
		}

		window.location.hash = window.location.hash.replace(/selected=(\d+(?:,\d+)*)/, "").replace(/&&/, "&").replace(/^\#&|&$/, "");
	}

	if (window.location.hash == "" && window.location.href.indexOf("#") && typeof history != "undefined") {
		history.pushState("", document.title, window.location.pathname + window.location.search);
	}
});