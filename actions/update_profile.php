<?php
session_start();
require_once '../config/db_connect.php';

// Limite de Upload: 5MB
const MAX_FILE_SIZE = 5 * 1024 * 1024; 

if (!isset($_SESSION['user_id'])) { die("Acesso negado"); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $tipo_conta = $_SESSION['tipo_conta'] ?? '';
    
    // 1. Receber dados de texto
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // 2. Lógica da Senha
    $sql_senha = "";
    $params = ['nome' => $nome, 'telefone' => $telefone, 'email' => $email, 'id' => $user_id];

    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (!empty($nova_senha)) {
        if ($nova_senha === $confirma_senha) {
            $senhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql_senha = ", senha = :senha";
            $params['senha'] = $senhaHash;
        } else {
            echo "<script>alert('As senhas não conferem!'); window.history.back();</script>";
            exit;
        }
    }

    // 3. Lógica de Upload de Imagem
    $sql_foto = "";
    
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        
        $arquivo = $_FILES['foto'];
        
        // A. Verifica Erros
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $msg = "Erro no upload. Tente novamente.";
            if ($arquivo['error'] === UPLOAD_ERR_INI_SIZE) $msg = "Arquivo muito grande.";
            echo "<script>alert('$msg'); window.history.back();</script>";
            exit;
        }

        // B. Verifica Extensão
        $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo "<script>alert('Formato inválido (Use JPG, PNG, WEBP).'); window.history.back();</script>";
            exit;
        }

        // C. Verifica Tamanho
        if ($arquivo['size'] > MAX_FILE_SIZE) {
            echo "<script>alert('Arquivo maior que 5MB.'); window.history.back();</script>";
            exit;
        }

        // D. Salva
        $new_name = md5(time() . $user_id) . '.' . $ext;
        $dir = '../assets/uploads/';
        
        if (!is_dir($dir)) { mkdir($dir, 0777, true); }

        if (move_uploaded_file($arquivo['tmp_name'], $dir . $new_name)) {
            $sql_foto = ", foto = :foto";
            $path_db = "assets/uploads/" . $new_name;
            $params['foto'] = $path_db;
            
            $_SESSION['user_foto'] = $path_db; // Atualiza sessão
        } else {
            echo "<script>alert('Falha ao salvar a imagem.'); window.history.back();</script>";
            exit;
        }
    }

    // 4. Atualizar no Banco
    try {
        $sql = "UPDATE usuarios SET nome = :nome, telefone = :telefone, email = :email $sql_senha $sql_foto WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_email'] = $email;

        // 5. Redirecionamento Correto
        if ($tipo_conta === 'admin') {
            $back_url = '../admin.php?pagina=perfil';
        } elseif ($tipo_conta === 'coach') {
            $back_url = '../coach.php?pagina=perfil';
        } else {
            // Atleta
            $back_url = '../usuario.php?pagina=perfil';
        }
            
        echo "<script>alert('Perfil atualizado com sucesso!'); window.location.href='$back_url';</script>";

    } catch (PDOException $e) {
        echo "Erro ao atualizar banco: " . $e->getMessage();
    }
}
?>