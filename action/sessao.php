<?php
// no início do login.php
session_start();


if ($login->logar($loginn, $senha)) {
    $_SESSION['mensagem'] = "Usuário logado com sucesso!";
    header('Location: ../tela_Login.php'); // volta pra tela HTML
    exit;
} else {
    $_SESSION['mensagem'] = "Login ou senha inválidos.";
    header('Location: ../tela_Login.php');
    exit;
}
