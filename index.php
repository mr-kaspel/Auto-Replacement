<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Auto Replacement</title>
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<header><h1>auto replacement</h1></header>
	<section class="wrapper">
		<div class="meanings">
			<div class="meanings_headr">test</div>
			<form action="config.php" method="POST">
				<label for="url">url BD</label> <br>
				<input name="url" type="text" id="url" placeholder="192.168.0.106"> <br>

				<label for="login">Имя пользователя BD</label> <br>
				<input name="login" type="text" id="login" placeholder="root"> <br>

				<label for="passwd">Пароль пользователя BD</label> <br>
				<input name="passwd" type="password" id="passwd" placeholder="password"> <br>

				<label for="name_bd">Наименование BD</label> <br>
				<input name="namebd" type="text" id="name_bd" placeholder="bd_"> <br>

				<label for="val">Искомое значение</label> <br>
				<input name="val" type="text" id="val" placeholder="text"> <br>

				<label for="replace">Чем заменить</label> <br>
				<input name="replace" type="text" id="replace" placeholder="text 2"> <br>

				<input id="submit" type="submit" placeholder="Заменить">
			</form>
		</div>
	</section>
	<section class="ptogress">
		<progress value="10" max="100"></progress>
	</section>
</body>
</html>