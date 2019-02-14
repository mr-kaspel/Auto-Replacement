<?php
	header('Content-Type: text/html; charset=utf-8');
	error_reporting(0);
	set_time_limit(15); // 15 seconds for testing
	$arrip = ['127.0.0.1'];
	$bul = 1;
	if(count($arrip) > 0) {
		foreach($arrip as $val) {
			if(strripos($_SERVER['REMOTE_ADDR'], $val) !== false) $bul = 0; break;
		}
	} else {
		$bul = 0;
	}
	if($bul) {
		header("HTTP/1.1 404 Not Found");
		die();
	}

	$userbd = '';
	$db_ ='';
	$passwordbd = '';
	$nameCMS = '';
	$linkbd = '';
	$dirname = __DIR__;
	$report = '';
	$searchval = '';
	$valreplace = '';
	$showrestore = 0;

	$arrCMS = [
		["configuration.php", '\$user', '\$password', '\$db', '\$host', 'Joomla'],
		['wp-config.php', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'DB_HOST', 'WordPress'],
		['site/default/settings.php', 'username', 'password', 'databasename', 'mysqlhost', 'Drupal'],
		['engine/data/dbconfig.php', 'DBUSER', 'DBPASS', 'DBNAME', 'DBHOST', 'DLE'],
		['cfg/connect.inc.php', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_HOST', 'Shop-script'],
		['core/config/connect.inc.php', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_HOST', 'ShopCMS'],
		['dblist/логин.xml', 'DB_USER', 'DB_PASSWORD', 'DB_NAME', 'SQLSERVER', 'WebAsyst'], // nope
		['config/settings.inc.php', '_DB_USER_', '_DB_PASSWD_', '_DB_NAME_', '_DB_SERVER_', 'PrestaShop'],
		['manager/includes/config.inc.php', '\$database_user', '\$database_password', '\$dbase', '\$database_server', 'MODx'],
		['bitrix/php_interface/dbconn.php', '\$DBLogin', '\$DBPassword', '\$DBName', '\$DBHost', 'Bitrix'],
		['phpshop/inc/config.ini', 'user_db', 'pass_db', 'dbase', 'host', 'PHPShop'],
		['modules/core/config/database.php', 'username', 'password', 'database', 'host', 'HostCMS'],
		['docs/config.ini', 'core.login', 'core.password', 'core.dbname', 'core.host', 'UMI'],
		['docs/config.php', '\$dbuser', '\$dbpasswd', '\$dbname', '\$dbhost', 'phpBB'],
		['docs/vars.inc.php', '\$MYSQL_USER', '\$MYSQL_PASSWORD', '\$MYSQL_DB_NAME', '\$MYSQL_HOST', 'NetCat'],
		['docs/_local/config.ini.php', 'DB_User', 'DB_Password', 'DB_Database', 'DB_Host', 'Amiro.CMS']
	];

	function Export_Database($host,$user,$pass,$name, $dirname) {
		$arr = array();
		$dblink = mysql_connect($host,$user, $pass);
		if($dblink) {
			$arr[0] .= '<p>Соединение с сервером установлено.</p>';
		} else {
			$arr[0] .= '<p><mark>Ошибка подключения к серверу баз данных.</mark></p>';
			return $arr;
		}

		$selected = mysql_select_db($name, $dblink);
		if($selected) {
			$arr[0] .= '<p>Подключение к базе данных '.$name.' прошло успешно.</p>';
		} else {
			$arr[0] .= '<p><mark>База данных '.$name.' не найдена или отсутствует доступ.</mark></p>';
			return $arr;
		}

		$mysqli = new mysqli($host,$user,$pass,$name);
		$mysqli->select_db($name);
		$mysqli->query("SET NAMES 'utf8'");
		$queryTables = $mysqli->query('SHOW TABLES');

		while($row = $queryTables->fetch_row()) $target_tables[] = $row[0];
		foreach($target_tables as $table) {
			$result = $mysqli->query('SELECT * FROM '.$table);
			$fields_amount  = $result->field_count;
			$rows_num=$mysqli->affected_rows;
			$res = $mysqli->query('SHOW CREATE TABLE '.$table);
			$TableMLine = $res->fetch_row();
			$content = (!isset($content) ? '' : $content) . $TableMLine[1].";\n";
			$innsert = '';
			preg_match_all('/`[a-zA-Z-_]+`/i', $TableMLine[1], $m);
			foreach($m[0] as $key => $val) if($key > 0) $innsert .= count($m[0])-1 !== $key ? $val.', ' : $val;

			for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter=0) {
				while($row = $result->fetch_row()) {
					if ($st_counter%100 == 0 || $st_counter == 0 ) {
						$content .=  "\nINSERT INTO `".$table."` (".$innsert.") VALUES";
					}
					$content .= "\n(";
					for($j=0; $j<$fields_amount; $j++) {
						$row[$j] = str_replace("\n","\\n", addslashes($row[$j]) );
						if (isset($row[$j]) && iconv_strlen($row[$j]) > 0) {
							$content .= is_numeric($row[$j]) ? $row[$j] : '"'.$row[$j].'"';
						} else {
							$content .= 'NULL';
						}
						if ($j<($fields_amount-1)) {
							$content.= ',';
						}
					}
					$content .=")";
					if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) {
						$content .= ";\n";
					} else {
						$content .= ",";
					}
					$st_counter=$st_counter+1;
				}
			} $content .="\n";
		}

		if(file_exists($dirname.'\\backup_cms_bd')) {
			$arr[0] .= '<p>Папка для бэкапа уже существует: "'.$dirname.'\\backup_cms_bd\\".</p>';
		} else if (!file_exists($dirname.'/backup_cms_bd') && !mkdir($dirname.'/backup_cms_bd', 0777, true)) {
			$arr[0] .= '<p>Не удалось создать директорию для бэкапа!!!!</p>';
			return $arr;
		} else {
			$arr[0] .= '<p>Папка для бэкапа создана: "'.$dirname.'\\backup_cms_bd\\"</p>';
		}

		$backup_name = $name.'_'.date('d-m-Y').'_'.date('H-i-s').".sql";
		$fp = fopen($dirname.'\\backup_cms_bd\\'.$backup_name, "w");
		fwrite($fp, $content);
		fclose($fp);
		$arr[0] .= '<p>Сделана резервная копия bd "'.$name.'".sql.</p>';
		$arr[1] = $dirname.'\\backup_cms_bd\\'.$backup_name;
		return $arr;
	}

	function replaceFileText ($filedir, $val, $valrepl, $option, $dirname) {
		$arr = array();
		if($option == 0) {
			$newdir = substr($filedir, 0, strripos($filedir, '\\'));
			$newdir .= '\\replace_copy_sql';
			if (!file_exists($newdir) && !mkdir($newdir, 0777, true)) {
				$arr[0] .= '<p>Ошибка, значение "'.$val.'" не удалось перезаписать.</p>';
				return $arr;
			} else {
				$arr[0] .= '<p>Создана новая папка для редактирования файла "... /backup_cms_bd/replace_copy_sql/".</p>';
				$newfile = $newdir.'\\';
				$newfile .= substr($filedir, strripos($filedir, '\\')+1);
				if (!copy($filedir, $newfile)) {
					$arr[0] .= '<p><mark>Не удалось создать копию файла, возможно файл отсутствует.</mark></p>';
					return $arr;
				} else {
					$arr[0] .= '<p>Файл скопирован в директорию для редактирования "'.$newfile.'".</p>';
				}
			}
		} else {
			$newfile = $filedir;
		}
		$abbrdir = substr($newfile,strripos($dirname, '\\'));
		$file = file_get_contents($newfile);
		$file = str_replace($val, $valrepl, $file);
		if(file_put_contents($newfile, $file)) {
			$arr[0] .= '<p>Значение "'.$val.'" в файле "'.$abbrdir.'" перезаписано на "'.$valrepl.'".</p>';
			$arr[1] = $newfile;
			return $arr;
		} else {
			$arr[0] .= '<p>Не удалось изменить содержимое файла "'.$newfile.'"</p>';
			return $arr;
		}
	}

	function deletingChildFolders ($fileText) {
		$result = '';
		if(count($fileText) > 4) {
		foreach ($fileText as $key => $value) {
			$arrState = explode('\\', $value);
			$endDir = $arrState[count($arrState)-1];
			$countArr = count($arrState);
			foreach ($fileText as $keyTwo => $valTwo) {
				if($keyTwo !== $key && iconv_strlen($fileText[$key]) > 0) {
					$arrStateTwo = explode('\\', $valTwo);

					if(strpos($arrState[$countArr-1], $arrStateTwo[$countArr-1]) !== false) {
						$strOne = str_replace('
', '', str_replace('\\', '', $valTwo));
						$strTwo = str_replace('
', '', str_replace('\\', '', $fileText[$key]));

						if(iconv_strlen($strOne) > 0 && strpos($strOne, $strTwo) !== false) {
							$fileText[$key] = "";
							//echo $key;
							//unset($fileText[$key]);
						}
					} else {
						continue;
					}
				}
			}
		}
	}
		foreach($fileText as $valecho) if(iconv_strlen($valecho) > 0) $result .= $valecho.'
';
		return $result;
	}

	function searchByContentsOfFiles($searchval, $valreplace, $dirname, $param) {
		//if(file_exists($dirname.'\\state_conf.json')) file_put_contents('state_conf.json', ''); //временное решение
		$folderCheck = '';
		$startSc = time();
		if(isset($searchval)) {
			function search($data, $searchval, $valreplace, $dirname, $param, $startSc) {

				/*if(time() > ($startSc + 9)) {
					exit('Скрипт выполняется, ожидайте завершение!'.'<script>location.reload();</script>');
				}*/
				$folderCheck = false;
				foreach(scandir($data) as $key => $val) {
					$dirN = $data.'\\'.$val;
					$arrimage = ['.gif', '.jpg', '.jpeg', '.png', '.bmp', '.dib', '.svg', '.tif', '.tiff', '.zip', '.rar', '.sql'];
					if($val !== '.' && $val !== '..' && !array_search(substr($val, strripos($val,'.')), $arrimage)) {
						if(is_dir($dirN."\\")){
							$result .= search($dirN, $searchval, $valreplace, $dirname, $param, $startSc);
						} else if(filesize($dirN) < 9999999 && strripos(file_get_contents($dirN), $searchval)) {
							$ab = '';
							/*foreach(file($dirN) as $num => $str) {
								if(strpos($str,  $searchval) !== false) {
									$ab = $num + 1;
									break;
								}
							}*/
							$abbrdiv = substr($dirN,strripos($dirname, '\\'));
							if($param == 1) {
								$result .= replaceFileText($dirN, $searchval, $valreplace, 1, $dirname)[0];
							} else {
								$result .= '<p class="res"><span class="val_result_search"><input type="checkbox"></span><span class="address" full-addr="'.$dirN.'">'.$abbrdiv.'</span><!-- span class="line">line: '.$ab.'</span --><span class="size">'.filesize($dirN).' byte</span></p>';
							}
						}
					}
				}
				//$fd = fopen("state_conf.json", 'a') or die("не удалось создать файл");
				/*$json .= $data.'
';*/
				//$json = deletingChildFolders($json);
				//fwrite($fd, $json);
				//fclose($fd);
				return $result;
			}
			return search($dirname, $searchval, $valreplace, $dirname, $param, $startSc);
		}
	}

	function selectiveRewriting($arrfulladdr, $searchval, $valreplace, $dirname) {
		$arr = array();
		$arr = split('\|\|', $arrfulladdr);
		foreach($arr as $value) {
			if(strlen($value) > 0) {
				$result .= replaceFileText($value, $searchval, $valreplace, 1, $dirname)[0];
			}
		}
		return $result;
	}

	function parseDataCMS($files, $elem) {
		if(preg_match('/'.$elem.'(\s.*=\s|[\'"],\s|[\'"]]\s=\s)[\'"](.*)[\'"]/', $files, $m)) {
				return $m[2];
			}
	}

	foreach($arrCMS as $key => $value) {
		if(file_exists($value[0])) {
			$text = file_get_contents($value[0]);
			$nameCMS = $value[5];
			$userbd = parseDataCMS($text, $value[1]);
			$passwordbd = parseDataCMS($text, $value[2]);
			$db_ = parseDataCMS($text, $value[3]);
			$linkbd = parseDataCMS($text, $value[4]);
			break;
		}
	}

		if($_POST) {
				$userbd = $_POST['login'] ? $_POST['login'] : '';
				$db_ = $_POST['name_bd'] ? $_POST['name_bd'] : '';
				$passwordbd = $_POST['passwd'] ? $_POST['passwd'] : '';
				$linkbd = $_POST['hostbd'] ? $_POST['hostbd'] : '';
				$searchval = $_POST['val'] ? $_POST['val'] : '';
				$valreplace = $_POST['replace'] ? $_POST['replace'] : '';
				$checksearchbd = $_POST['searchbd'] ? $_POST['searchbd'] : false;
				$checkreplactext = $_POST['replacetext'] ? $_POST['replacetext'] : false;
				$textareafulladdr = $_POST['arr-addr'] ? $_POST['arr-addr'] : '';

		switch ($_POST['type_action']) {
			case '1':
				if($checksearchbd && !$checkreplactext) {
					$arr = Export_Database($linkbd, $userbd, $passwordbd, $db_, $dirname);
					$report .= $arr[0];
					$report .= replaceFileText($arr[1], $searchval, $valreplace, 0, $dirname)[0];
					$showrestore = 1;
				} else if(!$checksearchbd && $checkreplactext && strlen($textareafulladdr) > 1) {
					$report .= '<p><b>Были перезаписаны значения в указанных файлах:</b></p>';
					$report .= selectiveRewriting($textareafulladdr, $searchval, $valreplace, $dirname);
				} else if(!$checksearchbd && $checkreplactext) {
					$report .= '<p><b>Были найдены и перезаписаны значения в файлах:</b></p>';
					$report .= searchByContentsOfFiles($searchval, $valreplace, $dirname, 1);
				} else {

				}
				break;
			case '2':
				$report .= Export_Database($linkbd, $userbd, $passwordbd, $db_, $dirname)[0];
				$showrestore = 1;
				break;
			case '3':
				$report .= '<p><b>Number of coincidences: <span class="coincidences"></span></b></p>';
				$report .= searchByContentsOfFiles($searchval, $valreplace, $dirname, 0);
				$showrestore = 2;
				break;
		}
	} else {
		if(count($arrip) == 0) $report .= '<p><mark>Заполните IP в запущенном файле!</mark></p>';
		$report .= strlen($nameCMS) == 0 ? '<p><mark>CMS не определена!</mark> </p> <p>Введите данные учетной записи BD.<p>' : '<p>CMS: <b>'.$nameCMS.'</b></p>';
		$report .= strlen($linkbd) == 0 ? '<p>host_bd: <mark>не найден</mark>.</p>' : '<p>host_bd: <b>найден</b>.</p>';
		$report .= strlen($userbd) == 0 ? '<p>host_bd: <mark>не найден</mark>.</p>' : '<p>login_bd: <b>найден</b>.</p>';
		$report .= strlen($passwordbd) == 0 ? '<p>password_bd: <mark>не найден</mark>.</p>' : '<p>password_bd: <b>найден</b>.</p>';
		$report .= strlen($db_) == 0 ? '<p>name_bd: <mark>не найден</mark>.</p>' : '<p>name_bd: <b>найден</b>.</p>';
		$report .= '<hr> <mark>Бэкап файлов не производится автоматически, убедитесь, что у вас есть резервная копия текущей версии файлов сайта!</mark><br>';
		$report .= '<hr><mark>Если новое имя базы данных не задано, будет перезаписана текущая!</mark>';
		$report .= '<hr><p>Если необходимо сделать только бэкап bd, снимите все доступные чекбоксы. Перед этим необходимо отчистить кэшь, если имеется данная возможность в панели администратора вашей CMS.</p><p>Если необходимо найти файлы, с вхождением определенного значения, отключите все флаги кроме "Поиск в файлах на ftp".<br>После поиска возможно выбрать "Замена вхождений в файлах" и выделить файлы в которых необходимо произвести замену.</p><hr>';
	}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<title>Auto Replacement</title>
</head>
<body>
	<style>
		body {margin: 0;padding: 0;background: #eee;font-family: "Segoe UI", "Source Sans Pro", Calibri, Candara, Arial, sans-serif;font-size: 15px;line-height: 1.58;letter-spacing: -.003em;}.content {height: 100vh;display: -webkit-box;display: -moz-box;display: -ms-flexbox;display: -webkit-flex;display: flex;justify-content: center;align-items:center;}form {width: 300px;background: #fff;padding: 1em;border-radius: 7px;box-shadow: 0 1px 20px 0 rgba(0,0,0,.1);}form > fieldset {border: 1px solid #eee;margin-bottom: 10px;}form > fieldset > legend {text-transform: uppercase;font-size: 12px;}fieldset > ul {list-style: none;margin: 0;padding: 0;}fieldset > ul ul{list-style: none;padding-left: 20px;}form input[type="text"], form input[type="password"] {width: 100%;padding: 5px 7px;border: 1px solid #ebebe4;box-sizing: border-box;}form input#submit, form input#restore {width: 90%;color: #fff;text-transform: uppercase;padding: 7px 15px;margin: 0 5% 0 5%;border: none;border-radius: 7px;cursor: pointer;}form input#submit {margin-bottom: 10px;background: -webkit-linear-gradient(left, #3777ec, #6bcfcb);background: -o-linear-gradient(left, #3777ec, #6bcfcb);background: linear-gradient(to right, #3777ec, #6bcfcb);}form input#restore {background: -webkit-linear-gradient(left, #ec373f, #cf6b79);background: -o-linear-gradient(left, #ec373f, #cf6b79);background: linear-gradient(to right, #ec373f, #cf6b79);}label {position: relative;}.alert_box {width: 300px;display: none;padding: 15px;color: #fff;font-size: 12px;border-radius: 7px;background: #60bdd2;box-shadow: 0 1px 20px 0 rgba(0,0,0,.1);position: absolute;right: -340px;top: -38px;z-index: 2;}.alert_box:before {content: '';border: 10px solid transparent;border-right: 10px solid #60bdd2;position: absolute;top: 40px;left: -6%;}label:not(.off):hover .alert_box {display: inline-block;}.val_result_search {display: none;}span.address {width: 65%;display: inline-block;white-space: nowrap;line-height: 15px;overflow: hidden;-ms-overflow-style: none;}.res:hover span.address {overflow: auto;}span.address::-webkit-scrollbar {width: 0;height: 0;}.arr-addr {display: none;}.off {color: #b1aeae;}.progress {font-size: 14px;width: 300px;margin-left: 20px;word-wrap: break-word;}.progress_header {width: 100%;display: inline-block;font-size: 19px;text-align: center;text-transform: uppercase;background: #fff;border-radius: 7px;}.progress_body {max-height: 520px;overflow: auto;}.progress .progress_body span.size {width: 27%;display: inline-block;text-align: right;color: #d0d0d0;}.progress .progress_body span.line {margin-left: 10px;color: #d0d0d0;}.progress p {margin: 0;border-bottom: 1px solid #e0e0e0;}
	</style>
	<section class="wrapper">
		<div class="content">
			<form id="substitution" action="" method="POST">
				<input type="hidden" name="type_action" id="type_action" value="1">
				<div class="meanings_headr">Replace value <img src="https://img.icons8.com/color/48/000000/finn.png"></div>
				<fieldset>
					<legend>Подключение к bd</legend>
					<ul>
						<li>
							<label for="hostbd">Host</label>
							<input name="hostbd" type="text" id="hostbd" placeholder="127.0.0.1" value="<?=$linkbd;?>" required>
						</li>
						<li>
							<label for="login">Login</label>
							<input name="login" type="text" id="login" placeholder="root" value="<?=$userbd;?>" required>
						</li>
						<li>
							<label for="passwd">Password</label>
							<input name="passwd" type="password" id="passwd" placeholder="password" value="<?=$passwordbd;?>">
						</li>
						<li>
							<input name="show_pass" id="show_pass" type="checkbox" onClick="showPass(this.checked)">
							<label class="show_pass_label" for="show_pass">Показать пароль</label>
						</li>
						<li>
							<label for="name_bd">DB</label>
							<input name="name_bd" type="text" id="name_bd" placeholder="bd_" value="<?=$db_;?>" required>
						</li>
					</ul>
				</fieldset>
				<fieldset>
					<legend>Значения для замены</legend>
					<ul>
						<li>
							<label for="val">Искомое значение</label>
							<input name="val" type="text" id="val" placeholder="text 0" value="<?=$searchval;?>" required>
						</li>
						<li>
							<label for="replace">Чем заменить</label>
							<input name="replace" type="text" id="replace" placeholder="text 1" value="<?=$valreplace;?>" required>
						</li>
					</ul>
				</fieldset>
				<fieldset>
					<legend>Где искать?</legend>
						<ul>
							<li>
								<input name="searchfile" id="searchfile" type="checkbox" onClick="allowCheckbox(this);">
								<label for="searchfile">
										Поиск в файлах на ftp
									<span class="alert_box">
										Если выбран только данный флаг, без дочернего, будет получен список файлов где были совпадения искомого значений.<br>Поиск происходит с директории в которой расположен файл.<br>Поиск по содержимому файла производится ели размер не превышает 10 мегабайт.<br>Из поиска исключены изображения.<br>После поиска появляется возможность выделить файлы в которых необходимо заменить значение, для этого необходимо активировать "Замена вхождений в файлах".<br>
										<mark>Регистр учитывается.</mark>
									</span>
								</label>
								<ul>
									<li>
										<input name="replacetext" id="replacetext" type="checkbox" onClick="allowCheckbox(this);" disabled>
										<label class="replacetext_label off" for="replacetext">
											Замена вхождений в файлах
											<span class="alert_box">
												Убедитесь, что вы сделали бэкап всех файлов сайта! Это тестовая возможность, есть вероятность возникновения проблем.<br>Не используйте данную возможность в слишком больших по объему сайтах.
											</span>
										</label>
									</li>
								</ul>
							</li>
							<li>
								<input name="searchbd" id="searchbd" type="checkbox" onClick="allowCheckbox(this);" checked>
								<label for="searchbd">
									Поиск и замена в bd
									<span class="alert_box">
										Последовательность действий скрипта:
										<ol>
											<li>Сохраняем копию bd в папке "\backup_cms_bd\";</li>
											<li>Копируем сохранённый файл в директорию "\backup_cms_bd\replace_copy_sql\";</li>
											<li>Перезаписываем указанное значение на необходимое в скопированном файле;</li>
											<li>Импортируем полученный файл, в новую базу данных или первоначальную.</li>
										</ol>
										<mark>Регистр учитывается.</mark>
									</span>
								</label>
							</li>
						</ul>
				</fieldset>
				<fieldset>
					<legend>Backup bd</legend>
					<ul>
						<li>
							<label for="new_name_bd">Наименование bd для сохранения</label>
							<input name="new_name_bd" type="text" id="new_name_bd" placeholder="new name bd" disabled>
						</li>
						<li>
							<input name="backup_new_name_bd" id="backup_new_name_bd" type="checkbox" onClick="newBackup(this.checked);">
							<label class="backup_new_name_bd_label" for="backup_new_name_bd" title="Первоначально необходимо вручную создать новую базу данных!">Залить результат в новую bd</label>
						</li>
						<li>
							<input name="backup" id="backup" type="checkbox" disabled checked>
							<label class="off" for="backup">Сделать бэкап bd</label>
						</li>
						<li>
							<label for="way">Путь для сохранения backup</label>
							<input name="way" id="way" type="text" value="<?=$dirname.'\\backup_cms_bd\\';?>" required disabled>
						</li>
					</ul>
				</fieldset>
					<input name="submit" id="submit" type="submit" value="Replace">
				<? if($showrestore == 2) { ?>
					<input name="restore" id="restore" type="submit" value="Clean post">
					<textarea class="arr-addr" name="arr-addr" id="arr-addr"></textarea>
				<? } ?>
			</form>
			<div class="progress">
				<div class="progress_header">report</div>
				<div class="progress_body"><?=$report;?></div>
			</div>
		</div>
	</section>

	<script>
		var addrElem = document.querySelectorAll('.address');
		if(addrElem.length > 0) for(var i = 0; i < addrElem.length; i++) addrElem[i].scrollLeft = 1800;

		if(document.querySelector('.coincidences')) document.querySelector('.coincidences').innerHTML = document.querySelectorAll('section p.res').length;
		function showPass (data) {
			document.getElementById('passwd').type = data ? 'text' : 'password';
		}

		function allowCheckbox (data) {
			var elemone = document.getElementById('searchfile').checked,
					elemtwo = document.getElementById('searchbd').checked,
					val = !elemone && !elemtwo,
					dopbutton = document.getElementById('submit'),
					dopoption = document.getElementById('backup_new_name_bd'),
					dopinput = document.getElementById('new_name_bd'),
					dopreplacetext = document.getElementById('replacetext'),
					textSearch = document.getElementById('val'),
					replacement = document.getElementById('replace'),
					typeAction = document.getElementById('type_action'),
					checkBackup = document.getElementById('backup'),
					inphost = document.getElementById('hostbd'),
					inplogin = document.getElementById('login'),
					inppass = document.getElementById('passwd'),
					checkshowpas = document.getElementById('show_pass'),
					inpnamebd = document.getElementById('name_bd'),
					labelshowpass = document.querySelector('.show_pass_label'),
					labelbackupnewname = document.querySelector('.backup_new_name_bd_label'),
					labelreplacetext = document.querySelector('.replacetext_label'),
					checkresultsearch = document.querySelectorAll('.val_result_search');

			if(!data.checked && data.id.indexOf('searchfile') !== -1) {
				dopreplacetext.disabled = true;
				dopreplacetext.checked = false;
				labelreplacetext.classList.add('off');
			} else if(data.id.indexOf('searchfile') !== -1) {
				dopreplacetext.disabled = false;
				labelreplacetext.classList.remove('off');
			}

			if(elemone && dopreplacetext.checked){
				if(checkresultsearch.length > 0) for(var key in checkresultsearch) if(typeof checkresultsearch[key] == 'object') checkresultsearch[key].style.display = 'inline-block';
			} else {
				if(checkresultsearch.length > 0) {
					for(var key in checkresultsearch) if(typeof checkresultsearch[key] == 'object') checkresultsearch[key].style.display = 'none';
					document.querySelector('.arr-addr').value = '';
					var list = document.querySelectorAll('.progress_body input[type="checkbox"]');
					for (var i = 0, len = list.length; i < len; i++) {
						list[i].checked = false;
					}
				}
			}

			if(!data.checked && data.id.indexOf('searchbd') !== -1) {
				inphost.disabled = true;
				inplogin.disabled = true;
				inppass.disabled = true;
				checkshowpas.disabled = true;
				inpnamebd.disabled = true;
				inphost.required = false;
				inplogin.required = false;
				inppass.required = false;
				inpnamebd.required = false;
				labelshowpass.classList.add('off');
				checkBackup.checked = false;
				dopoption.disabled = true;
				labelbackupnewname.classList.add('off');
				if(!dopinput.disabled) {
					dopinput.disabled = true;
					dopinput.required = false;
					dopoption.checked = false;
					labelbackupnewname.classList.add('off');
				}
			} else if(data.id.indexOf('searchbd') !== -1) {
				dopoption.disabled = false;
				labelbackupnewname.classList.remove('off');
			}

			if(val) {
					textSearch.disabled = true;
					textSearch.required = false;
					replacement.disabled = true;
					replacement.required = false;
					dopbutton.value = 'Make backup';
					checkBackup.checked = true;
					inphost.disabled = false;
					inplogin.disabled = false;
					inppass.disabled = false;
					checkshowpas.disabled = false;
					inpnamebd.disabled = false;
					inphost.required = true;
					inplogin.required = true;
					//inppass.required = true;
					inpnamebd.required = true;
					labelshowpass.classList.remove('off');
					typeAction.value = '2';
					//class="off"
			}else {
				textSearch.disabled = false;
				textSearch.required = true;
				if(elemone && !elemtwo && !dopreplacetext.checked) {
					replacement.disabled = true;
					replacement.required = false;
					dopbutton.value = 'Search';
					checkBackup.checked = false;
					inphost.disabled = true;
					inplogin.disabled = true;
					inppass.disabled = true;
					checkshowpas.disabled = true;
					inpnamebd.disabled = true;
					inphost.required = false;
					inplogin.required = false;
					//inppass.required = false;
					inpnamebd.required = false;
					labelshowpass.classList.add('off');
					typeAction.value = '3';
				} else {
					replacement.disabled = false;
					replacement.required = true;
					dopbutton.value = 'Replace';
					if(elemtwo) {
						inphost.disabled = false;
						inplogin.disabled = false;
						inppass.disabled = false;
						checkshowpas.disabled = false;
						inpnamebd.disabled = false;
						inphost.required = true;
						inplogin.required = true;
						//inppass.required = true;
						inpnamebd.required = true;
						labelshowpass.classList.remove('off');
						checkBackup.checked = true;
					}
					typeAction.value = '1';
				}
			}
		}

		function newBackup (data) {
			var elem = document.getElementById('new_name_bd');
			if(data) {
				elem.disabled = false;
				elem.required = true;
			} else {
				elem.disabled = true;
				elem.required = false;
			}
		}

		var documentrestore = document.getElementById('restore');

		if(documentrestore) documentrestore.addEventListener('click', function(event) {
			event.preventDefault();
			document.location.pathname = document.location.pathname
		});

		function recordFullAddred() {
			var textarea = document.querySelector('.arr-addr'),
					fuladdr = this.parentNode.parentNode.childNodes[1].getAttribute('full-addr');
			if(this.checked) {
				textarea.value +=fuladdr+'||';
			} else {
				textarea.value = textarea.value.replace(fuladdr+'||', '');
			}
		}

		function addEventListenerAll(className, event, fn) {
			var list = document.querySelectorAll(className);
			for (var i = 0, len = list.length; i < len; i++) {
				list[i].addEventListener(event, fn, false);
			}
		}

		addEventListenerAll('.progress_body input[type="checkbox"]', 'click', recordFullAddred);

	</script>
</body>
</html>