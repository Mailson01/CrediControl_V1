<?php
class Conexao {
    private $host = 'localhost';
    private $banco = 'controle_pag';
    private $usuario = 'root';
    private $password = '';

    public $conn;


    public function conectar(){
        $this->conn =null;
        try{
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->banco}", $this->usuario, $this->password);
          $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          return $this->conn;
        }catch(PDOException $e){
            echo "falha na conexão: " . $e->getMessage();
            return null;
        }
    }
}





?>