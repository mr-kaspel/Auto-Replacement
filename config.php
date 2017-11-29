<?php
mb_internal_encoding("UTF-8");

$host = $_REQUEST["url"]; //'localhost';  адрес сервера 
$database = $_REQUEST["namebd"]; //'compstore';  имя базы данных
$user = $_REQUEST["login"]; //'root';  имя пользователя
$password = $_REQUEST["passwd"]; //'1234567';  пароль
$isval = $_REQUEST["val"];
$isreplace = $_REQUEST["replace"];

$link = mysqli_connect($host, $user, $password, $database)
	or die("Ошибка соединения: " . mysqli_error($link));


$query = "DECLARE done INT DEFAULT 0;
DECLARE cur1 CURSOR FOR
SELECT column_name FROM information_schema.columns
WHERE table_name = 'my_table';
DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN cur1;
REPEAT
SET s = concat(
'UPDATE my_table SET ',
column_name,
' = REPLACE(', column_name, ', {$isval}, {$isreplace});');
PREPARE stmt2 FROM s;
EXECUTE stmt2;
FETCH cur1 INTO a;
UNTIL done END REPEAT;
CLOSE cur1";

$result = mysqli_query($link, $query) or die("Ошибка, значене не найдено. <br>" . mysqli_error($link));
if( $result ) {
	echo "Замена занчения прошла успешно $company";
}

// закрываем подключение
mysqli_close($link);
?>