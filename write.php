<?php
// write.php（受信・集計・役割判定・CSV保存・表示）
//受け取った文字列を安全にHTMLに埋め込める形に変換する関数hを使用
//htmlspecialchars() が < や > を &lt; &gt; に変換する。
// ENT_QUOTES は ' と " も変換対象にする（より堅い）。
// 'UTF-8' は文字コード指定。日本語が崩れにくい。
//stringで$sがnullでも強制的に文字列に変換→これを入れないと

function h($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// 入力受信
$name = trim($_POST['name'] ?? '');
if ($name === '') $name = '名無し';

// 質問数（index.php と合わせる）
$QUESTION_COUNT = 5;

// 直接アクセス防止
if (!isset($_POST['q1'])) {
  echo '質問が送信されていません。index.php から回答してください。';
  exit;
}

// スコア計算
$score = ['A'=>0,'B'=>0,'C'=>0,'D'=>0];
for ($i = 1; $i <= $QUESTION_COUNT; $i++) {
  $ans = $_POST["q{$i}"] ?? '';
  if (isset($score[$ans])) {
    $score[$ans]++;
  }
}

// 最大軸（同点はランダム）
$max = max($score);
$candidates = array_keys($score, $max);
$axis = $candidates[array_rand($candidates)];

// 役割
$roles = [
  'A' => [
    'pos' => 'FW',
    'title' => '前に出るタイプ',
    'desc' => "場を動かしにいく。刺されば一気に勝つが、外すと事故る。\n『合コンなのにFWだけ揃ってる』状態を作りがち。",
  ],
  'B' => [
    'pos' => 'MF',
    'title' => '回すタイプ',
    'desc' => "会話をつなぎ、温度を上げ、全員を参加させる。\nいないと全体が止まる。MF不在は静かに負ける。",
  ],
  'C' => [
    'pos' => 'DF',
    'title' => '安定させるタイプ',
    'desc' => "地雷回避と温度調整。崩壊を防ぐが目立たない。\n『変な方向に行かない』こと自体が価値。",
  ],
  'D' => [
    'pos' => 'GK',
    'title' => '回収するタイプ',
    'desc' => "注文・席・会計・撤収・事故処理。最後に全部を回収する。\nGK不在は、会計と帰路で必ず失点する。",
  ],
];

$result = $roles[$axis];

// DB保存（MySQL）
try {
  // ▼ さくらのDB接続
  $DB_HOST = 'mysql3112.db.sakura.ne.jp'; // phpMyAdmin上部の「サーバ：」と合わせる
  $DB_NAME = 'picinc_gs_kadai';
  $DB_USER = 'picinc_gs_kadai'; // ← さくらの「データベースユーザー名」（DB名ではない）
  $DB_PASS = 'ysite1221'; // ← そのパスワード

  $pdo = new PDO(
    "mysql:dbname={$DB_NAME};charset=utf8mb4;host={$DB_HOST};port=3306",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );

  // kadai8:
  // id, name, position, axis, qs_a, qs_b, qs_c, qs_d, sysdate, is_starter
  $sql = "
    INSERT INTO kadai8
      (name, position, axis, qs_a, qs_b, qs_c, qs_d, sysdate)
    VALUES
      (:name, :pos, :axis, :a, :b, :c, :d, NOW())
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':name', $name, PDO::PARAM_STR);
  $stmt->bindValue(':pos',  $result['pos'], PDO::PARAM_STR);
  $stmt->bindValue(':axis', $axis, PDO::PARAM_STR);
  $stmt->bindValue(':a', (string)$score['A'], PDO::PARAM_STR);
  $stmt->bindValue(':b', (string)$score['B'], PDO::PARAM_STR);
  $stmt->bindValue(':c', (string)$score['C'], PDO::PARAM_STR);
  $stmt->bindValue(':d', (string)$score['D'], PDO::PARAM_STR);
  $stmt->execute();

} catch (PDOException $e) {
   // エラー特定用
  echo 'DB保存に失敗しました：' . h($e->getMessage());
  exit;
}

// 一言コメント
$oneShotNote = '';
if ($result['pos'] === 'FW') {
  $oneShotNote = '※注意：FWは強い。ただし、FWが多い宴はだいたい疲れる。';
}

if ($result['pos'] === 'MF') {
  $oneShotNote = '※戦略：一番美味しいところを享受するタイプ。FWには踊らせとけ。';
}


if ($result['pos'] === 'DF') {
  $oneShotNote = '※定説：あなたがいると場が盛り上がる（ただし、イケメンに限る。）';
}

if ($result['pos'] === 'GK') {
  $oneShotNote = '※朗報：あなたがいれば最低限「成立」する。派手さはないが、全員を救う。';
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>編成結果｜合コンのチーム編成</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container">
  <h1 class="title">編成結果</h1>
  <p class="subtitle">― 己を知る ―</p>

  <section class="result-card">
    <p class="result-text">
      <span class="result-name"><?= h($name) ?></span> は<br>
      【<span class="result-role"><?= h($result['pos']) ?></span>】<br>
      <span class="result-sub"><?= h($result['title']) ?></span>
      と判定。
    </p>

    <p class="result-desc"><?= nl2br(h($result['desc'])) ?></p>

    <?php if ($oneShotNote !== ''): ?>
      <p class="result-note"><?= h($oneShotNote) ?></p>
    <?php endif; ?>

    <details class="debug">
      <summary>（任意）スコアを見る</summary>
      <pre><?= h(print_r($score, true)) ?></pre>
    </details>

    <div class="result-actions">
      <a class="link" href="index.php">もう一度編成する →</a>
      <a class="link" href="read.php">チーム全体を見る →</a>
    </div>
  </section>
</main>
</body>
</html>