<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: login.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");

	if(isset($_POST['nome'])) {
		$q = "INSERT INTO ferramenta (fer_utl_id, fer_cat_id, fer_nome, fer_descricao, fer_preco_base, fer_preco, fer_lat, fer_lng) VALUES (?,?,?,?,?,?,?,?)";
		$stat = $bd->prepare($q);
		$stat->execute(array(
			$_SESSION['utl_id'],
			$_POST['cat'],
			$_POST['nome'],
			$_POST['descricao'],
			$_POST['preco_base'],
			$_POST['preco'],
			$_POST['lat'],
			$_POST['lng']
		));
		header('Location: Ferramentas.php');
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
    <style>
        #mapa { height: 350px; width: 100%; border-radius: 8px; margin-bottom: 8px; border: 1px solid #ccc; }
        #mapa-info { font-size: 0.85rem; color: #666; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="Ferramentas.php">Ferramentas</a>
                <a href="dashboard.php">Dashboard</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="logout.php">Sair</a>
                    <a href="perfil.php" class="profile-circle" title="Perfil"></a>
                </div>
            </header>

            <main class="main-area">
                <section class="form-section">
                    <h1>Adicionar Ferramenta</h1>

                    <form class="tool-form" action="" method="post">
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

                        <label>Localização da ferramenta</label>
                        <div id="mapa"></div>
                        <p id="mapa-info"> Clica no mapa para definir a localização da ferramenta.</p>

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

    <script src="../js/script.js"></script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // esconder campos latitude/longitude (o mapa preenche)
        document.getElementById('lat').closest('label') && (document.querySelector('label[for="lat"]').style.display = 'none');
        document.getElementById('lat').style.display = 'none';
        document.querySelector('label[for="lng"]').style.display = 'none';
        document.getElementById('lng').style.display = 'none';

        const map = L.map('mapa').setView([39.5, -8.0], 7);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        let marcador = null;
        map.locate({ setView: true, maxZoom: 14 });

        map.on('click', function(e) {
            document.getElementById('lat').value = e.latlng.lat.toFixed(7);
            document.getElementById('lng').value = e.latlng.lng.toFixed(7);
            document.getElementById('mapa-info').textContent =
                'Localização selecionada: ' + e.latlng.lat.toFixed(7) + ', ' + e.latlng.lng.toFixed(7);
            if (marcador) { marcador.setLatLng(e.latlng); }
            else { marcador = L.marker(e.latlng).addTo(map).bindPopup('Localização da ferramenta').openPopup(); }
        });
    </script>
</body>
</html>