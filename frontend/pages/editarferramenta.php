<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) { header('Location: login.php'); exit; }

    $bd  = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = $_SESSION['utl_id'];
    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$uid]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];
    $id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Verify ownership
    $stmt = $bd->prepare(
        "SELECT f.*, c.cat_nome FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE f.fer_id = ? AND f.fer_utl_id = ?"
    );
    $stmt->execute([$id, $uid]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$f) { header('Location: perfil.php'); exit; }

    $imgStmt = $bd->prepare(
        "SELECT img_id, img_path, img_principal FROM ferramenta_imagem
         WHERE img_fer_id = ? ORDER BY img_principal DESC, img_ordem ASC"
    );
    $imgStmt->execute([$id]);
    $imagens = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

    $cats = $bd->query("SELECT * FROM categoria ORDER BY cat_nome")->fetchAll();

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update basic info
        $descontoDias  = !empty($_POST['desconto_dias'])  ? (int)$_POST['desconto_dias']    : null;
        $precoDesconto = !empty($_POST['desconto_preco']) ? (float)$_POST['desconto_preco'] : null;
        $upd = $bd->prepare(
            "UPDATE ferramenta SET fer_cat_id=?, fer_nome=?, fer_descricao=?, fer_preco_base=?, fer_preco=?,
             fer_desconto_dias=?, fer_preco_desconto=?, fer_lat=?, fer_lng=?
             WHERE fer_id=? AND fer_utl_id=?"
        );
        $upd->execute([
            $_POST['cat'],
            $_POST['nome'],
            $_POST['descricao'],
            $_POST['preco_base'],
            $_POST['preco_base'],
            $descontoDias,
            $precoDesconto,
            $_POST['lat'] ?: null,
            $_POST['lng'] ?: null,
            $id, $uid
        ]);

        // Delete marked images
        if(!empty($_POST['delete_imgs'])) {
            foreach($_POST['delete_imgs'] as $imgId) {
                $imgId = (int)$imgId;
                $getPath = $bd->prepare("SELECT img_path FROM ferramenta_imagem WHERE img_id=? AND img_fer_id=?");
                $getPath->execute([$imgId, $id]);
                $path = $getPath->fetchColumn();
                if($path) {
                    @unlink(__DIR__ . '/' . $path);
                    $bd->prepare("DELETE FROM ferramenta_imagem WHERE img_id=?")->execute([$imgId]);
                }
            }
        }

        // Update principal among existing images
        if(!empty($_POST['img_principal_id'])) {
            $pid = (int)$_POST['img_principal_id'];
            $bd->prepare("UPDATE ferramenta_imagem SET img_principal=0 WHERE img_fer_id=?")->execute([$id]);
            $bd->prepare("UPDATE ferramenta_imagem SET img_principal=1 WHERE img_id=? AND img_fer_id=?")->execute([$pid, $id]);
        }

        // Upload new images
        if(!empty($_FILES['imagens']['name'][0])) {
            $uploadDir = __DIR__ . '/uploads/ferramentas/';
            $allowed   = ['jpg','jpeg','png','gif','webp'];
            $hasPrincipal = !empty($_POST['img_principal_id']);

            foreach($_FILES['imagens']['tmp_name'] as $i => $tmp) {
                if($_FILES['imagens']['error'][$i] !== UPLOAD_ERR_OK) continue;
                $ext = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
                if(!in_array($ext, $allowed)) continue;
                $filename = $id . '_' . $i . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file($tmp, $uploadDir . $filename);
                $newPrincipal = (!$hasPrincipal && $i === 0) ? 1 : 0;
                if($newPrincipal) {
                    $bd->prepare("UPDATE ferramenta_imagem SET img_principal=0 WHERE img_fer_id=?")->execute([$id]);
                }
                $ins = $bd->prepare("INSERT INTO ferramenta_imagem (img_fer_id, img_path, img_principal, img_ordem) VALUES (?,?,?,?)");
                $ins->execute([$id, 'uploads/ferramentas/' . $filename, $newPrincipal, $i]);
                $hasPrincipal = true;
            }
        }

        $_SESSION['flash'] = 'Ferramenta atualizada com sucesso.';
        header('Location: perfil.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ferramenta - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="Ferramentas.php">Ferramentas</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="calendario.php">Calendário</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="perfil.php" class="profile-circle" title="Perfil" <?php if(!empty($userFoto)): ?>style="background-image:url('<?php echo htmlspecialchars($userFoto); ?>');background-size:cover;background-color:transparent;"<?php endif; ?>></a>
                </div>
            </header>

            <main class="main-area">
                <section class="form-section">
                    <h1>Editar Ferramenta</h1>

                    <form class="tool-form" method="post" enctype="multipart/form-data" data-redirect="perfil.php">

                        <label for="nome">Nome da ferramenta</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($f['fer_nome']); ?>">

                        <label for="cat">Categoria</label>
                        <select id="cat" name="cat">
                            <?php foreach($cats as $c): ?>
                                <option value="<?php echo $c['cat_id']; ?>" <?php echo $c['cat_id'] == $f['fer_cat_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['cat_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"><?php echo htmlspecialchars($f['fer_descricao'] ?? ''); ?></textarea>

                        <label for="preco_base">Preço (€/dia)</label>
                        <input type="number" id="preco_base" name="preco_base" step="0.01" value="<?php echo $f['fer_preco_base']; ?>">

                        <label>Desconto por aluguer prolongado <span class="label-opt">(opcional)</span></label>
                        <div class="discount-row">
                            <span>A partir de</span>
                            <input type="number" id="desconto_dias" name="desconto_dias" min="2" placeholder="Nº dias"
                                   value="<?php echo $f['fer_desconto_dias'] ?? ''; ?>">
                            <span>dias, preço de</span>
                            <input type="number" id="desconto_preco" name="desconto_preco" step="0.01" min="0" placeholder="€/dia"
                                   value="<?php echo $f['fer_preco_desconto'] ?? ''; ?>">
                            <span>€/dia</span>
                        </div>

                        <!-- Existing photos -->
                        <?php if(!empty($imagens)): ?>
                        <label>Fotos atuais</label>
                        <div class="foto-preview-grid" id="fotoExistingGrid"></div>
                        <div id="deleteImgsContainer"></div>
                        <input type="hidden" id="imgPrincipalId" name="img_principal_id"
                               value="<?php echo $imagens[0]['img_id']; ?>">
                        <?php endif; ?>

                        <!-- New photos -->
                        <label>Adicionar novas fotos</label>
                        <div class="foto-drop-zone" id="fotoDropZone">
                            <div class="foto-drop-content">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z"/>
                                </svg>
                                <p>Arrasta fotos aqui ou <span class="foto-drop-link">clica para selecionar</span></p>
                                <small>JPG, PNG, WebP · A primeira foto adicionada será a principal se não houver nenhuma selecionada acima</small>
                            </div>
                        </div>
                        <div class="foto-preview-grid" id="fotoPreviewGrid"></div>
                        <input type="file" id="imagens" name="imagens[]" multiple accept="image/*" style="display:none;">

                        <!-- Map -->
                        <label>Localização da ferramenta</label>
                        <div id="mapa"
                             data-lat="<?php echo $f['fer_lat'] !== null ? (float)$f['fer_lat'] : ''; ?>"
                             data-lng="<?php echo $f['fer_lng'] !== null ? (float)$f['fer_lng'] : ''; ?>"></div>
                        <p id="mapa-info">Clica no mapa para alterar a localização.</p>

                        <label for="lat">Latitude</label>
                        <input type="text" id="lat" name="lat" value="<?php echo $f['fer_lat'] !== null ? (float)$f['fer_lat'] : ''; ?>">

                        <label for="lng">Longitude</label>
                        <input type="text" id="lng" name="lng" value="<?php echo $f['fer_lng'] !== null ? (float)$f['fer_lng'] : ''; ?>">

                        <div style="display:flex; gap:12px; margin-top:8px;">
                            <button type="submit">Guardar alterações</button>
                            <a href="perfil.php" class="simple-button" style="background:#888;">Cancelar</a>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script>window.existingPhotos = <?php echo json_encode(array_map(function($img) { return ['id' => $img['img_id'], 'path' => $img['img_path'], 'principal' => (bool)$img['img_principal']]; }, $imagens)); ?>;</script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../js/script.js?v=2"></script>
</body>
</html>
