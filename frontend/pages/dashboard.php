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

    // Rating submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'avaliar') {
        $aluId    = (int)($_POST['alu_id'] ?? 0);
        $notaFer  = round((float)($_POST['nota_fer']  ?? 0) * 2) / 2;
        $notaDono = round((float)($_POST['nota_dono'] ?? 0) * 2) / 2;
        $texto    = trim($_POST['texto'] ?? '');
        if ($aluId > 0 && $notaFer >= 0.5 && $notaFer <= 5.0 && $notaDono >= 0.5 && $notaDono <= 5.0) {
            $check = $bd->prepare(
                "SELECT a.alu_fer_id, f.fer_utl_id FROM aluguer a
                 JOIN ferramenta f ON a.alu_fer_id = f.fer_id
                 WHERE a.alu_id = ? AND a.alu_utl_id = ? AND a.alu_estado = 'Devolvido'"
            );
            $check->execute([$aluId, $uid]);
            $rental = $check->fetch(PDO::FETCH_ASSOC);
            if ($rental) {
                $bd->prepare(
                    "INSERT IGNORE INTO avaliacao (ava_alu_id, ava_fer_id, ava_utl_id, ava_dono_id, ava_nota_fer, ava_nota_dono, ava_texto)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                )->execute([$aluId, $rental['alu_fer_id'], $uid, $rental['fer_utl_id'], $notaFer, $notaDono, $texto ?: null]);
            }
        }
        header('Location: dashboard.php');
        exit;
    }

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
        "SELECT a.alu_id, a.alu_fer_id, a.alu_inicio, a.alu_fim, a.alu_devolvido, a.alu_estado,
                f.fer_nome, c.cat_nome,
                DATEDIFF(COALESCE(DATE(a.alu_devolvido), a.alu_fim), a.alu_inicio) AS dias,
                av.ava_id, av.ava_nota_fer, av.ava_nota_dono
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN categoria c ON f.fer_cat_id = c.cat_id
         LEFT JOIN avaliacao av ON av.ava_alu_id = a.alu_id
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
                                    <th>Avaliação</th>
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
                                    <td>
                                        <?php if($h['alu_estado'] === 'Devolvido'): ?>
                                            <?php if($h['ava_id']): ?>
                                                <div style="font-size:0.75rem;color:#888;margin-bottom:2px;">Ferramenta</div>
                                                <div class="stars-display" data-nota="<?php echo $h['ava_nota_fer']; ?>"></div>
                                                <div style="font-size:0.75rem;color:#888;margin:4px 0 2px;">Proprietário</div>
                                                <div class="stars-display" data-nota="<?php echo $h['ava_nota_dono']; ?>"></div>
                                            <?php else: ?>
                                                <button class="simple-button btn-avaliar" data-alu-id="<?php echo $h['alu_id']; ?>" style="font-size:0.8rem;padding:5px 12px;">Avaliar</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:#aaa;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>

            </main>
        </div>
    </div>

    <div class="modal-overlay" id="ratingModalOverlay">
        <div class="modal-box">
            <button class="modal-close" id="ratingModalClose">&times;</button>
            <h2>Avaliar experiência</h2>
            <form method="post" id="ratingForm">
                <input type="hidden" name="action" value="avaliar">
                <input type="hidden" name="alu_id" id="ratingAluId" value="">

                <div class="modal-field">
                    <span class="modal-label">Ferramenta</span>
                    <div class="star-picker">
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <input type="hidden" name="nota_fer" value="">
                    </div>
                </div>

                <div class="modal-field">
                    <span class="modal-label">Proprietário</span>
                    <div class="star-picker">
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <span class="sp-star">★</span>
                        <input type="hidden" name="nota_dono" value="">
                    </div>
                </div>

                <div class="modal-field" style="flex-direction:column;align-items:flex-start;gap:6px;">
                    <span class="modal-label">Review da ferramenta <span style="font-weight:normal;color:#999;">(opcional)</span></span>
                    <textarea name="texto" rows="3" placeholder="Descreve a tua experiência com a ferramenta..." style="width:100%;border:1px solid #ddd;border-radius:4px;padding:8px;font-size:0.9rem;resize:vertical;box-sizing:border-box;font-family:inherit;"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="simple-button">Enviar avaliação</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/script.js?v=3"></script>
</body>
</html>
