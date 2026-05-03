<?php
session_start();
if (!isset($_SESSION['utl_id'])) { http_response_code(401); exit; }
header('Content-Type: application/json');

$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
$uid = $_SESSION['utl_id'];

$check = $bd->prepare("SELECT utl_admin FROM utilizador WHERE utl_id = ?");
$check->execute([$uid]);
if (!$check->fetchColumn()) { http_response_code(403); exit; }

$catFiltro  = (int)($_GET['cat']  ?? 0);
$nomeFiltro = trim($_GET['nome']  ?? '');
$where  = [];
$params = [];

if ($catFiltro) {
    $where[]  = "f.fer_cat_id = ?";
    $params[] = $catFiltro;
}
if ($nomeFiltro !== '') {
    $where[]  = "f.fer_nome LIKE ?";
    $params[] = '%' . $nomeFiltro . '%';
}
$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $bd->prepare(
    "SELECT f.fer_id, f.fer_nome, f.fer_preco, f.fer_ativa, c.cat_nome, u.utl_nome AS dono_nome,
            (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) AS alugueres_ativos
     FROM ferramenta f
     JOIN categoria c ON f.fer_cat_id = c.cat_id
     JOIN utilizador u ON f.fer_utl_id = u.utl_id
     $whereStr
     ORDER BY f.fer_criada DESC"
);
$stmt->execute($params);
echo json_encode(['tools' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
