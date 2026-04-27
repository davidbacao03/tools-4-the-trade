<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: login.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
	if(!array_key_exists('utl_foto', $_SESSION)) {
		$fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
		$fotoQ->execute([$_SESSION['utl_id']]);
		$_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
	}
	$userFoto = $_SESSION['utl_foto'];

	if(isset($_POST['nome'])) {
		$q = "INSERT INTO ferramenta (fer_utl_id, fer_cat_id, fer_nome, fer_descricao, fer_preco_base, fer_preco, fer_lat, fer_lng) VALUES (?,?,?,?,?,?,?,?)";
		$stat = $bd->prepare($q);
		$stat->execute([
			$_SESSION['utl_id'],
			$_POST['cat'],
			$_POST['nome'],
			$_POST['descricao'],
			$_POST['preco_base'],
			$_POST['preco'],
			$_POST['lat'] ?: null,
			$_POST['lng'] ?: null
		]);
		$newId = $bd->lastInsertId();

		// Handle image uploads
		if(!empty($_FILES['imagens']['name'][0])) {
			$uploadDir = __DIR__ . '/uploads/ferramentas/';
			$allowed   = ['jpg','jpeg','png','gif','webp'];
			$principal = true;
			foreach($_FILES['imagens']['tmp_name'] as $i => $tmp) {
				if($_FILES['imagens']['error'][$i] !== UPLOAD_ERR_OK) continue;
				$ext = strtolower(pathinfo($_FILES['imagens']['name'][$i], PATHINFO_EXTENSION));
				if(!in_array($ext, $allowed)) continue;
				$filename = $newId . '_' . $i . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
				move_uploaded_file($tmp, $uploadDir . $filename);
				$ins = $bd->prepare("INSERT INTO ferramenta_imagem (img_fer_id, img_path, img_principal, img_ordem) VALUES (?,?,?,?)");
				$ins->execute([$newId, 'uploads/ferramentas/' . $filename, $principal ? 1 : 0, $i]);
				$principal = false;
			}
		}

		header('Location: Ferramentas.php');
		exit;
	}

	$q = "SELECT * FROM categoria";
	$cats = $bd->query($q)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Adicionar ferramenta">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Ferramenta - Tools 4 The Trade</title>
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
                    <h1>Adicionar Ferramenta</h1>

                    <form class="tool-form" action="" method="post" enctype="multipart/form-data" data-redirect="Ferramentas.php">
                        <label for="nome">Nome da ferramenta</label>
                        <input type="text" id="nome" name="nome">

                        <label for="cat">Categoria</label>
                        <select id="cat" name="cat">
                        <?php foreach($cats as $c) { ?>
                            <option value="<?php echo $c['cat_id']; ?>"><?php echo $c['cat_nome']; ?></option>
                        <?php } ?>
                        </select>

                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao"></textarea>

                        <label for="preco_base">Preço base (€/dia)</label>
                        <input type="number" id="preco_base" name="preco_base" step="0.01">

                        <label for="preco">Preço atual (€/dia)</label>
                        <input type="number" id="preco" name="preco" step="0.01">

                        <label>Fotos da ferramenta</label>
                        <div class="foto-drop-zone" id="fotoDropZone">
                            <div class="foto-drop-content">
                                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z"/>
                                </svg>
                                <p>Arrasta fotos aqui ou <span class="foto-drop-link">clica para selecionar</span></p>
                                <small>JPG, PNG, WebP · A primeira foto é a principal</small>
                            </div>
                        </div>
                        <div class="foto-preview-grid" id="fotoPreviewGrid"></div>
                        <input type="file" id="imagens" name="imagens[]" multiple accept="image/*" style="display:none;">

                        <label>Localização da ferramenta</label>
                        <div id="mapa"></div>
                        <p id="mapa-info">Clica no mapa para definir a localização da ferramenta.</p>

                        <label for="lat">Latitude</label>
                        <input type="text" id="lat" name="lat">

                        <label for="lng">Longitude</label>
                        <input type="text" id="lng" name="lng">

                        <button type="submit">Guardar</button>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="../js/script.js?v=2"></script>
</body>
</html>
