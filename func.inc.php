<?php
/**
 * Script ini berisi fungsi fungsi yang dibutuhkan
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function kirimPesan($pesan){
    // kirim  pesan ke Telegram Bot
    // ikuti petunjuk disini https://ibnumaksum@bitbucket.org/ibnumaksum/banten-kuliner-android.git
    // https://core.telegram.org/bots/api
    file_get_contents("https://api.telegram.org/BOTTOKEN/sendMessage?chat_id=IDnya&text=".urlencode($pesan));
}

function kirimEmail($judul,$pesan){
    if(file_exists($GLOBALS['app']."/email.inc.php")){
        include($GLOBALS['app']."/email.inc.php");
    }
}

function bersihin($txt){
    return preg_replace("/[^a-zA-Z0-9]+/", "", $txt);
}

// ini pake phpmailer, konfigurasi username dan password
function sendMail($to,$subject,$body){
    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'mail.ibnux.net';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'no-reply@ibnux.net';                 // SMTP username
        $mail->Password = 'n0-r3pl4y';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('no-reply@ibnux.net', 'Build Server');
        $mail->addAddress($to);

        //Attachments
        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        
        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = nl2br($body);
        $mail->AltBody    = $body;

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}

//Kata kata mutiara
function getKamut(){
    $kamuts = json_decode(file_get_contents("./kamut.json"),true);
    $rand = rand(0,count($kamuts)-1);
    return $kamuts[$rand];
}
