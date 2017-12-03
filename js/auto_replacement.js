"use strict";

document.getElementById('submit').addEventListener('click', function(){
	//event.preventDefault();
	function showHint(str) {
		if(str.length == 0) {
			document.getElementById('return').getElementById.innerHTML = "";
			return;
		} else {
			var xhr = new XMLHttpRequest();
			xhr.onreadystatechange = function() {
				if(this.readyState == 4 && this.status == 200) {
					document.getElementById("return").innerHTML = this.responseText;
				}
			};
			xhr.open("POST", "config.php" + str, true);
			xhr.send();
		}
	}
}); 

