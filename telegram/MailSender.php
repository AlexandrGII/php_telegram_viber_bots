<?php

class Poster {

  public static function MainSendMsg($to, $from, $message) {
    $to = $to;
    $subject = "Telegram user question";

    $header = "From:$from \r\n";
    $header .= "Cc:user@dom.io \r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= 'Content-Type: text/html; charset="UTF-8";';

    $retval = mail ($to,$subject,$message,$header);

    if( $retval == true ) {
      echo "Message sent successfully...";
    }else {
      echo "Message could not be sent...";
    }
  }

}

