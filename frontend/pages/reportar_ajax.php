<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['utl_id'])) { http_response_code(401); echo json_encode(['error' => 'not_logged_in']); exit; }

$bd  = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
$uid = (int)$_SESSION['utl_id'];

// POST — submit a report or update report state (admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update report state (admin only)
    if ($action === 'update_estado') {
        $adminCheck = $bd->prepare("SELECT utl_admin FROM utilizador WHERE utl_id = ?");
        $adminCheck->execute([$uid]);
        if (!$adminCheck->fetchColumn()) { http_response_code(403); exit; }

        $denId  = (int)($_POST['den_id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $estados = ['Pendente', 'Resolvido', 'Ignorado'];
        if (!$denId || !in_array($estado, $estados, true)) {
            http_response_code(400); echo json_encode(['error' => 'invalid']); exit;
        }
        $bd->prepare("UPDATE denuncia SET den_estado = ? WHERE den_id = ?")->execute([$estado, $denId]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Submit a new report
    $ferId     = (int)($_POST['fer_id']    ?? 0);
    $motivo    = trim($_POST['motivo']     ?? '');
    $descricao = trim($_POST['descricao']  ?? '');

    $motivos = ['Conteúdo inapropriado', 'Ferramenta inexistente', 'Preço enganoso', 'Utilizador suspeito', 'Outro'];
    if (!$ferId || !in_array($motivo, $motivos, true)) {
        http_response_code(400); echo json_encode(['error' => 'invalid']); exit;
    }

    // Check tool exists and is active
    $check = $bd->prepare("SELECT fer_id FROM ferramenta WHERE fer_id = ? AND fer_ativa = 1");
    $check->execute([$ferId]);
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'not_found']); exit; }

    // Check if already reported by this user
    $dup = $bd->prepare("SELECT den_id FROM denuncia WHERE den_fer_id = ? AND den_utl_id = ?");
    $dup->execute([$ferId, $uid]);
    if ($dup->fetch()) { echo json_encode(['error' => 'already_reported']); exit; }

    $bd->prepare(
        "INSERT INTO denuncia (den_fer_id, den_utl_id, den_motivo, den_descricao) VALUES (?, ?, ?, ?)"
    )->execute([$ferId, $uid, $motivo, $descricao ?: null]);

    echo json_encode(['success' => true]);
    exit;
}

// GET — admin list of reports
$adminCheck = $bd->prepare("SELECT utl_admin FROM utilizador WHERE utl_id = ?");
$adminCheck->execute([$uid]);
if (!$adminCheck->fetchColumn()) { http_response_code(403); exit; }

$estado = $_GET['estado'] ?? '';
$where  = [];
$params = [];
if ($estado && in_array($estado, ['Pendente', 'Resolvido', 'Ignorado'], true)) {
    $where[]  = "d.den_estado = ?";
    $params[] = $estado;
}
$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $bd->prepare(
    "SELECT d.den_id, d.den_motivo, d.den_descricao, d.den_estado, d.den_criada,
            f.fer_id, f.fer_nome,
            u.utl_id AS reporter_id, u.utl_nome AS reporter,
            o.utl_id AS dono_id,    o.utl_nome AS dono_nome
     FROM denuncia d
     JOIN ferramenta f ON d.den_fer_id = f.fer_id
     JOIN utilizador u ON d.den_utl_id = u.utl_id
     JOIN utilizador o ON f.fer_utl_id = o.utl_id
     $whereStr
     ORDER BY d.den_criada DESC"
);
$stmt->execute($params);
echo json_encode(['reports' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);