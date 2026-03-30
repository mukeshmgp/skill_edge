<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$user = currentUser();

$testId = (int)($_GET['test_id'] ?? 0);
if (!$testId) { header('Location: test.php'); exit; }

// Fetch test
$t = $conn->query("SELECT * FROM tests WHERE id=$testId AND user_id={$user['id']}")->fetch_assoc();
if (!$t) { header('Location: test.php'); exit; }

// Fetch per-question results with question details
$res = $conn->query("
  SELECT r.*, q.question, q.option_a, q.option_b, q.option_c, q.option_d,
         q.correct_option, q.explanation, q.subject
  FROM results r
  JOIN questions q ON r.question_id = q.id
  WHERE r.test_id = $testId
  ORDER BY r.id
")->fetch_all(MYSQLI_ASSOC);

$pct     = (float)$t['score_pct'];
$grade   = $pct >= 80 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 40 ? 'Average' : 'Needs Work'));
$gradeC  = $pct >= 80 ? '#2e7d32'   : ($pct >= 60 ? '#F9A825' : ($pct >= 40 ? '#E65100' : '#C62828'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Test Result – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
.result-hero{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon),var(--maroon-mid));
  padding:48px 0;text-align:center;position:relative;overflow:hidden;}
.result-hero::before{content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(-45deg,transparent,transparent 20px,rgba(244,196,48,.05) 20px,rgba(244,196,48,.05) 22px);}
.result-grade{font-family:var(--font-display);font-size:2.5rem;font-weight:900;color:#FFF;
  position:relative;z-index:1;animation:gradeIn .8s cubic-bezier(0.34,1.56,0.64,1) .3s both;}
@keyframes gradeIn{from{transform:scale(0.5);opacity:0}to{transform:scale(1);opacity:1}}
.result-grade .g{color:var(--gold-light);}
.result-sub{font-family:var(--font-serif);color:rgba(255,255,255,.7);font-size:1.05rem;
  margin-top:6px;position:relative;z-index:1;}

/* Score cards */
.score-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:32px 0;}
.score-card{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);
  box-shadow:var(--shadow);padding:20px;text-align:center;
  animation:scoreIn .6s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes scoreIn{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.score-card:nth-child(1){animation-delay:.1s}.score-card:nth-child(2){animation-delay:.2s}
.score-card:nth-child(3){animation-delay:.3s}.score-card:nth-child(4){animation-delay:.4s}
.score-num{font-family:var(--font-display);font-size:2rem;font-weight:900;line-height:1;}
.score-label{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);margin-top:4px;}
.score-icon{font-size:1.4rem;margin-bottom:6px;}

/* Big circle */
.big-circle-wrap{display:flex;align-items:center;justify-content:center;
  width:160px;height:160px;margin:0 auto;}
.big-circle-wrap .pct{font-size:2rem;}
.big-circle-wrap .sub{font-size:.7rem;}

/* Review section */
.review-item{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);
  box-shadow:var(--shadow);padding:20px;margin-bottom:14px;}
