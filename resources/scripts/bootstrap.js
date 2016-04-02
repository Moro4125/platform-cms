require(["jquery", "mustache", "bootstrap"], function(jQuery, Mustache) {
	var fileInputFlag = false,
		handler = null,
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

	jQuery("input.file-loading[type=file]:not([data-upload-url])").each(function() {
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

	jQuery("input.file-loading[type=file][data-upload-url]").each(function() {
		var self = jQuery(this),
			ajaxUrl = self.data("upload-url"),
			uploadExtraData = {};

		if (!fileInputFlag) {
			jQuery("input[type=hidden]", self.closest('form')).each(function() {
				var input = jQuery(this);
				uploadExtraData[input.attr("name")] = input.val();
			});

			require(["bootstrap-fileinput"], function() {
				jQuery.ajax({
					url: ajaxUrl,
					type: "GET",
					dataType: "json"
				}).done(function(data) {
					self.fileinput({
						language: "ru",
						dropZoneEnabled: false,
						allowedPreviewTypes: false,
						uploadAsync: true,
						uploadExtraData: uploadExtraData,
						uploadUrl: ajaxUrl,
						mainClass: "b-file-upload",
						previewClass: "b-file-upload",
						overwriteInitial: false,
						initialPreview: data.initialPreview,
						initialPreviewConfig: data.initialPreviewConfig
					});
				});
			});
		}

		fileInputFlag = true;
	});

	jQuery("div.img-cropper[role=tabpanel]").each(function() {
		var tabPanel = this;
		require(["bootstrap-cropper"], function() {
			var cropper = jQuery(".cropper", tabPanel),
				panelId = jQuery(tabPanel).attr("id"),
				preview = jQuery(".img-preview-container", tabPanel),
				prLabel = preview.closest(".panel").find(".label-default"),
				prPoint = preview.closest(".panel").find(".label-info"),
				prSizeR = preview.closest(".panel").find(".label-success"),
				showTab = function(tab) {
					var kind = tab.data("kind"),
						ratio = parseFloat(tab.data("ratio")),
						prefix = tab.data("id-prefix"),
						mask = jQuery("#admin_update_hide_mask"+kind),
						watermark = jQuery("#admin_update_watermark"+kind);

					cropper.cropper("destroy");
					prLabel.html(kind);
					preview.attr("class", "img-preview-container img-preview-" + kind).find(".img-preview").attr("style", null);
					preview.toggleClass("hide-mask", mask.length && mask.is(":checked"));
					preview.toggleClass("w-" + (watermark.length ? watermark.val() : "0"), true);

					cropper.cropper({
						aspectRatio: ratio,
						autoCrop: true,
						autoCropArea: 0.995,
						responsive: true,
						rotatable: false,
						zoomable: false,
						viewMode: 1,
						preview: "#" + panelId + " .img-preview",
						data: {
							x:      parseInt(jQuery("#" + prefix + "_x").val()),
							y:      parseInt(jQuery("#" + prefix + "_y").val()),
							width:  parseInt(jQuery("#" + prefix + "_w").val()),
							height: parseInt(jQuery("#" + prefix + "_h").val()),
							rotate: 0
						},
						crop: function(data) {
							prPoint.html(Math.round(data.x) + "x" + Math.round(data.y));
							prSizeR.html(Math.round(data.width) + "x" + Math.round(data.height));
							jQuery("#" + prefix + "_x").val(Math.round(data.x));
							jQuery("#" + prefix + "_y").val(Math.round(data.y));
							jQuery("#" + prefix + "_w").val(Math.round(data.width));
							jQuery("#" + prefix + "_h").val(Math.round(data.height));
						}
					});

					tab.tab("show");
				};

			jQuery("ul[role=tablist] a", tabPanel).click(function(event) {
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

			jQuery("ul[role=tablist] label", tabPanel).click(function(event) {
				event.stopPropagation();
			});

			jQuery(".tab-pane input[type=checkbox]", tabPanel).on("change", function() {
				preview.toggleClass("hide-mask", jQuery(this).is(":checked"));
			});

			jQuery(".tab-pane select", tabPanel).on("change", function() {
				for (var i = 0; i <= 4; i++) {
					preview.toggleClass("w-" + i, false);
				}
				preview.toggleClass("w-" + jQuery(this).val(), true);
			});

			(function(){
				var target, search = "a[href=\""+(window.location.hash || "").replace(/^.tab/, "#")+"\"]";

				jQuery("ul[role=tablist] li", tabPanel).each(function() {
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
					// allowClear: true,
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
		var stamp,
			check = function() {
			jQuery.ajax({
				method: "POST",
				url: window.location.href.split('?', 1)[0] + "?lock=Y"
			}).done(function(text) {
				handler = setTimeout(check, 15000);
				stamp = text;
			}).fail(function() {
				if (handler) {
					handler = null;
					alert("Блокировка с материала снята!");
				}
			});
		};

		if (!handler) {
			handler = setTimeout(function() {
				check();
			}, 15000);

			jQuery(window).unload(function() {
				handler && clearTimeout(handler);
				handler = null;
				jQuery.ajax({
					async: false,
					method: "POST",
					data: {"stamp": stamp},
					url: window.location.href.split('?', 1)[0] + "?lock=N"
				})
			});
		}
	});

	jQuery("#comment-area").each(function() {
		var target = jQuery(this);
		jQuery("#admin_update_comment").each(function() {
			var self = jQuery(this),
				text = "Пояснение к изменениям или просто комментарий к записи",
				node = jQuery("<textarea>").attr("class", "form-control form-control").attr("placeholder", text);

			target.append(node);
			node.on({
				change: function() {
					self.val(node.val());
				}
			});

			require(["textarea_autosize"], function(autosize) {
				autosize(node);
			});
		});
	});

	jQuery("#buttons-area").each(function() {
		var target = jQuery(this);
		jQuery("#admin_update_commit,#admin_update_apply,#admin_update_cancel").each(function() {
			var self = jQuery(this),
				node = jQuery("<button>").attr("type", "submit").attr("class", self.attr("class")).html(self.html());

			target.append(node);
			node.on({
				click: function(event) {
					event.stopPropagation();
					event.preventDefault();
					self.click();
				}
			});
		});
	});

	jQuery("#admin_update_cancel,#admin_update_delete").each(function() {
		jQuery(this).on({
			click: function(event) {
				jQuery(event.target).closest("form").find("input[required]").each(function() {
					jQuery(this).val() || jQuery(this).val("-");
				});
			}
		});
	});

	jQuery(".history_diff").each(function() {
		jQuery(this).on({
			click: function(event) {
				var self = jQuery(this),
					that = this,
					modal = jQuery("#diffModal");

				event.preventDefault();
				modal.modal();
				jQuery(".modal-content", modal).html("Расчёт изменений...");

				setTimeout(function() {
					//noinspection JSPotentiallyInvalidConstructorUsage
					var service = new diff_match_patch(),
						pattern = /&para;|\r|\n/g,
						list = [],
						from,
						next,
						text,
						diff,
						html,
						i;

					from = self.data("from");
					text = jQuery("#" + from).data("text");

					jQuery(".history_diff").each(function() {
						var self = jQuery(this);
						if (self.data("from") == from) {
							list.push(this);
						}
					});

					for (i = 0; i < list.length; i = (list[i] != that) ? i + 1 : list.length) {
						next = text;
						text = service.patch_apply(service.patch_fromText(jQuery(list[i]).data("diff")), text)[0];
					}

					diff = service.diff_main(text, next);
					html = service.diff_prettyHtml(diff).replace(pattern, "");

					html = ''
						+ '<div class="modal-header">'
						+ '<button type="button" class="close" data-dismiss="modal" aria-label="Close">'
						+ '<span aria-hidden="true">×</span>'
						+ '</button>'
						+ '<h3 class="modal-title">'
						+ self.data("title")
						+ '</h3>'
						+ '</div>'
						+ '<div class="modal-body b-diff-text">'
						+ html
						+ '</div>';

					jQuery(".modal-content", modal).html(html);
				},1);
			}
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

	if (window.location.hash && (window.location.hash.match(/(?:[#&])close=Y/))) {
		window.close();
	}

	if (window.location.hash == "" && window.location.href.indexOf("#") && typeof history != "undefined") {
		history.pushState("", document.title, window.location.pathname + window.location.search);
	}
});