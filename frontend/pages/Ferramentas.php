<?php
	session_start();
	if(!isset($_SESSION['utl_id'])) header('Location: login.php');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="Lista de ferramentas">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ferramentas - Tools 4 The Trade</title>
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
                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="profile-circle"></div>
                    <a href="logout.php">Sair</a>
                </div>
            </header>

            <main class="main-area">
                <section class="tools-section">
                    <h1>Lista de Ferramentas</h1>

                    <div class="page-action">
                        <a href="adicionarferramentas.php" class="simple-button">Adicionar Ferramenta</a>
                    </div>

                    <div class="tools-grid">
                        <article class="tool-card">
                            <h3>Berbequim</h3>
                            <p>Categoria: Elétrica</p>
                            <p>Preço: 10€/dia</p>
                            <button>Alugar</button>
                        </article>

                        <article class="tool-card">
                            <h3>Martelo</h3>
                            <p>Categoria: Manual</p>
                            <p>Preço: 4€/dia</p>
                            <button>Alugar</button>
                        </article>

                        <article class="tool-card">
                            <h3>Escadote</h3>
                            <p>Categoria: Construção</p>
                            <p>Preço: 9€/dia</p>
                            <button>Alugar</button>
                        </article>

                        <article class="tool-card">
                            <h3>Lixadora</h3>
                            <p>Categoria: Elétrica</p>
                            <p>Preço: 11€/dia</p>
                            <button>Alugar</button>
                        </article>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
