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
include('MailSender.php');

$telegram = new Api('ldnfsnfsdnlasdfnalsdbf'); //Устанавливаем токен, полученный у BotFather
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
	if (isset($result["message"]["contact"]["phone_number"])) { // Парсим сообщение от пользователя и вытаскиваем телефон
		$phone = $result["message"]["contact"]["phone_number"];
		DBmanager::Telegram_Add($chat_id, $user_id, $phone); // если пользователь прислал телефон, то добавляем его в бд привязывая к chat_id
	}
}

$find_user = DBmanager::Telegram_SearchUserid($chat_id); // ищим пользователя в бд. Если не нашли, то возвращает пустой массив
if (count($find_user) > 0) {
  $phone = $find_user['telephone'];
}

LogWrite2File("### REQUEST");
//file_put_contents('/tmp/bot_telegram.log', var_export($find_user, true));
//file_put_contents('/tmp/bot.log', var_export($result, true));
//file_put_contents('/tmp/bot.log', var_export($result["message"], true));
//file_put_contents('/tmp/bot.log', var_export($result["message"]["contact"], true));
LogWrite2File("CHAT-ID: " . $chat_id);
if ($user_id != '0') {
  LogWrite2File("USER-ID: " . $user_id);
} 
if ($phone != '0') { 
  LogWrite2File("PHONE: " . $phone);
} 




if($text){
	if ($text == "/start") { // Пользователь иницировал чат с ботом
		LogWrite2File("### START CHAT-ID: " . $chat_id . " /start");
		//$find_user = DBmanager::Telegram_SearchUserid($chat_id);
		if (count($find_user) == 0) {
      $reply = "Здравствуйте! Для идентификации клиента нашему сервису необходим Ваш номер телефона! Для этого нажмите кнопку 'Указать телефон'.";
			$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard_telephone, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
			LogWrite2File("CHAT-ID: " . $chat_id . " Ask phone");
		} else {
      $reply = "Укажите Ваш вопрос?";
			$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
			LogWrite2File("CHAT-ID: " . $chat_id . " Send menu");
		}
	}elseif ($text == "Запросить баланс") { // Польозватель нажал кнопку "Запросить баланс"
		LogWrite2File("CHAT-ID: " . $chat_id . " user=>ask_balance");
		$reply = "Извините, не удалось определить Ваш баланс.";
		if (count($find_user) > 0) {
			$phone = $find_user['telephone'];
			$balance = OrangeApi::GetBalanceByPhone($phone);
			$reply = "Ваш баланс: ". $balance . ' руб.';
			LogWrite2File("CHAT-ID: " . $chat_id . " bot=>balance: " . $balance);
		}
		if ($balance == 'NULL') $reply = 'Не удалось определить договор по номеру телефона.';
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
			LogWrite2File("CHAT-ID: " . $chat_id . " bot=>result_of_balance");
	}elseif ($text == "Напомнить пароль") { // Пользователь нажал кнопку "Напомнить пароль"
		LogWrite2File("CHAT-ID: " . $chat_id . " user=>ask_password");
		$reply = "Извините, Ваш номер телефона не зарегистрирован в системе.";
		if (count($find_user) > 0) {
			$phone = $find_user['telephone'];
			$user_password = OrangeApi::GetPaswdByPhone($phone);
			$reply = "Ваш пароль: ". $user_password;
			LogWrite2File("CHAT-ID: " . $chat_id . " bot=>user_password: " . $user_password);
		}
		if ($user_password == 'NULL') $reply = 'Не удалось определить пароль по номеру телефона.';
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
		LogWrite2File("CHAT-ID: " . $chat_id . " bot=>result_of_password");
		/*
		$url = "https://dom.io/img/logo.png";
		$telegram->sendPhoto([ 'chat_id' => $chat_id, 'photo' => $url, 'caption' => "Ваш баланс: 5000 рублей." ]);
		}elseif ($text == "Обещанный платеж") {
		$url = "https://server.dom.io/msgapi/telegram/Wow2.jpg";
		$telegram->sendDocument([ 'chat_id' => $chat_id, 'document' => $url, 'caption' => "В данном случае, передается документ." ]);
		*/
	}elseif ($text == "phone") { // Пользователь прислал телефон
		LogWrite2File("CHAT-ID: " . $chat_id . " user=>phone");
		$reply = "Спасибо за телефон";
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply ]);
		LogWrite2File("CHAT-ID: " . $chat_id . " bot=>senks_by_phone");
	}else{ // Пользователь прислал произвольное сообщение, это надо отправить на почту home@dom.io
		LogWrite2File("CHAT-ID: " . $chat_id . " PHONE: " . $phone . " user=>request: " . $text);
    $fio_email = OrangeApi::GetFIOEmailByPhone($phone);
    $reply = "Спасибо за вопрос!";
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode'=> 'HTML', 'text' => $reply ]);
		LogWrite2File("CHAT-ID: " . $chat_id . " PHONE: " . $phone . " Email: " . $fio_email);
    if ($fio_email != 'NULL') {
      $email_body = '<html> <body><h3>Question: </h3>' . $text . " <br><h3>User info: </h3>" . $fio_email . "</body> </html>";
      Poster::MainSendMsg('v.eliseev@dom.io, home@dom.io', 'telegrambot@dom.io', $email_body);
      LogWrite2File("CHAT-ID: " . $chat_id . " PHONE: " . $phone . " Send email");
    } else {
      LogWrite2File("CHAT-ID: " . $chat_id . " PHONE: " . $phone . " Send email Promblems with user fio_info");
    }
	}
}else{
	$reply = "Меню";
  if (count($find_user) > 0) { // Если пользователь найден в бд 
    $phone = $find_user['telephone'];
    LogWrite2File("CHAT-ID: " . $chat_id . " PHONE: " . $phone . " bot=>send_menu  Request: " . $text );
		$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
		LogWrite2File("CHAT-ID: " . $chat_id . " bot=>send_menu");
  } else { // Если пользователь в бд не найден
    LogWrite2File("CHAT-ID: " . $chat_id . " bot=>send_menu  Request: " . $text );
		$reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard_telephone, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
		$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup ]);
		LogWrite2File("CHAT-ID: " . $chat_id . " bot=>ask_phone");
  }
}

/*
 * 
 * 
 * ### Генерируем самоподписанный сертификат
 * openssl req -newkey rsa:2048 -sha256 -nodes -keyout tel132.key -x509 -days 365 -out tel132.pem -subj "/C=RU/ST=Leningradskaya oblast/L=St.Petersburg/O=Wow Wow2/CN=server.dom.io"
 * curl -F "url=https://server.dom.io:88/bot.php" -F "certificate=@/etc/ssl/certs/telegram/tel132.pem" https://api.telegram.org/botldnfsnfsdnlasdfnalsdbf/setWebhook
 * 
 * ### Некоторые возможности бота
 * https://api.telegram.org/botldnfsnfsdnlasdfnalsdbf/getme									()
 * https://api.telegram.org/botldnfsnfsdnlasdfnalsdbf/getupdates							(получить сообщения)
 * https://api.telegram.org/botldnfsnfsdnlasdfnalsdbf/getWebhookInfo					(узнать как обстоят дела с веб-хуком)
 */

?>


