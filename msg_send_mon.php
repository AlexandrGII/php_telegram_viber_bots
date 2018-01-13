<?php

// Простенькое логирование в файл
function logWrite2File($stroka) {
  $file = "/tmp/messeges_monitoring_api.log";
  $d = date("Y-m-d H:i:s");
  file_put_contents($file, $d."\t".$stroka."\n", FILE_APPEND);
}


// Логирование в базе данных mysql
function LogWrite2Db ($messenger, $phone, $msg, $result) {
  /*
   * Столбцы:
   * id
   * messenger(telegram or viber)
   * phone
   * msg
   * datatime
   * result
   */
  $link = mysql_connect('localhost', 'XXXXX', 'XXXXXXXXX') or die('Dont connect to database: ' . mysql_error());
  mysql_select_db('msgapi') or die('Dont check database');
  
  $query = "insert into logs values(null, '$messenger', '$phone', '$msg', NOW(), '$result');";
  $result = mysql_query($query) or die('Query get error: ' . mysql_error());
  
  mysql_close($link);
}


// Послать сообщение клиенту в telegram
//curl -s -X POST https://api.telegram.org/bot12345667:fnskdfjsdfknsdkjnfksndjfnkn/sendMessage -d text="Telegram bot message" -d chat_id=1914234 | json_pp
function SendByTelegram($user_id, $msg) {
  logWrite2File("Func SendByTelegram. MSG: " . $msg);
  $url = "https://api.telegram.org/bot123456778:dflsmflmslkmdflskdmf/sendMessage";
  $data_string = "text=$msg&chat_id=$user_id";
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_USERAGENT, 'Dom-curl');
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Length: ' . strlen($data_string),) // viber token
  );
  $resp = curl_exec($curl);
  curl_close($curl);
  
  return $resp;
}


// Послать сообщение клиенту в viber
function SendByViber($user_id, $msg) {
  logWrite2File("Func SendByViber. MSG: " . $msg);
  $url = "https://chatapi.viber.com/pa/send_message";
  $data = array(
  "receiver" => $user_id, 
  "type" => "text",
  "text" => $msg,
  );
  $data_string = json_encode($data);
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_USERAGENT, 'Dom-curl');
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string),
      'X-Viber-Auth-Token:  XXXXXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',) // viber token
  );
  $resp = curl_exec($curl);
  curl_close($curl);
  
  return $resp;
}


// Поиск тeлефона в Viber списке
function Viber_SearchPhone($phone) {
  logWrite2File("Func Viber_SearchPhone. Phone: " . $phone);
  $link = mysql_connect('localhost', 'XXXXX', 'XXXXXXXXX') or die('Dont connect to database: ' . mysql_error());
  mysql_select_db('msgapi') or die('Dont check database');
  
  $query = "SELECT * FROM viber_tel WHERE telephone='$phone'";
  $result = mysql_query($query) or die('Query get error: ' . mysql_error());
  $rez = [];
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $rez = $line;
  }
  
  mysql_free_result($result);
  mysql_close($link);
  return $rez;
}


// Поиск тeлефона в Telegram списке
function Telegram_SearchPhone($phone) {
  logWrite2File("Func Telegram_SearchPhone. Phone: " . $phone);
  $link = mysql_connect('localhost', 'XXXXX', 'XXXXXXXXX') or die('Dont connect to database: ' . mysql_error());
  mysql_select_db('msgapi') or die('Dont check database');
  
  $query = "SELECT * FROM telegram_tel WHERE telephone='$phone'";
  $result = mysql_query($query) or die('Query get error: ' . mysql_error());
  $rez = [];
  while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $rez = $line;
  }
  
  mysql_free_result($result);
  mysql_close($link);
  return $rez;
}



// C помощью рефлексии вытаскиваем protected свойства класса
function АccessProtected($obj, $prop) {
  $reflection = new ReflectionClass($obj);
  $property = $reflection->getProperty($prop);
  $property->setAccessible(true);
  return $property->getValue($obj);
}


