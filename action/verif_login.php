<?php
session_start();

include_once __DIR__.'/../config/conexao.php';
include_once __DIR__.'/../config/poo_login.php';


if (isset($_POST['loginn']) && isset($_POST['senha'])){
$login = $_POST['loginn'];
$senha = $_POST['senha'];
}

if ($login === '' && $senha === ''){

    echo '<script>alert("Por favor, preencha todos os campos de login e senha!")</script>';
}
 $conexao = new conexao();
 $conn = $conexao->conectar();

 $logando = new Login01($conn);
 if ($logando->logar($login, $senha)){
    // SE O LOGIN ESTIVER OK — ELE É SALVO NA SESSÃO
        $_SESSION['loginn'] = $login;  // Aqui salva o nome de quem logou
    header ('Location: ../views/dashboard.php');
 }else{
    echo '<script>alert("Login ou senha incorreto!")</script>';
    header ("Refresh:0.5, url= tela_login.php");
 }

?>