<?php
session_start();
if (!isset($_SESSION['utl_id'])) { http_response_code(401); exit; }
header('Content-Type: application/json');

$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");

$favStmt = $bd->prepare("SELECT fav_fer_id FROM favorito WHERE fav_utl_id = ?");
$favStmt->execute([$_SESSION['utl_id']]);
$favIds = array_flip($favStmt->fetchAll(PDO::FETCH_COLUMN));

$where   = ["f.fer_ativa = 1", "f.fer_utl_id != ?"];
$params  = [(int)$_SESSION['utl_id']];

if (isset($_GET['cat']) && $_GET['cat'] === 'favoritos') {
    $where[]  = "f.fer_id IN (SELECT fav_fer_id FROM favorito WHERE fav_utl_id = ?)";
    $params[] = $_SESSION['utl_id'];
} elseif (!empty($_GET['cat'])) {
    $where[]  = "f.fer_cat_id = ?";
    $params[] = (int)$_GET['cat'];
}
if (isset($_GET['preco_min']) && $_GET['preco_min'] !== '') {
    $where[]  = "f.fer_preco_base >= ?";
    $params[] = (float)$_GET['preco_min'];
}
if (isset($_GET['preco_max']) && $_GET['preco_max'] !== '') {
    $where[]  = "f.fer_preco_base <= ?";
    $params[] = (float)$_GET['preco_max'];
}
if (!empty($_GET['disponivel'])) {
    $where[] = "(SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) = 0";
}

$whereStr = implode(' AND ', $where);
$stmt = $bd->prepare(
    "SELECT f.fer_id, f.fer_utl_id, f.fer_nome, f.fer_descricao, f.fer_preco, f.fer_preco_base,
            f.fer_lat, f.fer_lng, f.fer_desconto_dias, f.fer_preco_desconto, c.cat_nome,
            u.utl_nome AS dono_nome, u.utl_foto AS dono_foto,
            (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
            (SELECT img_path FROM ferramenta_imagem WHERE img_fer_id = f.fer_id AND img_principal = 1 LIMIT 1) AS img_principal,
            ROUND((SELECT AVG(av.ava_nota_fer)  FROM avaliacao av  WHERE av.ava_fer_id  = f.fer_id),       1) AS avg_nota_fer,
            (SELECT COUNT(*) FROM avaliacao av WHERE av.ava_fer_id = f.fer_id) AS total_avaliacoes,
            ROUND((SELECT AVG(av2.ava_nota_dono) FROM avaliacao av2 WHERE av2.ava_dono_id = f.fer_utl_id), 1) AS avg_nota_dono
     FROM ferramenta f
     JOIN categoria c ON f.fer_cat_id = c.cat_id
     JOIN utilizador u ON f.fer_utl_id = u.utl_id
     WHERE $whereStr
     ORDER BY f.fer_nome"
);
$stmt->execute($params);
$tools = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tools as &$tool) {
    $tool['is_favorito'] = isset($favIds[$tool['fer_id']]) ? 1 : 0;
}
unset($tool);

echo json_encode(['tools' => $tools]);
