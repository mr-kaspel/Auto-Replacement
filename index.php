<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Auto Replacement</title>
	<link rel="shortcut icon" href="/img/ico/log_i.ico" type="image/x-icon">
	<link rel="stylesheet" href="css/style.css">
	<link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
	<script
  src="https://code.jquery.com/jquery-3.2.1.min.js"
  integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4="
  crossorigin="anonymous"></script>
</head>
<body>
	<header><h1>auto replacement</h1></header>
	<div class="flag">ar.$ <i class="fa fa-terminal" aria-hidden="true"></i></div>
	<section class="wrapper">
		<div class="meanings">
			<div class="meanings_headr">database</div>
			<form id="substitution" action="javascript:void(null);" onsubmit="call()" method="POST">
				<label for="url">url BD</label> <br><i class="fa fa-link" aria-hidden="true"></i>
				<input name="url" type="text" id="url" placeholder="192.168.0.106" required> <br>

				<label for="login">Имя пользователя BD</label> <br><i class="fa fa-user" aria-hidden="true"></i>
				<input name="login" type="text" id="login" placeholder="root" required> <br>

				<label for="passwd">Пароль пользователя BD</label> <br><i class="fa fa-ellipsis-h" aria-hidden="true"></i>
				<input name="passwd" type="password" id="passwd" placeholder="password"> <br>

				<label for="name_bd">Наименование BD</label> <br><i class="fa fa-database" aria-hidden="true"></i>
				<input name="namebd" type="text" id="name_bd" placeholder="bd_" required> <br>

				<label for="val">Искомое значение</label> <br><i class="fa fa-scissors" aria-hidden="true"></i>
				<input name="val" type="text" id="val" placeholder="text" required> <br>

				<label for="replace">Чем заменить</label> <br><i class="fa fa-header" aria-hidden="true"></i>
				<input name="replace" type="text" id="replace" placeholder="text 2" required> <br>

				<input name="submit" id="submit" type="submit" value="Заменить">
			</form>
		</div>
	</section>
	<section class="ptogress">
		
	</section>
	<section class="ptogress">
		<div id="return"></div>
	</section>
	<footer>2017 &copy;</footer>
	<!--<script async src="js/auto_replacement.js"></script> -->
	<script type="text/javascript" language="javascript">
		if(document.getElementById('return').innerHTML.lenght <= 0){
			alert('sesf');
		}
	function call() {
		var msg = $('#substitution').serialize();
				$.ajax({
					type: 'POST',
					url: 'config.php',
					data: msg,
					success: function(data) {
						$('#return').html(data);
					},
					error:  function(xhr, str){
			alert('Возникла ошибка: ' + xhr.responseCode);
					}
				});
		}
</script>
</body>
</html>