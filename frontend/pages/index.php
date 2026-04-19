<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: registar.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
	$q = "SELECT f.*, c.cat_nome FROM ferramenta f
	      JOIN categoria c ON f.fer_cat_id = c.cat_id
	      WHERE f.fer_ativa = 1 AND f.fer_lat IS NOT NULL AND f.fer_lng IS NOT NULL";
	$ferramentas = $bd->query($q)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tools 4 The Trade">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #mapa { height: 400px; width: 100%; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="ferramentas.php">Ferramentas</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="mapa.php">Mapa</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="logout.php">Sair</a>
                    <div class="profile-circle"></div>
                </div>
            </header>

            <main class="main-area">
                <section class="hero">
                    <h1>TOOLS 4 THE TRADE</h1>
                    <p>Encontre e alugue ferramentas de forma simples e rápida.</p>
                </section>

                <section class="map-section">
                    <h2>Mapa de Ferramentas</h2>
                    <div id="mapa"></div>
                        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
             const map = L.map('mapa').setView([39.5, -8.0], 7);
             L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
             attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
             }).addTo(map);

             const ferramentas = <?php echo json_encode($ferramentas); ?>;
              ferramentas.forEach(function(f) {
                   L.marker([parseFloat(f.fer_lat), parseFloat(f.fer_lng)])
                    .addTo(map)
                         .bindPopup(
                    '<b>' + f.fer_nome + '</b><br>' +
                    'Categoria: ' + f.cat_nome + '<br>' +
                    'Preço: ' + f.fer_preco + '€/dia'
                );
        });
    </script>
                </section>

                <section class="tools-section">
                    <h2>Ferramentas em destaque</h2>

                    <div class="tools-grid">
                        <article class="tool-card">
                            <h3>Berbequim</h3>
                            <p>10€/dia</p>
                            <button>Ver mais</button>
                        </article>

                        <article class="tool-card">
                            <h3>Escada</h3>
                            <p>8€/dia</p>
                            <button>Ver mais</button>
                        </article>

                        <article class="tool-card">
                            <h3>Serra elétrica</h3>
                            <p>15€/dia</p>
                            <button>Ver mais</button>
                        </article>

                        <article class="tool-card">
                            <h3>Caixa de ferramentas</h3>
                            <p>12€/dia</p>
                            <button>Ver mais</button>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
