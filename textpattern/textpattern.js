		function verify(msg) { return confirm(msg); }

		function toggleDisplay(obj_id) {
			if (document.getElementById){
				var obj = document.getElementById(obj_id);
				if (obj.style.display == '' || obj.style.display == 'none'){
					var state = 'block';
				} else {
					var state = 'none';
				}
				obj.style.display = state;
			}
		}

		function selectall() {
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) elem[i].checked = true;
		}
		
		function deselectall() {
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) elem[i].checked = false;
		}
		
		function selectrange() {
			var inrange = false;
			var cnt = 0;
			var elem = window.document.longform.elements;
			cnt = elem.length;
			for (var i=0; i < cnt; i++) {
				if (elem[i].type == 'checkbox') {
					if (elem[i].checked == true) {
						if (!inrange) inrange = true;
						else inrange = false;
					}
					if (inrange) elem[i].checked = true;
				}
			}
		}
		
		// By S.Andrew -- http://www.scottandrew.com/
		function addEvent(elm, evType, fn, useCapture) {
			if (elm.addEventListener) {
				elm.addEventListener(evType, fn, useCapture);
				return true;
			}
			else if (elm.attachEvent) {
				var r = elm.attachEvent('on' + evType, fn);
				return r;
			}
			else {
				elm['on' + evType] = fn;
			}
		}
		
		function cleanSelects(){
			withsel = document.getElementById('withselected');
			if(withsel.options[withsel.selectedIndex].value != '') return (withsel.selectedIndex = 0);
		}