if (($_SERVER['REMOTE_ADDR']=='172.1.2.3') || ($_SERVER['REMOTE_ADDR']=='172.1.2.4') ) {
	// Обработка переданных скрипту параметров
	if (isset($_POST['phone'])) {
		$phone = $_POST['phone'];
		if (!filter_var($phone, FILTER_VALIDATE_INT)) {
			echo json_encode(['result'=>'error', 'description'=>'Bad telephone number']);
			exit(1);
		}
		if (!isset($_POST['msg'])) {
			echo json_encode(['result'=>'error', 'description'=>'Please, provide message text']);
			exit(1);
		}
		$msg = $_POST['msg'];
		
		logWrite2File("msg_send.php" . "\tPHONE: " . $phone . "\tMSG: " . $msg);
		//file_put_contents('/tmp/bot_viber.log', var_export($_POST, true));
		
		// поиск номера в списке viber 
		$search_viber = Viber_SearchPhone($phone);
		if (count($search_viber) > 0) {
			$user_id = $search_viber['userid'];
			$resp_viber = SendByViber($user_id, $msg);
		} 
		
		// поиск номера в списке telegram
		$search_telegram = Telegram_SearchPhone($phone);
		if (count($search_telegram) > 0) {
			$user_id = $search_telegram['chatid'];
			$resp_telegram = SendByTelegram($user_id, $msg);
		}
		
		// разбираем ответ
		$resp_viber_obj = json_decode($resp_viber);
		$resp_telegram_obj = json_decode($resp_telegram);

		$status_viber = 'no';
		/*
		if ($resp_viber_obj->status_message == 'ok') {
			$status_viber = 'ok';
			LogWrite2Db('viber', $phone, $msg, '1');
		} else {
			LogWrite2Db('viber', $phone, $msg, '0');
		}
		*/
		
		$status_telegram = 'no';
		if ($resp_telegram_obj->ok == '1') {
			$status_telegram = 'ok';
			LogWrite2Db('telegram', $phone, $msg, '1');
		} else {
			LogWrite2Db('telegram', $phone, $msg, '0');
		}
		
		echo json_encode(['result'=>'success', 'viber_status'=>$status_viber, 'telegram_status'=>$status_telegram]); // положительный результат
	} else {
		echo json_encode(['result'=>'error', 'description'=>'Please, provide phone number']);
	}
} else {
	//print_r($_SERVER['REMOTE_ADDR']);
  logWrite2File("msg_send.php Error" ."\tREMOTE-ID: " . $_SERVER['REMOTE_ADDR']);
}


/*
 * 
 * 
 * USE:
 * curl -X POST "https://host.Dom.ru/msgapi/msg_send_monitoring.php" -d "phone=79234123123" -d "msg=This is test message"  
 * curl -X POST "https://host.Dom.ru/msgapi/msg_send_monitoring.php" -d "phone=79531728955" -d "msg=Dear customer, your balance is zero"
 * 
 * 
 * RESPONSE-OK:
 * {"result":"success","viber_status":"ok","telegram_status":"ok"}
 * 
 * RESPONSE-ERR(не указано смообщение):
 * curl -X POST "https://host.Dom.ru/msgapi/msg_send.php" -d "phone=79234123123" 
 * {"result":"error","description":"Please, provide message text"}
 * 
 * RESPONSE-ERR(не указан телефон):
 * curl -X POST "https://host.Dom.ru/msgapi/msg_send.php"
 * {"result":"error","description":"Please, provide phone number"}
 * 
 * RESPONSE-ERR(некорректный телефонный номер):
 * curl -X POST "https://host.Dom.ru/msgapi/msg_send.php" -d "phone=79234123123s" -d "msg=This is good7"
 * {"result":"error","description":"Bad telephone number"}
 * 
 * 
 * хосты с которых можно использовать апи
 * mama 172.1.2.3
 * zabbix.Dom.ru 172.1.2.4
 * 
 * 
 */

