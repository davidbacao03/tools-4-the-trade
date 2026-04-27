<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd = new PDO("mysql:host=localhost;dbname=tools4thetrade;charset=utf8mb4", "root", "");
    $uid = $_SESSION['utl_id'];
    if(!array_key_exists('utl_foto', $_SESSION)) {
        $fotoQ = $bd->prepare("SELECT utl_foto FROM utilizador WHERE utl_id = ?");
        $fotoQ->execute([$uid]);
        $_SESSION['utl_foto'] = $fotoQ->fetchColumn() ?: '';
    }
    $userFoto = $_SESSION['utl_foto'];

    // Overview counts
    $totalMinhas = $bd->prepare("SELECT COUNT(*) FROM ferramenta WHERE fer_utl_id = ? AND fer_ativa = 1");
    $totalMinhas->execute([$uid]);
    $cntMinhas = $totalMinhas->fetchColumn();

    $ocupadas = $bd->prepare(
        "SELECT COUNT(DISTINCT a.alu_fer_id) FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         WHERE f.fer_utl_id = ? AND a.alu_estado IN ('Reservado','Alugado')"
    );
    $ocupadas->execute([$uid]);
    $cntOcupadas = $ocupadas->fetchColumn();

    $totalAlugueresMinhas = $bd->prepare(
        "SELECT COUNT(*) FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         WHERE f.fer_utl_id = ?"
    );
    $totalAlugueresMinhas->execute([$uid]);
    $cntAlugueresMinhas = $totalAlugueresMinhas->fetchColumn();

    $meusAtivos = $bd->prepare(
        "SELECT COUNT(*) FROM aluguer WHERE alu_utl_id = ? AND alu_estado IN ('Reservado','Alugado')"
    );
    $meusAtivos->execute([$uid]);
    $cntMeusAtivos = $meusAtivos->fetchColumn();

    // Per-tool usage stats
    $toolStats = $bd->prepare(
        "SELECT f.fer_id, f.fer_nome, c.cat_nome, f.fer_preco,
                COUNT(a.alu_id) AS total_alugueres,
                COALESCE(SUM(DATEDIFF(COALESCE(DATE(a.alu_devolvido), a.alu_fim), a.alu_inicio)), 0) AS total_dias,
                MAX(a.alu_inicio) AS ultimo_aluguer,
                (SELECT a2.alu_estado FROM aluguer a2
                 WHERE a2.alu_fer_id = f.fer_id
                   AND a2.alu_estado IN ('Reservado','Alugado')
                 LIMIT 1) AS estado_atual
         FROM ferramenta f
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         LEFT JOIN aluguer a ON a.alu_fer_id = f.fer_id
         WHERE f.fer_utl_id = ? AND f.fer_ativa = 1
         GROUP BY f.fer_id, f.fer_nome, c.cat_nome, f.fer_preco
         ORDER BY total_alugueres DESC, total_dias DESC"
    );
    $toolStats->execute([$uid]);
    $minhasStats = $toolStats->fetchAll(PDO::FETCH_ASSOC);

    // My active rentals (tools I'm currently renting)
    $ativos = $bd->prepare(
        "SELECT a.alu_id, a.alu_inicio, a.alu_fim, a.alu_estado,
                f.fer_nome, c.cat_nome,
                u.utl_nome AS dono_nome
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         JOIN utilizador u ON f.fer_utl_id = u.utl_id
         WHERE a.alu_utl_id = ? AND a.alu_estado IN ('Reservado','Alugado')
         ORDER BY a.alu_inicio ASC"
    );
    $ativos->execute([$uid]);
    $meusAlugueres = $ativos->fetchAll(PDO::FETCH_ASSOC);

    // My rental history
    $hist = $bd->prepare(
        "SELECT a.alu_inicio, a.alu_fim, a.alu_devolvido, a.alu_estado,
                f.fer_nome, c.cat_nome,
                DATEDIFF(COALESCE(DATE(a.alu_devolvido), a.alu_fim), a.alu_inicio) AS dias
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         WHERE a.alu_utl_id = ?
         ORDER BY a.alu_criado DESC"
    );
    $hist->execute([$uid]);
    $historico = $hist->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
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

                <!-- Overview -->
                <section class="dashboard-section">
                    <h1 class="section-title">Visão geral</h1>
                    <div class="stats-grid-2">
                        <div class="stat-box-accent">
                            <h3>Minhas ferramentas</h3>
                            <div class="stat-num"><?php echo $cntMinhas; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Atualmente ocupadas</h3>
                            <div class="stat-num"><?php echo $cntOcupadas; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Total de alugueres recebidos</h3>
                            <div class="stat-num"><?php echo $cntAlugueresMinhas; ?></div>
                        </div>
                        <div class="stat-box-accent">
                            <h3>Ferramentas que estou a alugar</h3>
                            <div class="stat-num"><?php echo $cntMeusAtivos; ?></div>
                        </div>
                    </div>
                </section>

                <!-- Tool usage tracker -->
                <section class="dashboard-section">
                    <h2 class="section-title">Rastreio das minhas ferramentas</h2>
                    <?php if(empty($minhasStats)): ?>
                        <p class="empty-msg">Ainda não tens ferramentas registadas.</p>
                    <?php else:
                        $maxDias = max(array_column($minhasStats, 'total_dias')) ?: 1;
                    ?>
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Ferramenta</th>
                                    <th>Categoria</th>
                                    <th>Vezes alugada</th>
                                    <th>Total de dias em uso</th>
                                    <th>Último aluguer</th>
                                    <th>Estado atual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($minhasStats as $t):
                                    $estado = $t['estado_atual'] ?? 'Disponivel';
                                    $pct = $maxDias > 0 ? round(($t['total_dias'] / $maxDias) * 100) : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['fer_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['cat_nome']); ?></td>
                                    <td><?php echo $t['total_alugueres']; ?>×</td>
                                    <td>
                                        <?php echo $t['total_dias']; ?> dia<?php echo $t['total_dias'] != 1 ? 's' : ''; ?>
                                        <div style="margin-top:4px;">
                                            <div class="usage-bar-wrap"><div class="usage-bar" style="width:<?php echo $pct; ?>%"></div></div>
                                        </div>
                                    </td>
                                    <td><?php echo $t['ultimo_aluguer'] ? date('d/m/Y', strtotime($t['ultimo_aluguer'])) : '—'; ?></td>
                                    <td><span class="estado-badge estado-<?php echo $estado; ?>"><?php echo $estado === 'Disponivel' ? 'Disponível' : $estado; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <!-- Active rentals I'm doing -->
                <section class="dashboard-section">
                    <h2 class="section-title">Ferramentas que estou a alugar agora</h2>
                    <?php if(empty($meusAlugueres)): ?>
                        <p class="empty-msg">Não tens alugueres ativos de momento.</p>
                    <?php else: ?>
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Ferramenta</th>
                                    <th>Categoria</th>
                                    <th>Proprietário</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Dias restantes</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($meusAlugueres as $a):
                                    $diasRestantes = (int)((strtotime($a['alu_fim']) - strtotime('today')) / 86400);
                                    $urgente = $diasRestantes <= 2;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($a['fer_nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($a['cat_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($a['dono_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($a['alu_fim'])); ?></td>
                                    <td>
                                        <span class="dias-restantes <?php echo $urgente ? 'urgente' : ''; ?>">
                                            <?php
                                                if($diasRestantes < 0) echo 'Prazo ultrapassado';
                                                elseif($diasRestantes === 0) echo 'Termina hoje';
                                                else echo $diasRestantes . ' dia' . ($diasRestantes != 1 ? 's' : '');
                                            ?>
                                        </span>
                                    </td>
                                    <td><span class="estado-badge estado-<?php echo $a['alu_estado']; ?>"><?php echo $a['alu_estado']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

                <!-- Full rental history -->
                <section class="dashboard-section">
                    <h2 class="section-title">Histórico dos meus alugueres</h2>
                    <?php if(empty($historico)): ?>
                        <p class="empty-msg">Ainda não alugaste nenhuma ferramenta.</p>
                    <?php else: ?>
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Ferramenta</th>
                                    <th>Categoria</th>
                                    <th>Início</th>
                                    <th>Fim previsto</th>
                                    <th>Devolvido em</th>
                                    <th>Duração real</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($historico as $h): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($h['fer_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($h['cat_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($h['alu_inicio'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($h['alu_fim'])); ?></td>
                                    <td><?php echo $h['alu_devolvido'] ? date('d/m/Y H:i', strtotime($h['alu_devolvido'])) : '—'; ?></td>
                                    <td><?php echo $h['dias']; ?> dia<?php echo $h['dias'] != 1 ? 's' : ''; ?></td>
                                    <td><span class="estado-badge estado-<?php echo $h['alu_estado']; ?>"><?php echo $h['alu_estado']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

            </main>
        </div>
    </div>

    <script src="../js/script.js?v=2"></script>
</body>
</html>
