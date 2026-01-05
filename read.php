<?php
// read.php（DB版：一覧・スタメン管理・削除・ピッチ表示）

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// DB接続
try {
  // ▼ さくらのDB接続（
  $DB_HOST = 'XXXXX';
  $DB_NAME = 'XXXXX';
  $DB_USER = 'XXXXX'; // ← さくらの「データベースユーザー名」
  $DB_PASS = 'XXXXX'; // ← そのパスワード

  $pdo = new PDO(
    "mysql:dbname={$DB_NAME};charset=utf8mb4;host={$DB_HOST};port=3306",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
   //エラー特定用
  echo 'DB接続に失敗しました：' . h($e->getMessage());
  exit;
}

// アクション（toggle / delete）
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($action !== '' && $id > 0) {
  if ($action === 'delete') {
    $stmt = $pdo->prepare('DELETE FROM kadai8 WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    header('Location: read.php');
    exit;
  }

  if ($action === 'toggle') {
    // 現在状態
    $stmt = $pdo->prepare('SELECT is_starter FROM kadai8 WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row) {
      $now = (int)$row['is_starter'];

      if ($now === 1) {
        // スタメン → 控え
        $stmt = $pdo->prepare('UPDATE kadai8 SET is_starter = 0 WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        header('Location: read.php');
        exit;
      }

      // 控え → スタメン（4名制限）
      $cnt = (int)$pdo->query('SELECT COUNT(*) FROM kadai8 WHERE is_starter = 1')->fetchColumn();
      if ($cnt >= 4) {
        header('Location: read.php?err=limit');
        exit;
      }

      $stmt = $pdo->prepare('UPDATE kadai8 SET is_starter = 1 WHERE id = :id');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      header('Location: read.php');
      exit;
    }

    header('Location: read.php');
    exit;
  }

  header('Location: read.php');
  exit;
}

// データ取得（スタメン優先→新しい順） 
$rows = $pdo->query('SELECT * FROM kadai8 ORDER BY is_starter DESC, sysdate DESC, id DESC')->fetchAll();

//  集計：ピッチは「スタメンのみ」 
$counts  = ['FW'=>0,'MF'=>0,'DF'=>0,'GK'=>0];
$players = ['FW'=>[],'MF'=>[],'DF'=>[],'GK'=>[]];
$members = []; // ログ表示（新しい順）

foreach ($rows as $r) {
  $name = $r['name'] ?? '名無し';
  $pos  = $r['position'] ?? '';
  $ts   = $r['sysdate'] ?? '';
  $rid  = (int)($r['id'] ?? 0);
  $starter = (int)($r['is_starter'] ?? 0);

  $members[] = ['ts'=>$ts,'name'=>$name,'pos'=>$pos,'id'=>$rid,'is_starter'=>$starter];

  if ($starter === 1 && isset($counts[$pos])) {
    $counts[$pos]++;
    $players[$pos][] = $name;
  }
}

$total = $counts['FW'] + $counts['MF'] + $counts['DF'] + $counts['GK'];

// 一言コメント（スタメンのバランスで判定）
$comment = 'スタメン未設定。まず4人選べ。';
if ($total > 0) {
  $comment = 'バランス良好。普通に回る。';
  if ($counts['GK'] === 0) $comment = 'GK不在。会計と撤収で必ず失点する。';
  elseif ($counts['MF'] === 0) $comment = 'MF不在。会話がつながらず静かに終わる。';
  elseif ($counts['FW'] >= $counts['MF'] * 2) $comment = 'FW多すぎ。全員が前に出て渋滞。';
  elseif ($counts['DF'] === 0) $comment = 'DF不在。地雷を踏んだ瞬間に全滅。';
  elseif ($counts['FW'] === 0) $comment = 'FW不在。いい人たちだが、何も起きない。';
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>チーム編成</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <main class="container">
    <header class="page-header">
      <h1 class="title">チーム編成</h1>
      <p class="subtitle">― 我が軍の陣容 ―</p>
    </header>

    <section class="board">
      <?php if (($_GET['err'] ?? '') === 'limit'): ?>
        <div class="comment" style="margin-bottom:12px;">スタメンは4名まで。誰か控えにしてから入れろ。</div>
      <?php endif; ?>

      <div class="grid">
        <div class="slot"><div class="k">FW</div><div class="v"><?= h($counts['FW']) ?></div></div>
        <div class="slot"><div class="k">MF</div><div class="v"><?= h($counts['MF']) ?></div></div>
        <div class="slot"><div class="k">DF</div><div class="v"><?= h($counts['DF']) ?></div></div>
        <div class="slot"><div class="k">GK</div><div class="v"><?= h($counts['GK']) ?></div></div>
      </div>

      <div class="comment"><?= h($comment) ?></div>

      <div class="pitch">
        <div class="line fw"><?php foreach ($players['FW'] as $n): ?><div class="player fw"><?= h($n) ?></div><?php endforeach; ?></div>
        <div class="line mf"><?php foreach ($players['MF'] as $n): ?><div class="player mf"><?= h($n) ?></div><?php endforeach; ?></div>
        <div class="line df"><?php foreach ($players['DF'] as $n): ?><div class="player df"><?= h($n) ?></div><?php endforeach; ?></div>
        <div class="line gk"><?php foreach ($players['GK'] as $n): ?><div class="player gk"><?= h($n) ?></div><?php endforeach; ?></div>
      </div>

      <div class="result-actions" style="margin-top:12px;">
        <a class="link" href="index.php">編成を追加する →</a>
      </div>

      <div class="list">
        <div class="label" style="margin-bottom:8px;">メンバー一覧（★がスタメン / 上が新しい）</div>

        <?php if (count($members) === 0): ?>
          <div class="footnote">まだデータがない。</div>
        <?php else: ?>
          <?php foreach (array_slice($members, 0, 30) as $m): ?>
            <div class="row" style="align-items:center;">
              <div>
                <?= $m['is_starter'] ? '★ ' : '' ?><?= h($m['name']) ?>
                <span class="pos" style="margin-left:10px;"><?= h($m['pos']) ?></span>
              </div>
              <div style="display:flex; gap:10px;">
                <a class="link" href="read.php?action=toggle&id=<?= (int)$m['id'] ?>">
                  <?= $m['is_starter'] ? '控えにする' : 'スタメンにする' ?>
                </a>
                <a class="link" href="read.php?action=delete&id=<?= (int)$m['id'] ?>" onclick="return confirm('削除する？');">
                  削除
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>
</body>
</html>