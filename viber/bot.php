<?php
// VIBER
function LogWrite2File($stroka) {
  $file = "/tmp/bot_viber.log";
  $d = date("Y-m-d H:i:s");
  file_put_contents($file, $d."\t".$stroka."\n", FILE_APPEND);
}

// с помощью рефлексии вытаскиваем protected свойства класса
function АccessProtected($obj, $prop) {
  $reflection = new ReflectionClass($obj);
  $property = $reflection->getProperty($prop);
  $property->setAccessible(true);
  return $property->getValue($obj);
}

LogWrite2File("### RECEIVE TO CALLBACK");

require_once("viber-bot-php/vendor/autoload.php");

use Viber\Bot;
use Viber\Api\Sender;

include('Dbmanager.php');
include('Orange.php');

$apiKey = 'fsdlfnskjfnlasndkjfnaskjdfn';

$botSender = new Sender([
    'name' => 'UI some',
    'avatar' => 'https://developers.viber.com/img/favicon.ico',
]);


try {
    $bot = new Bot(['token' => $apiKey]);
    $bot
    ->onConversation(function ($event) use ($bot, $botSender) { // начало беседы(conversation)
        LogWrite2File("Conversation begin");
        $r_find = [];
        if (isset($event)) {
          $prop_user = АccessProtected($event, 'user'); // с помощью рефлексии вытаскиваем свойства класса
          $prop_id = АccessProtected($prop_user, 'id'); // с помощью рефлексии вытаскиваем свойства класса
          LogWrite2File("Conversation UserID:" . (string)$prop_id);
          $r_find = DBmanager::Viber_SearchUserid($prop_id);
        }
        if (count($r_find) != 0) {
          LogWrite2File("Bot -> ask - What can ia help");
          return (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setText("Здравствуйте, чем я могу Вам помочь?")
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')//,
                    /*
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
            ])
          );
        } else {
          LogWrite2File("Bot -> ask telephone");
          return (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setText("Введите Ваш номер телефона в формате(7XXXXXXXXXX, например: 79531728958");
        }
    })
    ->onSubscribe(function ($event) use ($bot, $botSender) { // 
        LogWrite2File("Subscribe");
        $this->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setText('Спасибо за диалог!')
        );
    })
    ->onText('|^menu.*|', function ($event) use ($bot, $botSender) { // если пользователь послал комманду menu
        $receiverId = $event->getSender()->getId();
        LogWrite2File("Receive button from: " . $receiverId);
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($receiverId)
            ->setText('Сделайте выбор')
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')/*,
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
                ])
            )
        );
    })
    ->onText('|hello.*|', function ($event) use ($bot, $botSender) { // если пользователь послал комманду hello
        $receiverId = $event->getSender()->getId();
        $receiverName = $event->getSender()->getName();
        LogWrite2File("Receive hello from: " . $receiverId . "Name: " . $receiverName);
        //LogWrite2File(print_r($event->getSender()->getId()));
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($event->getSender()->getId())
            ->setText("Чем я могу Вам помочь?") //How can I help you?
        );
    })
    ->onText('|btn_score.*|', function ($event) use ($bot, $botSender) {  // если пользователь нажал кнопку Запросить баланс, то ему надо показать баланс
        $receiverId = $event->getSender()->getId();
        LogWrite2File("Receive ret from: " . $receiverId);
        
        $r_find = DBmanager::Viber_SearchUserid($receiverId);
        $balance = '0';
        if (count($r_find) > 0) {
          $phone = $r_find['telephone'];
          LogWrite2File("Phone: " . $phone);
          $balance = OrangeApi::GetBalanceByPhone($phone);
          LogWrite2File('Balance: ' . $balance . ' руб.');
        }
        if ($balance == 'NULL') {
          $balance = 'Не удалось определить договор по номеру телефона.';
        }
        
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($event->getSender()->getId())
            ->setText("Ваш баланс: " . $balance)
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')/*,
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
                ])
            )
        );
    })
    ->onText('|btn_contract_num.*|', function ($event) use ($bot, $botSender) { // если пользователь нажал кнопку Напомнить пароль
        $receiverId = '';
        $msg_text = '';
        $receiverId = $event->getSender()->getId();
        $msg_text = $event->getMessage()->getText();
        LogWrite2File("Receive ret from: " . $receiverId . " TEXT: " . $msg_text);
        
        $r_find = DBmanager::Viber_SearchUserid($receiverId);
        $user_password = 'NULL';
        if (count($r_find) > 0) {
          $phone = $r_find['telephone'];
          LogWrite2File("Phone: " . $phone);
          $user_password = OrangeApi::GetPaswdByPhone($phone);
          LogWrite2File('Paswd: ' . $user_password);
        }
        if ($user_password == 'NULL') {
          $user_password = 'Не удалось определить клиента по номеру телефона';
        }
        
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($event->getSender()->getId())
            ->setText("Ваш пароль: " . $user_password)
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')/*,
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
                ])
            )
        ); //-------------------------
    })
    ->onText('|7[0-9]{10}|', function ($event) use ($bot, $botSender) { // если пользователь прислал номер телефона
        $receiverId = '';
        $msg_text = '';
        $receiverId = $event->getSender()->getId();
        $msg_text = $event->getMessage()->getText();
        LogWrite2File("Add telephone: " . $receiverId . " TEXT: " . $msg_text);
        DBmanager::Viber_Add($receiverId, $msg_text); // msg_text - хранит телефон, который пользователь прислал
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($event->getSender()->getId())
            ->setText("Телефон добавлен")
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')/*,
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
                ])
            )
        ); //-------------------------
    })
    ->onText('|.*|', function ($event) use ($bot, $botSender) { // если пользователь ввел не стандартный текст
        $receiverId = '';
        $msg_text = '';
        $receiverId = $event->getSender()->getId();
        $msg_text = $event->getMessage()->getText();
        LogWrite2File("USER -> " . $receiverId . " TEXT: " . $msg_text);

        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
            ->setSender($botSender)
            ->setReceiver($event->getSender()->getId())
            ->setText("Телефон добавлен")
            ->setKeyboard(
                (new \Viber\Api\Keyboard())
                ->setButtons([
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_score')
                    ->setText('Запросить баланс')/*,
                    (new \Viber\Api\Keyboard\Button())
                    ->setActionType('reply')
                    ->setActionBody('btn_contract_num')
                    ->setText('Напомнить пароль')
                    */
                ])
            )
        ); //-------------------------
    })
    ->run();
} catch (Exception $e) {
  LogWrite2File("Error:" . $e);
    // todo - log exceptions
}
