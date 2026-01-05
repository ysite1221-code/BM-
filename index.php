<?php
// index.php（入力フォーム）

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$questions = [
  1 => [
    '開始5分、あなたの立ち上がり',
    [
      ['A', '自己紹介を盛って空気を一気に作る'],
      ['B', '全員に軽く振って会話を回し始める'],
      ['C', '地雷を踏まないよう様子見して整える'],
      ['D', '席・注文・乾杯の段取りを整える'],
    ],
  ],
  2 => [
    '会話が止まりかけた瞬間',
    [
      ['A', '話題を投げ込んで前に出る'],
      ['B', '誰かの話を拾って別の人につなぐ'],
      ['C', '危ない話題はさりげなく逸らす'],
      ['D', 'ドリンク追加や取り分けで間を埋める'],
    ],
  ],
  3 => [
    '相手側に静かな人がいる',
    [
      ['A', 'まず自分が盛って輪に引き込む'],
      ['B', '質問と相づちで参加しやすくする'],
      ['C', '無理に引っ張らず居心地を守る'],
      ['D', '席替えや距離感を調整する'],
    ],
  ],
  4 => [
    '一人が暴走し始めた',
    [
      ['A', '別の話題で上書きして進める'],
      ['B', '周囲と連携して自然に話題を移す'],
      ['C', 'ラインを引いて被害を抑える'],
      ['D', '水・トイレ・店員対応で切り替える'],
    ],
  ],
  5 => [
    '終盤、次につなげるなら',
    [
      ['A', '二次会提案で流れを作る'],
      ['B', '反応を見て最適な動線を組む'],
      ['C', '温度感が低ければ撤退判断'],
      ['D', '会計・連絡先・解散を回収する'],
    ],
  ],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>チーム編成診断</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <main class="container">
    <header class="page-header">
      <h1 class="title">共通の友人を介した合同のお食事会フォーメーション調査</h1>
      <p class="subtitle">スタメン選び</p>
    </header>

    <form class="form" action="write.php" method="post">
      <section class="card">
        <div class="field">
          <label class="label" for="name">呼び名（任意）</label>
          <input class="input" id="name" type="text" name="name" placeholder="名無しでもOK" />
        </div>
      </section>

      <section class="questions">
        <?php foreach ($questions as $id => $q): ?>
          <div class="qcard">
            <div class="qtitle">Q<?= (int)$id ?>. <?= h($q[0]) ?></div>

            <div class="qoptions">
              <?php foreach ($q[1] as $i => $opt): ?>
                <label class="qopt">
                  <input class="qradio" type="radio" name="q<?= (int)$id ?>" value="<?= h($opt[0]) ?>" <?= $i === 0 ? 'required' : '' ?> />
                  <span class="qtext"><?= h($opt[1]) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </section>

      <button class="submit-btn" type="submit">チーム編成する</button>

      <div class="result-actions" style="margin-top:12px;">
        <a class="link" href="read.php">チーム全体を見る →</a>
      </div>
    </form>
  </main>
</body>
</html>