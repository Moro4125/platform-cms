require(["jquery"], function(jQuery) {
	var self = {},
		_replaceChars = {
			shortMonths: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
			longMonths: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
			shortDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
			longDays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],

			// Day
			d: function() { return (this.getDate() < 10 ? '0' : '') + this.getDate(); },
			D: function(self) { return self._replaceChars.shortDays[this.getDay()]; },
			j: function() { return this.getDate(); },
			l: function(self) { return self._replaceChars.longDays[this.getDay()]; },
			N: function() { return this.getDay() + 1; },
			S: function() { return (this.getDate() % 10 == 1 && this.getDate() != 11 ? 'st' : (this.getDate() % 10 == 2 && this.getDate() != 12 ? 'nd' : (this.getDate() % 10 == 3 && this.getDate() != 13 ? 'rd' : 'th'))); },
			w: function() { return this.getDay(); },
			z: function() {	var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((this - d) / 86400000); }, // Fixed now
			// Week
			W: function() { var d = new Date(this.getFullYear(), 0, 1); return Math.ceil((((this - d) / 86400000) + d.getDay() + 1) / 7); }, // Fixed now
			// Month
			F: function(self) { return self._replaceChars.longMonths[this.getMonth()]; },
			m: function() { return (this.getMonth() < 9 ? '0' : '') + (this.getMonth() + 1); },
			M: function(self) { return self._replaceChars.shortMonths[this.getMonth()]; },
			n: function() { return this.getMonth() + 1; },
			t: function() {	var d = new Date();	return new Date(d.getFullYear(), d.getMonth(), 0).getDate()	}, // Fixed now, gets #days of date
			// Year
			L: function() { var year = this.getFullYear(); return (year % 400 == 0 || (year % 100 != 0 && year % 4 == 0)); },   // Fixed now
			o: function() { var d = new Date(this.valueOf()); d.setDate(d.getDate() - ((this.getDay() + 6) % 7) + 3); return d.getFullYear(); }, //Fixed now
			Y: function() { return this.getFullYear(); },
			y: function() { return ('' + this.getFullYear()).substr(2); },
			// Time
			a: function() { return this.getHours() < 12 ? 'am' : 'pm'; },
			A: function() { return this.getHours() < 12 ? 'AM' : 'PM'; },
			B: function() { return Math.floor((((this.getUTCHours() + 1) % 24) + this.getUTCMinutes() / 60 + this.getUTCSeconds() / 3600) * 1000 / 24); }, // Fixed now
			g: function() { return this.getHours() % 12 || 12; },
			G: function() { return this.getHours(); },
			h: function() { return ((this.getHours() % 12 || 12) < 10 ? '0' : '') + (this.getHours() % 12 || 12); },
			H: function() { return (this.getHours() < 10 ? '0' : '') + this.getHours(); },
			i: function() { return (this.getMinutes() < 10 ? '0' : '') + this.getMinutes(); },
			s: function() { return (this.getSeconds() < 10 ? '0' : '') + this.getSeconds(); },
			u: function() { var m = this.getMilliseconds(); return (m < 10 ? '00' : (m < 100 ? '0' : '')) + m; },
			// Timezone
			e: function() { return 'Not Yet Supported'; },
			I: function() { return 'Not Yet Supported'; },
			O: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + '00'; },
			P: function() { return (-this.getTimezoneOffset() < 0 ? '-' : '+') + (Math.abs(this.getTimezoneOffset() / 60) < 10 ? '0' : '') + (Math.abs(this.getTimezoneOffset() / 60)) + ':00'; }, // Fixed now
			T: function() { var m = this.getMonth(); this.setMonth(0); var result = this.toTimeString().replace(/^.+ \(?([^\)]+)\)?$/, '$1'); this.setMonth(m); return result; },
			Z: function() { return -this.getTimezoneOffset() * 60; },
			// Full Date/Time
			c: function(self) { return self.format("Y-m-d\\TH:i:sP"); }, // Fixed now
			r: function() { return typeof this.toLocaleString == "function" ? this.toLocaleString() : this.toString(); },
			U: function() { return this.getTime() / 1000; }
		};

	_replaceChars.shortMonths = ["Янв","Фев","Мар","Апр","Май","Июн","Июл","Авг","Сен","Окт","Ноя","Дек"];
	_replaceChars.longMonths  = ["Январь","Февраль","Март","Апрель","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"];
	_replaceChars.shortDays   = ["Вск","Пон","Втр","Срд","Чет","Пят","Суб"];
	_replaceChars.longDays    = ["Воскресенье","Понедельник","Вторник","Среда","Четверг","Пятница","Суббота"];

	/**
	 * Представление даты в виде заданном шаблоном.
	 * @public
	 * @param {String} format
	 * @param {Date} time
	 * @param {Boolean} utc
	 */
	var format = function(format, time, utc) {
		var returnStr = '',
			replace = _replaceChars,
			i,
			utcTime,
			curChar;

		time = time || new Date();

		if (utc) {
			utcTime = time.getTime();
			time.setMinutes(time.getMinutes() - time.getTimezoneOffset());
		}

		for (i = 0; i < format.length; i++) {
			curChar = format.charAt(i);

			if (i - 1 >= 0 && format.charAt(i - 1) == "\\") {
				returnStr += curChar;
			}
			else if (replace[curChar]) {
				returnStr += replace[curChar].call(time, self);
			}
			else if (curChar != "\\") {
				returnStr += curChar;
			}
		}

		if (utc) {
			time.setTime(utcTime);
		}

		return returnStr;
	};

	self._replaceChars = _replaceChars;

	jQuery("time[data-timestamp][data-format]").each(function() {
		var self = jQuery(this),
			time = self.data("timestamp"),
			view = self.data("format"),
			date = new Date();

		date.setTime(time * 1000);
		self.html(format(view, date));
	});
});