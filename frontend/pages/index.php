<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: registar.php');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tools 4 The Trade">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="layout">

        <aside class="sidebar">
            <h2 class="logo">Tools 4 The Trade</h2>
            <nav class="menu">
                <a href="index.php">Home</a>
                <a href="ferramentas.php">Ferramentas</a>
                <a href="dashboard.php">Dashboard</a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="search-box">
                    <input type="text" placeholder="Pesquisar ferramenta...">
                </div>
                <div class="profile-circle"></div>
            </header>

            <main class="main-area">
                <section class="hero">
                    <h1>TOOLS 4 THE TRADE</h1>
                    <p>Encontre e alugue ferramentas de forma simples e rápida.</p>
                </section>

                <section class="map-section">
                    <h2>Mapa de Ferramentas</h2>
                    <div class="map-placeholder">
                        Mapa OpenStreetMap aqui
                    </div>
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
