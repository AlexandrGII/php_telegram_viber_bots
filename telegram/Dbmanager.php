<?php
// TELEGRAM
class DBmanager {
	
	// Показать телефоны telegrame
	public static function Telegram_List() {
		$link = mysql_connect('localhost', 'XXXx', 'XXXx') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');

		$query = 'SELECT * FROM telegram_tel';
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		//print_r($result);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			print_r($line);
		}

		mysql_free_result($result);
		mysql_close($link);
	}
	
		// Поиск user_id
	public static function Telegram_SearchUserid($user_id) {
		$link = mysql_connect('localhost', 'XXXx', 'XXXx') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');
		
		$query = "SELECT * FROM telegram_tel WHERE userid=$user_id";
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		$rez = [];
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rez = $line;
		}
		
		mysql_free_result($result);
		mysql_close($link);
		return $rez; // находим последнюю запись, у которой userid = $user_id
	}
	
	
	// Добавить телефон telegrame
	public static function Telegram_Add($chat_id, $user_id, $phone) {
		$link = mysql_connect('localhost', 'XXXx', 'XXXx') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');
		
		$query = "SELECT * FROM telegram_tel WHERE userid=$user_id";
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		$rez = [];
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rez = $line;
		}
		
		// чистим номер телефона от лишних символов
		$phone = preg_replace("/[^0-9,.]/", "", $phone);
		
		if (count($rez) == 0) { // добавляем номер телефона + chatid только если это первый раз
			$query = "insert into telegram_tel values(null, $chat_id, $user_id, '$phone');";
			$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		}
		
		mysql_free_result($result);
		mysql_close($link);
	}
	//---
}



//DBmanager::TelegrameAdd('12345', '12345', "9531728956");

//DBmanager::TelegrameList();
//$line = DBmanager::Telegrame_SearchUserid('123455');
//echo count($line);
//print_r($line);



