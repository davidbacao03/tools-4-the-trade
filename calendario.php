<?php
    session_start();
    if(!isset($_SESSION['utl_id'])) header('Location: login.php');

    $bd  = new PDO("mysql:host=localhost;dbname=tools4thetrade", "root", "");
    $uid = $_SESSION['utl_id'];

    // Month navigation
    $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
    $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
    if($mes < 1)  { $mes = 12; $ano--; }
    if($mes > 12) { $mes = 1;  $ano++; }
    $tab = ($_GET['tab'] ?? 'minhas') === 'aluguei' ? 'aluguei' : 'minhas';

    $mesStr    = sprintf('%04d-%02d-01', $ano, $mes);
    $mesFimStr = date('Y-m-t', strtotime($mesStr));

    // My tools being rented this month
    $q1 = $bd->prepare(
        "SELECT a.alu_id, a.alu_inicio, a.alu_fim, a.alu_estado,
                f.fer_id, f.fer_nome, u.utl_nome AS pessoa
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN utilizador u ON a.alu_utl_id = u.utl_id
         WHERE f.fer_utl_id = ?
           AND a.alu_estado IN ('Reservado','Alugado')
           AND a.alu_inicio <= ? AND a.alu_fim >= ?
         ORDER BY f.fer_nome, a.alu_inicio"
    );
    $q1->execute([$uid, $mesFimStr, $mesStr]);
    $alugsMinhas = $q1->fetchAll(PDO::FETCH_ASSOC);

    // Tools I am renting this month
    $q2 = $bd->prepare(
        "SELECT a.alu_id, a.alu_inicio, a.alu_fim, a.alu_estado,
                f.fer_id, f.fer_nome, u.utl_nome AS pessoa
         FROM aluguer a
         JOIN ferramenta f ON a.alu_fer_id = f.fer_id
         JOIN utilizador u ON f.fer_utl_id = u.utl_id
         WHERE a.alu_utl_id = ?
           AND a.alu_estado IN ('Reservado','Alugado')
           AND a.alu_inicio <= ? AND a.alu_fim >= ?
         ORDER BY f.fer_nome, a.alu_inicio"
    );
    $q2->execute([$uid, $mesFimStr, $mesStr]);
    $alugsAluguei = $q2->fetchAll(PDO::FETCH_ASSOC);

    $palette = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f',
                '#edc948','#b07aa1','#ff9da7','#9c755f','#bab0ac'];

    function buildColorMap(array $alugueres, array $palette): array {
        $map = []; $i = 0;
        foreach($alugueres as $a) {
            if(!isset($map[$a['fer_id']])) {
                $map[$a['fer_id']] = $palette[$i++ % count($palette)];
            }
        }
        return $map;
    }

    function renderCalendario(int $ano, int $mes, array $alugueres, array $colorMap): string {
        $firstTs     = mktime(0,0,0,$mes,1,$ano);
        $dow         = (int)date('N', $firstTs) - 1; // 0=Mon … 6=Sun
        $daysInMonth = (int)date('t', $firstTs);
        $calStart    = strtotime("-{$dow} days", $firstTs);
        $weeks       = (int)ceil(($dow + $daysInMonth) / 7);
        $today       = date('Y-m-d');
        $html        = '';

        // Day-of-week headers
        $html .= '<div class="cal-head">';
        foreach(['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'] as $lbl)
            $html .= "<div>{$lbl}</div>";
        $html .= '</div>';

        for($w = 0; $w < $weeks; $w++) {
            $wStartTs = strtotime("+".($w * 7)." days", $calStart);
            $wEndTs   = strtotime("+6 days", $wStartTs);

            $html .= '<div class="cal-week">';

            // Day numbers row
            $html .= '<div class="cal-days">';
            for($d = 0; $d < 7; $d++) {
                $dayTs  = strtotime("+{$d} days", $wStartTs);
                $dayNum = (int)date('j', $dayTs);
                $dayMon = (int)date('n', $dayTs);
                $dayStr = date('Y-m-d', $dayTs);
                $inMonth = ($dayMon == $mes);
                $cls = 'cal-day' . (!$inMonth ? ' out-month' : '') . ($dayStr === $today ? ' today' : '');
                $html .= "<div class=\"{$cls}\">" . ($inMonth ? $dayNum : '') . "</div>";
            }
            $html .= '</div>';

            // Strips grid
            $html .= '<div class="cal-strips">';
            foreach($alugueres as $a) {
                $iniTs = strtotime($a['alu_inicio']);
                $fimTs = strtotime($a['alu_fim']);
                if($fimTs < $wStartTs || $iniTs > $wEndTs) continue;

                $sIni = max($iniTs, $wStartTs);
                $sFim = min($fimTs, $wEndTs);
                $colS = (int)(($sIni - $wStartTs) / 86400) + 1;
                $colE = (int)(($sFim - $wStartTs) / 86400) + 1;
                $cor  = $colorMap[$a['fer_id']] ?? '#999';
                $nome = htmlspecialchars($a['fer_nome']);
                $pessoa = htmlspecialchars($a['pessoa']);
                $estado = htmlspecialchars($a['alu_estado']);

                $html .= "<div class=\"cal-strip\" "
                       . "style=\"grid-column:{$colS}/".($colE+1).";background:{$cor};\" "
                       . "title=\"{$nome} — {$pessoa} ({$estado})\">"
                       . "<span>{$nome}</span>"
                       . "</div>";
            }
            $html .= '</div>'; // .cal-strips

            $html .= '</div>'; // .cal-week
        }
        return $html;
    }

    $colorMapMinhas  = buildColorMap($alugsMinhas,  $palette);
    $colorMapAluguei = buildColorMap($alugsAluguei, $palette);

    $prevMes = $mes - 1; $prevAno = $ano;
    if($prevMes < 1)  { $prevMes = 12; $prevAno--; }
    $nextMes = $mes + 1; $nextAno = $ano;
    if($nextMes > 12) { $nextMes = 1;  $nextAno++; }

    $mesesPT = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário - Tools 4 The Trade</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
            margin-bottom: 20px;
        }
        .tab-link {
            padding: 10px 22px;
            text-decoration: none;
            color: #777;
            font-size: 0.95rem;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .tab-link.active {
            color: #222;
            font-weight: bold;
            border-bottom-color: #333;
        }

        /* Month navigation */
        .cal-nav {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }
        .cal-nav a {
            text-decoration: none;
            color: #333;
            font-size: 1.2rem;
            padding: 4px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cal-nav a:hover { background: #f0f0f0; }
        .cal-nav h2 { margin: 0; font-size: 1.15rem; min-width: 180px; text-align: center; }

        /* Calendar grid */
        .cal-head {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8f8f8;
            border: 1px solid #e2e2e2;
            border-bottom: 2px solid #ddd;
        }
        .cal-head div {
            text-align: center;
            padding: 8px 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .cal-week {
            border-left: 1px solid #e2e2e2;
            border-right: 1px solid #e2e2e2;
            border-bottom: 1px solid #e2e2e2;
        }

        .cal-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .cal-day {
            text-align: right;
            padding: 5px 8px;
            font-size: 0.82rem;
            color: #333;
            border-right: 1px solid #f0f0f0;
            min-height: 26px;
        }
        .cal-day:last-child { border-right: none; }
        .cal-day.out-month { color: #ccc; }
        .cal-day.today {
            font-weight: bold;
            color: #fff;
            background: #333;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            line-height: 22px;
            padding: 0;
            text-align: center;
            margin: 3px 6px 3px auto;
        }

        /* Strips */
        .cal-strips {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            row-gap: 2px;
            padding: 2px 2px 6px;
            min-height: 10px;
        }
        .cal-strip {
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 0.72rem;
            color: #fff;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            cursor: default;
            margin: 0 1px;
        }

        /* Legend */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #eee;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.85rem;
        }
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }
        .legend-sub {
            color: #999;
            font-size: 0.78rem;
        }

        .empty-cal {
            color: #999;
            font-size: 0.9rem;
            padding: 20px 0 8px;
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
                <section class="dashboard-section">
                    <h1 style="margin-top:0; margin-bottom:16px;">Calendário</h1>

                    <!-- Tabs -->
                    <div class="tabs">
                        <a href="?tab=minhas&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>"
                           class="tab-link <?php echo $tab === 'minhas' ? 'active' : ''; ?>">
                            As minhas ferramentas
                        </a>
                        <a href="?tab=aluguei&mes=<?php echo $mes; ?>&ano=<?php echo $ano; ?>"
                           class="tab-link <?php echo $tab === 'aluguei' ? 'active' : ''; ?>">
                            Ferramentas que aluguei
                        </a>
                    </div>

                    <!-- Month navigation -->
                    <div class="cal-nav">
                        <a href="?tab=<?php echo $tab; ?>&mes=<?php echo $prevMes; ?>&ano=<?php echo $prevAno; ?>">&#8249;</a>
                        <h2><?php echo $mesesPT[$mes] . ' ' . $ano; ?></h2>
                        <a href="?tab=<?php echo $tab; ?>&mes=<?php echo $nextMes; ?>&ano=<?php echo $nextAno; ?>">&#8250;</a>
                    </div>

                    <?php if($tab === 'minhas'): ?>

                        <?php echo renderCalendario($ano, $mes, $alugsMinhas, $colorMapMinhas); ?>
                        <?php if(empty($alugsMinhas)): ?>
                            <p class="empty-cal">Nenhuma das tuas ferramentas está alugada neste mês.</p>
                        <?php else: ?>
                            <div class="legend">
                                <?php
                                $seen = [];
                                foreach($alugsMinhas as $a):
                                    if(isset($seen[$a['fer_id']])) continue;
                                    $seen[$a['fer_id']] = true;
                                    $cor = $colorMapMinhas[$a['fer_id']];
                                ?>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background:<?php echo $cor; ?>"></div>
                                    <span><?php echo htmlspecialchars($a['fer_nome']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>

                        <?php echo renderCalendario($ano, $mes, $alugsAluguei, $colorMapAluguei); ?>
                        <?php if(empty($alugsAluguei)): ?>
                            <p class="empty-cal">Não tens ferramentas alugadas neste mês.</p>
                        <?php else: ?>
                            <div class="legend">
                                <?php
                                $seen = [];
                                foreach($alugsAluguei as $a):
                                    if(isset($seen[$a['fer_id']])) continue;
                                    $seen[$a['fer_id']] = true;
                                    $cor = $colorMapAluguei[$a['fer_id']];
                                ?>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background:<?php echo $cor; ?>"></div>
                                    <span><?php echo htmlspecialchars($a['fer_nome']); ?></span>
                                    <span class="legend-sub">— <?php echo htmlspecialchars($a['pessoa']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </section>
            </main>
        </div>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>
