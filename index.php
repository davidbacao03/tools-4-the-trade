<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: registar.php');
	$bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
	$q = "SELECT f.fer_id, f.fer_nome, f.fer_descricao, f.fer_preco, f.fer_preco_base, f.fer_lat, f.fer_lng, c.cat_nome,
	             (SELECT COUNT(*) FROM aluguer a WHERE a.alu_fer_id = f.fer_id AND a.alu_estado IN ('Reservado','Alugado')) > 0 AS ocupada,
	             (SELECT COUNT(*) FROM aluguer a2 WHERE a2.alu_fer_id = f.fer_id) AS total_alugueres
	      FROM ferramenta f
	      JOIN categoria c ON f.fer_cat_id = c.cat_id
	      WHERE f.fer_ativa = 1
	      ORDER BY total_alugueres DESC
	      LIMIT 10";
	$ferramentas = $bd->query($q)->fetchAll(PDO::FETCH_ASSOC);
	$ferramentasComLoc = array_filter($ferramentas, fn($f) => $f['fer_lat'] !== null && $f['fer_lng'] !== null);
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

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            width: 420px;
            max-width: 90%;
            position: relative;
        }
        .modal-box h2 { margin-top: 0; }
        .modal-close {
            position: absolute;
            top: 14px;
            right: 18px;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #555;
        }
        .modal-close:hover { color: #000; }
        .modal-field { margin-bottom: 10px; }
        .modal-label { font-weight: bold; margin-right: 4px; }
        .modal-actions { margin-top: 20px; }
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
                <a href="calendario.php">Calendário</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <a href="perfil.php" class="profile-circle" title="Perfil"></a>
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

             const ferramentas = <?php echo json_encode(array_values($ferramentasComLoc)); ?>;
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
                    <h2>Top 10 ferramentas mais alugadas</h2>

                    <div class="tools-grid">
                        <?php if(empty($ferramentas)): ?>
                            <p>Não existem ferramentas disponíveis de momento.</p>
                        <?php else: ?>
                            <?php foreach($ferramentas as $f): ?>
                                <article class="tool-card"
                                    data-lat="<?php echo $f['fer_lat'] !== null ? (float)$f['fer_lat'] : ''; ?>"
                                    data-lng="<?php echo $f['fer_lng'] !== null ? (float)$f['fer_lng'] : ''; ?>">
                                    <h3><?php echo htmlspecialchars($f['fer_nome']); ?></h3>
                                    <p>Categoria: <?php echo htmlspecialchars($f['cat_nome']); ?></p>
                                    <p><?php echo number_format($f['fer_preco'], 2); ?>€/dia</p>
                                    <?php if($f['ocupada']): ?>
                                        <span class="badge-indisponivel">Indisponível</span>
                                    <?php endif; ?>
                                    <button class="btn-ver-mais"
                                        data-id="<?php echo $f['fer_id']; ?>"
                                        data-ocupada="<?php echo $f['ocupada'] ? '1' : '0'; ?>"
                                        data-nome="<?php echo htmlspecialchars($f['fer_nome'], ENT_QUOTES); ?>"
                                        data-categoria="<?php echo htmlspecialchars($f['cat_nome'], ENT_QUOTES); ?>"
                                        data-descricao="<?php echo htmlspecialchars($f['fer_descricao'] ?? '', ENT_QUOTES); ?>"
                                        data-preco="<?php echo number_format($f['fer_preco'], 2); ?>"
                                        data-preco-base="<?php echo number_format($f['fer_preco_base'], 2); ?>">Ver mais</button>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <button class="modal-close" id="modalClose">&times;</button>
            <h2 id="modalNome"></h2>
            <div class="modal-field"><span class="modal-label">Categoria:</span><span id="modalCategoria"></span></div>
            <div class="modal-field"><span class="modal-label">Descrição:</span><span id="modalDescricao"></span></div>
            <div class="modal-field"><span class="modal-label">Preço base:</span><span id="modalPrecoBase"></span>€/dia</div>
            <div class="modal-field"><span class="modal-label">Preço atual:</span><span id="modalPreco"></span>€/dia</div>
            <div class="modal-actions">
                <a href="#" id="modalAlugarLink" class="simple-button">Alugar</a>
                <span id="modalIndisponivel" class="badge-indisponivel" style="display:none;">Indisponível</span>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        const overlay = document.getElementById('modalOverlay');
        document.querySelectorAll('.btn-ver-mais').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('modalNome').textContent = btn.dataset.nome;
                document.getElementById('modalCategoria').textContent = btn.dataset.categoria;
                document.getElementById('modalDescricao').textContent = btn.dataset.descricao || 'Sem descrição disponível.';
                document.getElementById('modalPreco').textContent = btn.dataset.preco;
                document.getElementById('modalPrecoBase').textContent = btn.dataset.precoBase;
                const ocupada = btn.dataset.ocupada === '1';
                const alugarLink = document.getElementById('modalAlugarLink');
                const indisponivel = document.getElementById('modalIndisponivel');
                alugarLink.style.display = ocupada ? 'none' : '';
                indisponivel.style.display = ocupada ? '' : 'none';
                alugarLink.href = 'alugarferramenta.php?id=' + btn.dataset.id;
                overlay.classList.add('active');
            });
        });
        document.getElementById('modalClose').addEventListener('click', function() {
            overlay.classList.remove('active');
        });
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    </script>
</body>
</html>