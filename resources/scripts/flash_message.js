(function() {
	var flashes = document.querySelectorAll('.alert-success'), i,
		addListener = function(flash) {
			var button = flash.getElementsByTagName('button'),
				close  = function() {
					flash.parentNode.removeChild(flash);
				};

			if (button.length) {
				if (!button[0].addEventListener) {
					button[0].attachEvent("onclick", close);
				}
				else {
					button[0].addEventListener('click', close);
				}
			}
		};

	for (i = flashes.length; i; i--) {
		addListener(flashes[i-1]);
	}
})();