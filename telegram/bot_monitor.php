<?php
// TELEGRAM
function LogWrite2File($stroka) {
	$file = "/tmp/bot_telegram.log";
	$d = date("Y-m-d H:i:s");
	file_put_contents($file, $d."\t".$stroka."\n", FILE_APPEND);
}

include('telegram-bot-sdk/vendor/autoload.php'); //Подключаем библиотеку
use Telegram\Bot\Api;
include('Dbmanager.php');
include('Orange.php');

$telegram = new Api('dfsdfasfsalkdnfasnldnfsaldf'); 
$result = $telegram -> getWebhookUpdates(); //Передаем в переменную $result полную информацию о сообщении пользователя


$chat_id = $result["message"]["chat"]["id"]; //Уникальный идентификатор пользователя
$name = $result["message"]["from"]["username"]; //Юзернейм пользователя
$keyboard = [["Запросить баланс"],["Напомнить пароль"]]; //Клавиатура
$keyboard_telephone = [[["text"=>"Указать телефон", "request_contact"=>true]]]; //Клавиатура для запроса телефона у пользователя   // REQUEST_CONTACT - означате запросить телефон средствами telegram

if (isset($result["message"]["text"])){
	$text = $result["message"]["text"]; //Текст сообщения
} else {
	$text = "";
}

$user_id = '0'; // id пользователя
$phone = '0'; // номер телефона
if (isset($result["message"]["contact"])) {
	if (isset($result["message"]["contact"]["user_id"])) {
		$user_id = $result["message"]["contact"]["user_id"];
	}
	if (isset($result["message"]["contact"]["phone_number"])) {
		$phone = $result["message"]["contact"]["phone_number"];
		DBmanager::Telegram_Add($chat_id, $user_id, $phone); // если пользователь прислал телефон, то добавляем его в бд привязывая к chat_id
	}
}

$find_user = DBmanager::Telegram_SearchUserid($chat_id); // ищим пользователя в бд. Если не нашли, то возвращает пустой массив

LogWrite2File("### REQUEST");
LogWrite2File("CHAT-ID: " . $chat_id);
if ($user_id != '0') LogWrite2File("USER-ID: " . $user_id);
if ($phone != '0') LogWrite2File("PHONE: " . $phone);




if($text){
	if ($text == "/start") {
		$reply = "Здравствуйте,чем я могу Вам помочь?";
		//$find_user = DBmanager::Telegram_SearchUserid($chat_id);
		if (count($find_user) == 0) {
			$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard_telephone, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
		} else {
			/*
			$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
			*/
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Ваш телефон находится в списке рассылки.' ]);
		}
	}elseif ($text == "Запросить баланс") {
		$reply = "Извините, не удалось определить Ваш баланс.";
		if (count($find_user) > 0) {
			$phone = $find_user['telephone'];
			$balance = OrangeApi::GetBalanceByPhone($phone);
			$reply = "Ваш баланс: ". $balance . ' руб.';
		}
		if ($reply == 'NULL') $reply = 'Не удалось определить договор по номеру телефона.';
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
	}elseif ($text == "Напомнить пароль") {
		$reply = "Извините, Ваш номер телефона не зарегистрирован в системе.";
		if (count($find_user) > 0) {
			$phone = $find_user['telephone'];
			$user_password = OrangeApi::GetPaswdByPhone($phone);
			$reply = "Ваш пароль: ". $user_password;
		}
		if ($reply == 'NULL') $reply = 'Не удалось определить пароль по номеру телефона.';
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
	}elseif ($text == "phone") {
		$reply = "Спасибо за телефон";
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
	}else{
		$reply = "По запросу \"<b>".$text."</b>\" ничего не найдено.";
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode'=> 'HTML', 'text' => $reply ]);
	}
}else{
	$reply = "Меню";
	LogWrite2File("TEXT: " . $text);
	$find_user = DBmanager::Telegram_SearchUserid($chat_id);
	if (count($find_user) == 0) {
		$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard_telephone, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
	} else {
		/*
		$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
		*/
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Ваш телефон находится в списке рассылки.' ]);
	}
}

/*
 * 
 * 
 * ### Генерируем самоподписанный сертификат
 * openssl req -newkey rsa:2048 -sha256 -nodes -keyout telptl.key -x509 -days 365 -out telptl.pem -subj "/C=RU/ST=Leningradskaya oblast/L=Stsburg/O=XXXXXX/CN=host.dom.io"
 * openssl req -newkey rsa:2048 -sha256 -nodes -keyout telptl.key -x509 -days 365 -out telptl.pem -subj "/C=RU/ST=Leningradskaya oblast/L=Stsburg/O=XXXXXX/CN=host.dom.io"
 * curl -F "url=https://host.dom.io:88/bot.php" -F "certificate=@/etc/ssl/certs/telegram/tel.pem" https://api.telegram.org/botASFASDFSADFSA==/setWebhook
 * 
 * ### Некоторые возможности бота
 * https://api.telegram.org/botASFASDFSADFSA==/getme									()
 * https://api.telegram.org/botASFASDFSADFSA==/getupdates							(получить сообщения)
 * https://api.telegram.org/botASFASDFSADFSA==/getWebhookInfo					(узнать как обстоят дела с веб-хуком)
 */

?>


