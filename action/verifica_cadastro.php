<?php
session_start();
include __DIR__.'/../config/conexao.php';
include __DIR__.'/../config/poo_login.php';

if (isset($_POST['loginn']) && isset($_POST['senha'])){
    $login = $_POST['loginn'];
    $senha = $_POST['senha'];

if ($login === '' && $senha === ''){
    echo '<script>alert("Por favor preencha todos os campos de lgin e senha!")</script>';
}
}
$conexao = new conexao();
$conn = $conexao->conectar();

if ($conexao){
    $login = new Login01($conn);

    if ($login->existeUser($_POST['loginn'], $senha)){
        echo '<script>alert("ERRO! JA EXISTE ESSE NOME DE LOGIN EM NOSSO BANCO!")</script>';
        header ("Refresh:0.5, url=cadastrar_usuario.php");

    }else{
     $login->cdUser($_POST['loginn'],$senha);
        echo '<script>alert("Usuario Cadastrado com sucesso!")</script>';
        header ("Refresh:0.5, url=tela_login.php");
      }
    }
