<?php
// VIBER
class DBmanager {
	// Показать телефоны viber
	public static function Viber_List() {
		$link = mysql_connect('localhost', 'XXXX', 'XXXX') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');

		$query = 'SELECT * FROM viber_tel';
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			print_r($line);
		}

		mysql_free_result($result);
		mysql_close($link);
	}
	
		// Поиск user_id
	public static function Viber_SearchUserid($user_id) {
		$link = mysql_connect('localhost', 'XXXX', 'XXXX') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');
		
		$query = "SELECT * FROM viber_tel WHERE userid='$user_id'";
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		$rez = [];
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rez = $line;
		}
		
		mysql_free_result($result);
		mysql_close($link);
		return $rez; // находим последнюю запись, у которой userid = $user_id
	}
	
	
	// Добавить телефон viber
	public static function Viber_Add($user_id, $phone) {
		$link = mysql_connect('localhost', 'XXXX', 'XXXX') or die('Dont connect to database: ' . mysql_error());
		mysql_select_db('msgapi') or die('Dont check database');
		
		$query = "SELECT * FROM viber_tel WHERE userid='$user_id'";
		$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		$rez = [];
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rez = $line;
		}
		mysql_free_result($result);
		
		if (count($rez) == 0) { // добавляем номер телефона + chatid только если это первый раз
			$query = "insert into viber_tel values(null, '$user_id', '$phone');";
			$result = mysql_query($query) or die('Query get error: ' . mysql_error());
		}
		
		mysql_close($link);
	}
	//---
}




//DBmanager::Viber_List();

//$line = DBmanager::Viber_SearchUserid('dfasdfsdfsfadf');
//echo count($line);

//DBmanager::Viber_Add("dfsdfsdfsdf+dfsdf==", "79531328955");
