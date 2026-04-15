<?php
session_start();
require_once '../config/db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || empty($input['updates']) || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$id_avaliacao = intval($input['id']);
$updates = $input['updates'];

// Filtro de segurança: Apenas estas colunas podem ser editadas pelo JSON
$allowed_fields = [
    'peso_kg', 'altura_cm', 'pescoco', 'ombro', 'torax_relaxado', 'cintura', 'abdomen', 'quadril',
    'braco_dir_relaxado', 'braco_esq_relaxado', 'braco_dir_contraido', 'braco_esq_contraido',
    'coxa_dir', 'coxa_esq', 'panturrilha_dir', 'panturrilha_esq'
];

try {
    $pdo->beginTransaction();

    // 1. Prepara e executa o UPDATE das medidas manuais
    $set_clauses = [];
    $params = [];
    
    foreach ($allowed_fields as $field) {
        if (isset($updates[$field])) {
            $set_clauses[] = "$field = ?";
            // Converte virgula para ponto caso o user tenha digitado com vírgula
            $valor = $updates[$field] === '' ? null : floatval(str_replace(',', '.', $updates[$field]));
            $params[] = $valor;
        }
    }

    if (count($set_clauses) > 0) {
        $sql = "UPDATE avaliacoes SET " . implode(', ', $set_clauses) . " WHERE id = ?";
        $params[] = $id_avaliacao;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // 2. Refaz os cálculos de IMC, BF, Massa Magra e Massa Gorda
    $stmt_pull = $pdo->prepare("SELECT * FROM avaliacoes WHERE id = ?");
    $stmt_pull->execute([$id_avaliacao]);
    $av = $stmt_pull->fetch(PDO::FETCH_ASSOC);

    $imc = null; $bf = null; $massa_gorda = null; $massa_magra = null;

    if ($av) {
        $peso = $av['peso_kg'];
        $altura = $av['altura_cm'];
        $pescoco = $av['pescoco'];
        $cintura = $av['cintura'];
        $abdomen = $av['abdomen'];
        $quadril = $av['quadril'];
        $genero = $av['genero'];

        // IMC
        if ($peso && $altura) {
            $altura_m = $altura / 100;
            $imc = $peso / ($altura_m * $altura_m);
        }

        // BF (Formula de Navy)
        if ($peso && $altura && $pescoco && $cintura) {
            if ($genero === 'M') {
                $circ_abd = $abdomen ?: $cintura;
                if (($circ_abd - $pescoco) > 0) {
                    $bf = 495 / (1.0324 - 0.19077 * log10($circ_abd - $pescoco) + 0.15456 * log10($altura)) - 450;
                }
            } elseif ($genero === 'F' && $quadril) {
                if (($cintura + $quadril - $pescoco) > 0) {
                    $bf = 495 / (1.29579 - 0.35004 * log10($cintura + $quadril - $pescoco) + 0.22100 * log10($altura)) - 450;
                }
            }
        }

        // Massas
        if ($bf && $peso) {
            $massa_gorda = $peso * ($bf / 100);
            $massa_magra = $peso - $massa_gorda;
        }

        // Salva os novos cálculos no banco
        $stmt_calc = $pdo->prepare("UPDATE avaliacoes SET imc = ?, percentual_gordura = ?, massa_magra_kg = ?, massa_gorda_kg = ? WHERE id = ?");
        $stmt_calc->execute([$imc, $bf, $massa_magra, $massa_gorda, $id_avaliacao]);
    }

    $pdo->commit();

    // 3. Retorna Sucesso e os novos cálculos para o JavaScript atualizar na tela!
    echo json_encode([
        'success' => true,
        'calculos' => [
            'imc' => $imc ? round($imc, 2) : '-',
            'bf' => $bf ? round($bf, 2) : '-',
            'massa_magra' => $massa_magra ? round($massa_magra, 2) : '-',
            'massa_gorda' => $massa_gorda ? round($massa_gorda, 2) : '-'
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>