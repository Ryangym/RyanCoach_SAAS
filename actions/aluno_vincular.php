<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$aluno_id = $_SESSION['user_id'];
$acao = $_POST['acao'] ?? 'vincular'; // Padrão é vincular

try {
    // --- LÓGICA DE DESVINCULAR ---
    if ($acao === 'desvincular') {
        // Remove o coach_id (define como NULL)
        $stmt = $pdo->prepare("UPDATE usuarios SET coach_id = NULL WHERE id = ?");
        
        if ($stmt->execute([$aluno_id])) {
            echo "<script>alert('Você desvinculou seu personal com sucesso.'); window.location.href='../usuario.php';</script>";
        } else {
            echo "<script>alert('Erro ao desvincular.'); window.location.href='../usuario.php';</script>";
        }
        exit;
    }

    // --- LÓGICA DE VINCULAR (CÓDIGO) ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $codigo = filter_input(INPUT_POST, 'codigo_coach', FILTER_SANITIZE_STRING);

        if (empty($codigo)) {
            echo "<script>alert('Digite o código.'); window.location.href='../usuario.php';</script>";
            exit;
        }

        // Busca o dono do código
        $stmt = $pdo->prepare("SELECT id, nome, tipo_conta FROM usuarios WHERE codigo_convite = :cod");
        $stmt->execute(['cod' => $codigo]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coach) {
            echo "<script>alert('Código não encontrado!'); window.location.href='../usuario.php';</script>";
            exit;
        }

        if ($coach['tipo_conta'] !== 'coach' && $coach['tipo_conta'] !== 'personal') {
            echo "<script>alert('Este código não é de um Treinador.'); window.location.href='../usuario.php';</script>";
            exit;
        }

        // Atualiza
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET coach_id = ? WHERE id = ?");
        if ($stmtUpdate->execute([$coach['id'], $aluno_id])) {
            echo "<script>alert('Sucesso! Agora você treina com " . $coach['nome'] . ".'); window.location.href='../usuario.php';</script>";
        } else {
            echo "<script>alert('Erro ao vincular.'); window.location.href='../usuario.php';</script>";
        }
    }

} catch (PDOException $e) {
    echo "<script>alert('Erro no sistema.'); window.location.href='../usuario.php';</script>";
}
?>