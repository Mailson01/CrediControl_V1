<?php
include_once "./conexao.php";

class Login01{

      public $conn;

    // Construtor para injetar a conexão
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
  public function cdUser($loginn, $senha) {
        try {
            $sql = "INSERT INTO usuarios (loginn, senha) VALUES (:loginn, :senha)";
            $stmt = $this->conn->prepare($sql);

            // Usando bindValue para associar os valores
            $stmt->bindValue(':loginn', $loginn, PDO::PARAM_STR);
            $stmt->bindValue(':senha', $senha, PDO::PARAM_STR);

            // Executando a consulta
            return $stmt->execute();
        } catch (PDOException $e) {
            echo "Erro ao inserir galinha: ".$e->getMessage();
            return false;
        }
    }

    
     public function existeUser($loginn, $senha): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE loginn = :loginn AND senha = :senha";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':loginn', $loginn, PDO::PARAM_STR);
        $stmt->bindValue(':senha', $senha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
     }
     public function msg(){
        echo "Cadastro realizado com sucesso!";
     }

     public function logar($loginn, $senha): bool {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE loginn = :loginn AND senha = :senha";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':loginn', $loginn, PDO::PARAM_STR);
        $stmt->bindValue(':senha', $senha, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
     }
     public function msgE(){
        echo "Usuario ou senha incorreto!";
     }

}
?>