<?php

namespace Comments;

//client side HTML: 6LcUrcwnAAAAACkdMvv__mHmPEFTEqxCDbMqmAlG
require_once "./vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function comment_send(string $name, string $comment): bool
{
    $send_success = true;

    // uwiohcdnaoklhkrx

    $mail = new PHPMailer(true);

    try {
        $mail->setLanguage('en');
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adam.shufelt.official@gmail.com';
        $mail->Password = 'uwiohcdnaoklhkrx';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->CharSet = 'iso-8859-1';
        $mail->Encoding = '8bit';

        // Recipients
        $mail->setFrom('comments@ashufelt.com', $name);
        $mail->addAddress('adam.shufelt.official@gmail.com', 'Adam Shufelt');

        // Content
        $mail->Subject = "[Comment] from " . $name;
        $mail->Body    = $comment;
        $mail->send();
    } catch (Exception $e) {
        print_r($e->getMessage());
        $send_success = false;
    }


    return $send_success;
}
