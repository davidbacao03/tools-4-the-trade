<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['utl_id'])) { http_response_code(401); echo json_encode(['error' => 'not_logged_in']); exit; }

$bd    = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
$ferId = (int)($_POST['fer_id'] ?? 0);
$uid   = (int)$_SESSION['utl_id'];

if (!$ferId) { http_response_code(400); echo json_encode(['error' => 'invalid_id']); exit; }

$check = $bd->prepare("SELECT fav_id FROM favorito WHERE fav_utl_id = ? AND fav_fer_id = ?");
$check->execute([$uid, $ferId]);

if ($check->fetch()) {
    $bd->prepare("DELETE FROM favorito WHERE fav_utl_id = ? AND fav_fer_id = ?")->execute([$uid, $ferId]);
    echo json_encode(['favorito' => false]);
} else {
    $bd->prepare("INSERT INTO favorito (fav_utl_id, fav_fer_id) VALUES (?,?)")->execute([$uid, $ferId]);
    echo json_encode(['favorito' => true]);
}
