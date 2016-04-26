(function(document) {
	var flashNode = document.getElementById("flash-messages"),
		panelNode = document.getElementById("auth-panel"),
		loginForm = document.getElementById("login-form");

	if (flashNode || panelNode || loginForm) require(["jquery"], function(jQuery) {
		jQuery.ajax({
			method:   "POST",
			url:      "/action/auth/check.php",
			dataType: "json"
		}).done(function(data) {
			var flash = data.flash || {},
				user = data.user || null,
				flashTypes = {error: "danger", alert: "warning", info: "info", success: "success"},
				t1, t2, t3, node;

			if (loginForm && user) {
				flash.info = flash.info || [];


				flash.info.push('Вы уже вошли на сайт под именем «' + user.name + '». <a href="/action/auth/logout.php">Выйти.</a>');
			}

			if (flashNode) {
				jQuery(flashNode).html("");

				for (t1 in flashTypes) {
					if (flashTypes.hasOwnProperty(t1)) {
						t2 = flash[t1] || [];
						for (t3 in flash[t1]) {
							if (flash[t1].hasOwnProperty(t3)) {
								node = jQuery("<div>").addClass("alert").addClass("alert-" + flashTypes[t1]);
								node.html('<button type="button" class="close" data-dismiss="alert">×</button>' + flash[t1][t3]);
								jQuery(flashNode).append(node);
							}
						}
					}
				}
			}
		});
	});
})(document);
