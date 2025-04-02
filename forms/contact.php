<?php

// Captura os dados do formulário
$name = $_POST['name'];
$email = $_POST['email'];
$subject = $_POST['subject'];
$message = $_POST['message'];

// Define o destinatário (troque para o seu e-mail real)
$to = "arielsdias@gmail.com";

// Cria o corpo da mensagem
$body = "Você recebeu uma nova mensagem de contato:\n\n";
$body .= "Nome: $name\n";
$body .= "E-mail: $email\n";
$body .= "Assunto: $subject\n";
$body .= "Mensagem:\n$message\n";

// Cabeçalhos
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";

// Envia o e-mail
if (mail($to, $subject, $body, $headers)) {
  echo "OK";
} else {
  http_response_code(500);
  echo "Erro ao enviar o e-mail.";
}
?>