.review-item.correct{border-left:4px solid #2e7d32;}
.review-item.wrong{border-left:4px solid #C62828;}
.review-item.skipped{border-left:4px solid #E65100;}
.review-q{font-family:var(--font-serif);font-size:1rem;line-height:1.6;margin-bottom:12px;}
.review-opts{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;}
.review-opt{padding:8px 14px;border-radius:8px;font-size:.82rem;font-weight:700;
  display:flex;align-items:center;gap:8px;background:rgba(128,0,0,.04);border:1.5px solid rgba(128,0,0,.1);}
.review-opt.is-correct{background:rgba(0,180,80,.1);border-color:rgba(0,180,80,.3);color:#0a5c2a;}
.review-opt.is-selected.wrong{background:rgba(200,0,0,.1);border-color:rgba(200,0,0,.25);color:#8B0000;}
.explanation{margin-top:12px;padding:12px;background:rgba(244,196,48,.1);border-radius:8px;border-left:3px solid var(--gold);
  font-family:var(--font-serif);font-size:.9rem;color:var(--text-dark);}

/* Tabs */
.tabs{display:flex;gap:4px;margin:24px 0 16px;border-bottom:2px solid var(--border);}
.tab{padding:10px 18px;font-family:var(--font-body);font-size:.82rem;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);
  background:none;border:none;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;
  transition:var(--transition);}
.tab:hover{color:var(--maroon);}
.tab.active{color:var(--maroon);border-bottom-color:var(--maroon);}
.tab-pane{display:none;} .tab-pane.active{display:block;}

@media(max-width:640px){
  .score-grid{grid-template-columns:repeat(2,1fr);}
  .review-opts{grid-template-columns:1fr;}
}
</style>
</head>
<body <?= $user['dark'] ? 'class="dark-mode"' : '' ?>>

<nav class="navbar">
  <a href="index.php" class="navbar-brand"><div class="brand-icon">🏛️</div><span>Skill</span>Edge</a>
  <div class="nav-links" id="navLinks">
    <a href="index.php" class="nav-link">Home</a>
    <a href="test.php" class="nav-link">Mock Tests</a>
    <a href="profile.php" class="nav-link">Profile</a>
    <a href="logout.php" class="nav-btn">Logout</a>
    <button class="dark-toggle" onclick="toggleDarkMode()"><i id="darkIcon" class="fas fa-moon"></i></button>
  </div>
</nav>
<div class="gold-line"></div>

<!-- Result Hero -->
<div class="result-hero">
  <div class="container" style="position:relative;z-index:1">
    <!-- Big circle -->
    <div class="big-circle-wrap" data-pct="<?= $pct ?>">
      <svg width="160" height="160" viewBox="0 0 100 100">
        <defs>
          <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#D4A820"/>
            <stop offset="100%" stop-color="#FFE080"/>
          </linearGradient>
        </defs>
        <circle class="track" cx="50" cy="50" r="40"/>
        <circle class="fill" cx="50" cy="50" r="40"/>
      </svg>
      <div class="label">
        <div class="pct" style="color:var(--gold-light)"><?= round($pct) ?>%</div>
        <div class="sub" style="color:rgba(255,255,255,.6)">Score</div>
      </div>
    </div>
    <div class="result-grade">You did <span class="g"><?= $grade ?></span>!</div>
    <div class="result-sub"><?= h($t['exam_group']) ?> – <?= h($t['subject']) ?></div>
  </div>
</div>
<div class="gold-line"></div>

<div class="main-content">
<div class="container">

<!-- Score Summary -->
<div class="score-grid">
  <div class="score-card">
    <div class="score-icon" style="color:var(--maroon)">📝</div>
    <div class="score-num" style="color:var(--maroon)"><?= $t['total_q'] ?></div>
    <div class="score-label">Total Questions</div>
  </div>
  <div class="score-card">
    <div class="score-icon" style="color:#2e7d32">✅</div>
    <div class="score-num" style="color:#2e7d32"><?= $t['correct'] ?></div>
    <div class="score-label">Correct</div>
  </div>
  <div class="score-card">
    <div class="score-icon" style="color:#C62828">❌</div>
    <div class="score-num" style="color:#C62828"><?= $t['wrong'] ?></div>
    <div class="score-label">Wrong</div>
  </div>
  <div class="score-card">
    <div class="score-icon" style="color:#E65100">⏭️</div>
    <div class="score-num" style="color:#E65100"><?= $t['skipped'] ?></div>
    <div class="score-label">Skipped</div>
  </div>
</div>

<!-- Score bar -->
<div class="card" style="padding:24px;margin-bottom:24px" data-anim>
  <div style="display:flex;justify-content:space-between;margin-bottom:10px">
    <span style="font-family:var(--font-display);font-weight:700;color:var(--maroon)">Overall Score</span>
    <span class="badge" style="background:<?= $gradeC ?>20;color:<?= $gradeC ?>;border-color:<?= $gradeC ?>50"><?= $grade ?></span>
  </div>
  <div class="progress-wrap" style="height:14px">
    <div class="progress-bar" data-width="<?= $pct ?>"></div>
  </div>
  <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:.78rem;font-weight:600;color:var(--text-muted)">
    <span>Time: <?= gmdate('i:s',$t['time_taken']) ?> mins</span>
    <span><?= round($pct,1) ?>% (<?= $t['correct'] ?>/<?= $t['total_q'] ?>)</span>
  </div>
</div>

<!-- Action buttons -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px" data-anim>
  <a href="test.php?group=<?= h($t['exam_group']) ?>&subject=<?= urlencode($t['subject']) ?>" class="btn btn-primary ripple-btn">
    <i class="fas fa-redo"></i> Retry Same Topic
  </a>
  <a href="test.php" class="btn btn-gold ripple-btn">
    <i class="fas fa-play-circle"></i> New Test
  </a>
  <a href="profile.php" class="btn btn-outline">
    <i class="fas fa-chart-bar"></i> View Analytics
  </a>
</div>

<!-- Tabs: Review / Tips -->
<div class="tabs">
  <button class="tab active" onclick="switchTab('review',this)">📋 Question Review</button>
  <button class="tab" onclick="switchTab('tips',this)">💡 Study Tips</button>
</div>

<div id="tab-review" class="tab-pane active">
  <?php foreach($res as $ri => $r):
    $isCorrect  = $r['is_correct'];
    $isSkipped  = $r['selected_option'] === 'X';
    $cls = $isSkipped ? 'skipped' : ($isCorrect ? 'correct' : 'wrong');
    $opts = ['A'=>$r['option_a'],'B'=>$r['option_b'],'C'=>$r['option_c'],'D'=>$r['option_d']];
  ?>
  <div class="review-item <?= $cls ?>" data-anim data-delay="<?= $ri*50 ?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <span style="font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted)">Q<?= $ri+1 ?> · <?= h($r['subject']) ?></span>
      <span class="badge <?= $isSkipped?'badge-maroon':($isCorrect?'badge-green':'badge-red') ?>">
        <?= $isSkipped ? 'Skipped' : ($isCorrect ? '✓ Correct' : '✗ Wrong') ?>
      </span>
    </div>
    <div class="review-q"><?= nl2br(h($r['question'])) ?></div>
    <div class="review-opts">
      <?php foreach($opts as $k=>$v): ?>
      <div class="review-opt <?= ($k===$r['correct_option'])?'is-correct':'' ?> <?= ($k===$r['selected_option']&&!$isCorrect)?'is-selected wrong':'' ?>">
        <strong><?= $k ?>.</strong> <?= h($v) ?>
        <?php if($k===$r['correct_option']): ?><i class="fas fa-check" style="margin-left:auto;color:#2e7d32"></i><?php endif; ?>
        <?php if($k===$r['selected_option']&&$r['selected_option']!==$r['correct_option']&&!$isSkipped): ?><i class="fas fa-times" style="margin-left:auto;color:#C62828"></i><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if($r['explanation']): ?>
    <div class="explanation"><i class="fas fa-lightbulb" style="color:var(--gold-dark);margin-right:6px"></i><?= h($r['explanation']) ?></div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<div id="tab-tips" class="tab-pane">
  <?php
  $tips = [];
  if ($pct < 40) $tips[] = ['fas fa-exclamation-triangle','#C62828','Urgent Focus Needed',"Your score of {$pct}% needs significant improvement. Review basic concepts in {$t['subject']}."];
  if ($pct >= 40 && $pct < 60) $tips[] = ['fas fa-book-open','#E65100','Keep Practicing',"You're making progress! Practice more PYQ questions in {$t['subject']}."];
  if ($pct >= 60) $tips[] = ['fas fa-trophy','#2e7d32','Great Performance',"Excellent work! You scored {$pct}% — aim for 80%+ next time."];
  $tips[] = ['fas fa-clock','#7B1FA2','Improve Speed','Practice timed tests to improve your answer speed. Aim to finish 2 mins before the deadline.'];
  $tips[] = ['fas fa-repeat','var(--maroon)','Spaced Repetition','Re-attempt this test in 3 days to reinforce your memory of the concepts.'];
  $tips[] = ['fas fa-file-circle-question',var_export($pct<60,true)?'#E65100':'#2e7d32','PYQ Practice','Solve previous year questions for '.h($t['subject']).' to understand the exam pattern.'];
  foreach($tips as $tip):
  ?>
  <div class="card" style="padding:20px;margin-bottom:12px;display:flex;gap:14px;align-items:flex-start" data-anim>
    <div style="width:44px;height:44px;border-radius:12px;background:<?= $tip[1] ?>20;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="<?= $tip[0] ?>" style="color:<?= $tip[1] ?>;font-size:1.1rem"></i>
    </div>
    <div>
      <div style="font-family:var(--font-display);font-weight:700;color:var(--maroon);margin-bottom:4px"><?= $tip[2] ?></div>
      <div style="font-family:var(--font-serif);font-size:.92rem;color:var(--text-muted)"><?= $tip[3] ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

</div>
</div>

<script src="assets/js/main.js"></script>
<script>
function switchTab(id, btn) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('tab-'+id).classList.add('active');
}
</script>
</body>
</html>
