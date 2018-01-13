<?php
Class OrangeApi {
	// Метод получает баланс по номеру телефона
	public static function GetBalanceByPhone($phone) {
		$url = "https://server.dom.io/orange/orange_get_balance_by_phone.php?phone=$phone";
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'Wow-curl'
		));
		
		$resp = curl_exec($curl);
		curl_close($curl);
		//echo $resp;
		$resp_list = explode(';', $resp);
		$balance = $resp_list[2];
		
		return $balance;
	}
	
	// Метод получает пароль по номеру телефона
	public static function GetPaswdByPhone($phone) {
		$url = "https://server.dom.io/orange/orange_get_paswd_by_phone.php?phone=$phone";
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'Wow-curl'
		));
		
		$resp = curl_exec($curl);
		curl_close($curl);
		
		return $resp;
	}
  
	// Метод получает ФИО и EMAIL по номеру тлефона
	public static function GetFIOEmailByPhone($phone) {
		$url = "https://server.dom.io/orange/orange_get_fio_email_by_phone.php?phone=$phone";
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'Wow-curl'
		));
		
		$resp = curl_exec($curl);
		curl_close($curl);
		
		return $resp;
	}
}

//echo OrangeApi::GetBalanceByPhone('9531727844');			// получаем баланс по номеру тулефона
//echo OrangeApi::GetPaswdByPhone('9531727844');				// получаем пароль по номеру телефона
