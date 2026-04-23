<?php
session_start();
if(!isset($_SESSION['utl_id'])) { http_response_code(403); exit; }

$bd  = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
$uid = $_SESSION['utl_id'];

if($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['foto'])) {
    http_response_code(400); exit;
}

$file    = $_FILES['foto'];
$allowed = ['jpg','jpeg','png','gif','webp'];

if($file['error'] !== UPLOAD_ERR_OK) { http_response_code(400); exit; }

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if(!in_array($ext, $allowed)) { http_response_code(400); exit; }

// Delete old photo file
$oldStmt = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
$oldStmt->execute([$uid]);
$oldPath = $oldStmt->fetchColumn();
if($oldPath) @unlink(__DIR__ . '/' . $oldPath);

$filename  = 'perfil_' . $uid . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
$uploadDir = __DIR__ . '/uploads/perfis/';
move_uploaded_file($file['tmp_name'], $uploadDir . $filename);

$path = 'uploads/perfis/' . $filename;
$bd->prepare("UPDATE utilizador SET utl_foto = ? WHERE utl_id = ?")->execute([$path, $uid]);
$_SESSION['utl_foto'] = $path;

header('Content-Type: application/json');
echo json_encode(['path' => $path]);
