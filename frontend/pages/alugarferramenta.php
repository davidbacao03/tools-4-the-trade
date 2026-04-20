<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if($id === 0) { header('Location: ferramentas.php'); exit; }

    $q = "SELECT f.*, c.cat_nome FROM ferramenta f
          JOIN categoria c ON f.fer_cat_id = c.cat_id
          WHERE f.fer_id = ? AND f.fer_ativa = 1";
    $stmt = $bd->prepare($q);
    $stmt->execute([$id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$f) { header('Location: ferramentas.php'); exit; }

    $chk = $bd->prepare("SELECT COUNT(*) FROM aluguer WHERE alu_fer_id = ? AND alu_estado IN ('Reservado','Alugado')");
    $chk->execute([$id]);
    $ocupada = $chk->fetchColumn() > 0;

    $erro = '';
    $sucesso = false;

    if($ocupada && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $erro = 'Esta ferramenta já se encontra reservada ou alugada.';
    } elseif($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inicio = $_POST['inicio'] ?? '';
        $fim    = $_POST['fim']    ?? '';

        if(!$inicio || !$fim) {
            $erro = 'Por favor preenche as datas de início e fim.';
        } elseif($fim <= $inicio) {
            $erro = 'A data de fim tem de ser posterior à data de início.';
        } else {
            $ins = "INSERT INTO aluguer (alu_fer_id, alu_utl_id, alu_inicio, alu_fim)
                    VALUES (?, ?, ?, ?)";
            $stmt2 = $bd->prepare($ins);
            $stmt2->execute([$id, $_SESSION['utl_id'], $inicio, $fim]);
            $sucesso = true;
        }
    }

    $hoje = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alugar Ferramenta - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .rental-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .tool-info-card {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 20px;
        }
        .tool-info-card h2 { margin-top: 0; }
        .info-row { margin-bottom: 10px; }
        .info-label { font-weight: bold; margin-right: 6px; }
        .price-summary {
            background: #333;
            color: #fff;
            border-radius: 6px;
            padding: 16px 20px;
            margin-top: 16px;
            font-size: 1.1rem;
        }
        .price-summary span { font-weight: bold; }
        .msg-erro {
            background: #fdecea;
            border: 1px solid #f5c6c2;
            color: #c0392b;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        .msg-sucesso {
            background: #eafaf1;
            border: 1px solid #a9dfbf;
            color: #1e8449;
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        @media(max-width: 700px) {
            .rental-grid { grid-template-columns: 1fr; }
        }
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
                    <h1>Alugar Ferramenta</h1>

                    <?php if($sucesso): ?>
                        <div class="msg-sucesso">
                            Aluguer registado com sucesso! <a href="index.php">Voltar ao início</a>
                        </div>
                    <?php elseif($ocupada): ?>
                        <div class="msg-erro">
                            Esta ferramenta já se encontra reservada ou alugada. <a href="ferramentas.php">Ver outras ferramentas</a>
                        </div>
                    <?php else: ?>

                    <?php if($erro): ?>
                        <div class="msg-erro"><?php echo htmlspecialchars($erro); ?></div>
                    <?php endif; ?>

                    <div class="rental-grid">
                        <div class="tool-info-card">
                            <h2><?php echo htmlspecialchars($f['fer_nome']); ?></h2>
                            <div class="info-row">
                                <span class="info-label">Categoria:</span><?php echo htmlspecialchars($f['cat_nome']); ?>
                            </div>
                            <?php if($f['fer_descricao']): ?>
                            <div class="info-row">
                                <span class="info-label">Descrição:</span><?php echo htmlspecialchars($f['fer_descricao']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <span class="info-label">Preço base:</span><?php echo number_format($f['fer_preco_base'], 2); ?>€/dia
                            </div>
                            <div class="info-row">
                                <span class="info-label">Preço atual:</span><?php echo number_format($f['fer_preco'], 2); ?>€/dia
                            </div>
                        </div>

                        <form class="tool-form" method="post">
                            <label for="inicio">Data de início</label>
                            <input type="date" id="inicio" name="inicio" min="<?php echo $hoje; ?>"
                                   value="<?php echo htmlspecialchars($_POST['inicio'] ?? ''); ?>">

                            <label for="fim">Data de fim</label>
                            <input type="date" id="fim" name="fim" min="<?php echo $hoje; ?>"
                                   value="<?php echo htmlspecialchars($_POST['fim'] ?? ''); ?>">

                            <div class="price-summary" id="resumoPreco" style="display:none;">
                                Total estimado: <span id="totalPreco">—</span>
                            </div>

                            <button type="submit" style="margin-top:16px;">Confirmar aluguer</button>
                        </form>
                    </div>

                    <?php endif; ?>
                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        const precoDia = <?php echo (float)$f['fer_preco']; ?>;
        const inicio = document.getElementById('inicio');
        const fim    = document.getElementById('fim');
        const resumo = document.getElementById('resumoPreco');
        const total  = document.getElementById('totalPreco');

        function calcular() {
            if(!inicio.value || !fim.value) { resumo.style.display = 'none'; return; }
            const dias = Math.round((new Date(fim.value) - new Date(inicio.value)) / 86400000);
            if(dias <= 0) { resumo.style.display = 'none'; return; }
            total.textContent = (dias * precoDia).toFixed(2) + '€ (' + dias + ' dia' + (dias > 1 ? 's' : '') + ')';
            resumo.style.display = 'block';
        }

        inicio.addEventListener('change', function() {
            if(fim.value && fim.value <= inicio.value) fim.value = '';
            fim.min = inicio.value;
            calcular();
        });
        fim.addEventListener('change', calcular);

        calcular();
    </script>
</body>
</html>
