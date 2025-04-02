<?php
// Importa os arquivos diretamente
require '../src/PHPMailer.php';
require '../src/SMTP.php';
require '../src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Configurações do servidor SMTP do UOL Host
    $mail->isSMTP();
    $mail->Host       = 'smtps.uol.com.br';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contato@arieldias.com.br'; // seu e-mail UOL
    $mail->Password   = 'mC$u-G6w';     // senha do e-mail
    $mail->SMTPSecure = 'tls'; // ou 'ssl'
    $mail->Port       = 587;   // ou 465 para SSL

    // Dados do formulário
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Configurações do e-mail
    $mail->setFrom('contato@arieldias.com.br', 'Formulário do site');
    $mail->addAddress('contato@arieldias.com.br');
    $mail->addReplyTo($email, $name);

    $mail->Subject = $subject;
    $mail->Body    = "Nome: $name\nE-mail: $email\n\nMensagem:\n$message";

    $mail->send();
    echo "OK";
} catch (Exception $e) {
    http_response_code(500);
    echo "Erro ao enviar: {$mail->ErrorInfo}";
}
