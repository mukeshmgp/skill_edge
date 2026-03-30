<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$user = currentUser();
$uid  = $user['id'];

// ── Stats ──
$stats = $conn->query("
  SELECT COUNT(*) total_tests,
         SUM(total_q) total_q,
         SUM(correct) total_correct,
         SUM(wrong)   total_wrong,
         MAX(taken_at) last_test
  FROM tests WHERE user_id=$uid
")->fetch_assoc();

$totalTests   = (int)($stats['total_tests'] ?? 0);
$totalQ       = (int)($stats['total_q']     ?? 0);
$totalCorrect = (int)($stats['total_correct']?? 0);
$accuracy     = $totalQ > 0 ? round($totalCorrect/$totalQ*100,1) : 0;
$lastTest     = $stats['last_test'] ? date('d M Y', strtotime($stats['last_test'])) : 'N/A';

// ── Subject performance ──
$subjPerf = $conn->query("
  SELECT subject,
         COUNT(*) cnt,
         AVG(score_pct) avg_score,
         SUM(correct) tc,
         SUM(total_q) tq
  FROM tests WHERE user_id=$uid
  GROUP BY subject
  ORDER BY avg_score DESC
")->fetch_all(MYSQLI_ASSOC);

$strongSubj = !empty($subjPerf) ? $subjPerf[0]['subject'] : 'N/A';
$weakSubj   = !empty($subjPerf) ? end($subjPerf)['subject'] : 'N/A';
$mostPracticed = !empty($subjPerf) ? array_reduce($subjPerf, function($carry,$item) {
  return (!$carry || $item['cnt'] > $carry['cnt']) ? $item : $carry;
}, null)['subject'] ?? 'N/A' : 'N/A';

// ── Weekly activity (last 7 days) ──
$weekly = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $r = $conn->query("SELECT COUNT(*) c FROM tests WHERE user_id=$uid AND DATE(taken_at)='$d'")->fetch_assoc();
    $weekly[] = ['day'=>date('D',strtotime($d)), 'count'=>(int)$r['c']];
}

// ── Test history ──
$history = $conn->query("
  SELECT *,DATE_FORMAT(taken_at,'%d %b %Y %H:%i') dt
  FROM tests WHERE user_id=$uid
  ORDER BY taken_at DESC LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

// ── AI Tips based on data ──
$tips = [];
if ($accuracy < 50)  $tips[] = ['fas fa-exclamation-circle','#C62828','Boost Your Accuracy',"Your accuracy is {$accuracy}%. Focus on understanding concepts, not just practicing quantity."];
if ($weakSubj!=='N/A') $tips[] = ['fas fa-target','#E65100','Weak Subject Alert',"Spend extra 30 mins daily on <strong>$weakSubj</strong> — your weakest subject."];
if ($strongSubj!=='N/A') $tips[] = ['fas fa-award','#2e7d32','Leverage Your Strength',"<strong>$strongSubj</strong> is your strongest subject. Maintain it while improving others."];
$tips[] = ['fas fa-file-archive','var(--maroon)','PYQ Strategy','Solve at least 5 previous year questions per subject every day.'];
$tips[] = ['fas fa-clock','#7B1FA2','Time Management','Practice timed tests to ensure you finish within the allotted time.'];
$tips[] = ['fas fa-repeat','var(--gold-dark)','Revision Cycle','Revise topics after 1 day, 3 days, and 1 week for maximum retention.'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Dashboard – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
.profile-hero{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon));
  padding:36px 0;position:relative;overflow:hidden;}
.profile-hero::before{content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(-45deg,transparent,transparent 20px,rgba(244,196,48,.05) 20px,rgba(244,196,48,.05) 22px);}
.profile-avatar{width:70px;height:70px;border-radius:50%;
  background:radial-gradient(circle,var(--gold-light),var(--gold));
  display:flex;align-items:center;justify-content:center;font-size:1.8rem;
  box-shadow:0 0 0 3px rgba(255,255,255,.25);flex-shrink:0;position:relative;z-index:1;}
.profile-name{font-family:var(--font-display);font-size:1.5rem;font-weight:900;color:#FFF;
  position:relative;z-index:1;}
.profile-email{font-size:.82rem;font-weight:600;color:rgba(255,255,255,.6);position:relative;z-index:1;}

/* Stat cards */
.stat-cards{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;margin:24px 0;}
.stat-card{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);
  box-shadow:var(--shadow);padding:18px;text-align:center;
  animation:statIn .6s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes statIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.stat-card:nth-child(1){animation-delay:.05s}.stat-card:nth-child(2){animation-delay:.10s}
.stat-card:nth-child(3){animation-delay:.15s}.stat-card:nth-child(4){animation-delay:.20s}
.stat-card:nth-child(5){animation-delay:.25s}.stat-card:nth-child(6){animation-delay:.30s}
.stat-num{font-family:var(--font-display);font-size:1.6rem;font-weight:900;color:var(--maroon);line-height:1;}
.stat-lbl{font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);margin-top:3px;}
.stat-icon{font-size:1.2rem;margin-bottom:6px;}

/* Dashboard sections */
.dash-section{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);
  box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden;}
.dash-section-header{padding:18px 24px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;gap:10px;}
.dash-section-title{font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--maroon);}
.dash-section-body{padding:20px 24px;}

/* Chart bars */
.chart-bar-wrap{display:flex;align-items:flex-end;gap:8px;height:120px;margin-top:8px;}
.chart-col{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;}
.chart-bar{width:100%;border-radius:6px 6px 0 0;
  background:linear-gradient(180deg,var(--gold-dark),var(--gold));
  transition:height 1s cubic-bezier(.4,0,.2,1);min-height:4px;}
.chart-label{font-size:.65rem;font-weight:700;color:var(--text-muted);white-space:nowrap;}
.chart-val{font-size:.65rem;font-weight:700;color:var(--maroon);}

/* Subject bars */
.subj-row{margin-bottom:14px;}
.subj-info{display:flex;justify-content:space-between;margin-bottom:5px;}
.subj-name{font-size:.84rem;font-weight:700;color:var(--text-dark);}
.subj-score{font-family:var(--font-display);font-size:.84rem;font-weight:700;color:var(--maroon);}

/* History table */
.hist-table{width:100%;border-collapse:collapse;}
.hist-table th{text-align:left;padding:10px 14px;font-size:.72rem;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);border-bottom:2px solid var(--border);}
.hist-table td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.85rem;font-weight:600;}
.hist-table tr:last-child td{border-bottom:none;}
.hist-table tr:hover td{background:rgba(128,0,0,.03);}

/* Tips */
.tip-card{display:flex;gap:14px;align-items:flex-start;padding:16px;
  border-radius:var(--radius-sm);margin-bottom:10px;
  background:rgba(128,0,0,.03);border:1px solid var(--border);transition:var(--transition);}
.tip-card:hover{background:rgba(128,0,0,.06);transform:translateX(4px);}
.tip-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.tip-title{font-family:var(--font-display);font-size:.88rem;font-weight:700;color:var(--maroon);margin-bottom:3px;}
.tip-text{font-family:var(--font-serif);font-size:.88rem;color:var(--text-muted);line-height:1.5;}

/* Sidebar */
.profile-layout{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;}

/* Tabs */
.dash-tabs{display:flex;gap:4px;border-bottom:2px solid var(--border);margin-bottom:20px;}
.dash-tab{padding:10px 18px;font-family:var(--font-body);font-size:.82rem;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);
  background:none;border:none;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:var(--transition);}
.dash-tab:hover{color:var(--maroon);}
.dash-tab.active{color:var(--maroon);border-bottom-color:var(--maroon);}
.dash-pane{display:none;} .dash-pane.active{display:block;}

/* Circles row */
.circles-row{display:flex;gap:20px;flex-wrap:wrap;justify-content:center;}

@media(max-width:900px){
  .stat-cards{grid-template-columns:repeat(3,1fr);}
  .profile-layout{grid-template-columns:1fr;}
}
@media(max-width:480px){
  .stat-cards{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body <?= $user['dark'] ? 'class="dark-mode"' : '' ?>>

<nav class="navbar">
  <a href="index.php" class="navbar-brand"><div class="brand-icon">🏛️</div><span>Skill</span>Edge</a>
  <button class="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></button>
  <div class="nav-links" id="navLinks">
    <a href="index.php" class="nav-link">Home</a>
    <a href="test.php" class="nav-link">Mock Tests</a>
    <a href="profile.php" class="nav-link active">Profile</a>
    <?php if($user['role']==='admin'): ?><a href="admin/manage_questions.php" class="nav-link">Admin</a><?php endif; ?>
    <a href="logout.php" class="nav-btn">Logout</a>
    <button class="dark-toggle" onclick="toggleDarkMode()"><i id="darkIcon" class="fas fa-moon"></i></button>
  </div>
</nav>
<div class="gold-line"></div>

<!-- Profile Hero -->
<div class="profile-hero">
  <div class="container" style="position:relative;z-index:1">
    <div style="display:flex;align-items:center;gap:18px">
      <div class="profile-avatar">👤</div>
      <div>
        <div class="profile-name"><?= h($user['name']) ?></div>
        <div class="profile-email"><?= h($user['email']) ?></div>
        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap">
          <span class="badge badge-gold"><i class="fas fa-calendar"></i> Last Test: <?= $lastTest ?></span>
          <span class="badge" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.9);border-color:rgba(255,255,255,.2)"><?= ucfirst($user['role']) ?></span>
        </div>
      </div>
      <div style="margin-left:auto">
        <a href="test.php" class="btn btn-gold ripple-btn"><i class="fas fa-play-circle"></i> Start Test</a>
      </div>
    </div>
  </div>
</div>

<div class="main-content">
<div class="container">

<!-- Stat Cards -->
<div class="stat-cards">
  <div class="stat-card">
    <div class="stat-icon" style="color:var(--maroon)">📊</div>
    <div class="stat-num"><?= $totalTests ?></div>
    <div class="stat-lbl">Tests Taken</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="color:#1565C0">❓</div>
    <div class="stat-num"><?= $totalQ ?></div>
    <div class="stat-lbl">Questions</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="color:#2e7d32">✅</div>
    <div class="stat-num"><?= $totalCorrect ?></div>
    <div class="stat-lbl">Correct</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="color:#C62828">❌</div>
    <div class="stat-num"><?= $stats['total_wrong']??0 ?></div>
    <div class="stat-lbl">Wrong</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="color:var(--gold-dark)">🎯</div>
    <div class="stat-num"><?= $accuracy ?>%</div>
    <div class="stat-lbl">Accuracy</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="color:#7B1FA2">⭐</div>
    <div class="stat-num" style="font-size:.9rem"><?= $strongSubj !=='N/A' ? substr($strongSubj,0,8).'..' : 'N/A' ?></div>
    <div class="stat-lbl">Strong Subject</div>
  </div>
</div>

<!-- Tabs -->
<div class="dash-tabs">
  <button class="dash-tab active" onclick="switchDash('analytics',this)"><i class="fas fa-chart-bar"></i> Analytics</button>
  <button class="dash-tab" onclick="switchDash('history',this)"><i class="fas fa-history"></i> Test History</button>
  <button class="dash-tab" onclick="switchDash('tips',this)"><i class="fas fa-lightbulb"></i> Study Tips</button>
</div>

<!-- Analytics Tab -->
<div id="dash-analytics" class="dash-pane active">
  <div class="profile-layout">
    <div>
      <!-- Weekly chart -->
      <div class="dash-section" data-anim>
        <div class="dash-section-header">
          <i class="fas fa-chart-line" style="color:var(--gold-dark)"></i>
          <div class="dash-section-title">Weekly Activity</div>
        </div>
        <div class="dash-section-body">
          <?php $maxW = max(1, ...array_column($weekly,'count')); ?>
          <div class="chart-bar-wrap" id="weekChart">
            <?php foreach($weekly as $d): ?>
            <div class="chart-col">
              <div class="chart-val"><?= $d['count'] ?></div>
              <div class="chart-bar" id="wbar_<?= $d['day'] ?>" style="height:0" data-h="<?= round($d['count']/$maxW*100) ?>"></div>
              <div class="chart-label"><?= $d['day'] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Subject performance -->
      <?php if(!empty($subjPerf)): ?>
      <div class="dash-section" data-anim>
        <div class="dash-section-header">
          <i class="fas fa-bullseye" style="color:var(--gold-dark)"></i>
          <div class="dash-section-title">Subject Performance</div>
        </div>
        <div class="dash-section-body">
          <?php foreach($subjPerf as $sp):
            $spct = round($sp['avg_score'],1);
            $bColor = $spct>=70?'#2e7d32':($spct>=50?'#F9A825':'#C62828');
          ?>
          <div class="subj-row">
            <div class="subj-info">
              <span class="subj-name"><?= h($sp['subject']) ?></span>
              <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:.72rem;font-weight:600;color:var(--text-muted)"><?= $sp['cnt'] ?> tests</span>
                <span class="subj-score"><?= $spct ?>%</span>
              </div>
            </div>
            <div class="progress-wrap">
              <div class="progress-bar" data-width="<?= $spct ?>" style="background:linear-gradient(90deg,<?= $bColor ?>,<?= $bColor ?>aa)"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar: Circles + Recommended -->
    <div>
      <!-- Circular scores -->
      <div class="dash-section" data-anim>
        <div class="dash-section-header">
          <i class="fas fa-circle-half-stroke" style="color:var(--gold-dark)"></i>
          <div class="dash-section-title">Score Overview</div>
        </div>
        <div class="dash-section-body">
          <svg width="0" height="0" style="position:absolute">
            <defs>
              <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#D4A820"/>
                <stop offset="100%" stop-color="#FFE080"/>
              </linearGradient>
            </defs>
          </svg>
          <div class="circles-row">
            <div style="text-align:center">
              <div class="circle-progress" data-pct="<?= $accuracy ?>">
                <svg width="100" height="100" viewBox="0 0 100 100"><circle class="track" cx="50" cy="50" r="40"/><circle class="fill" cx="50" cy="50" r="40"/></svg>
                <div class="label"><div class="pct"></div><div class="sub">Accuracy</div></div>
              </div>
            </div>
            <?php
            $avgScore = $totalTests > 0 ? round($conn->query("SELECT AVG(score_pct) v FROM tests WHERE user_id=$uid")->fetch_assoc()['v'],1) : 0;
            ?>
            <div style="text-align:center">
              <div class="circle-progress" data-pct="<?= $avgScore ?>">
                <svg width="100" height="100" viewBox="0 0 100 100"><circle class="track" cx="50" cy="50" r="40"/><circle class="fill" cx="50" cy="50" r="40"/></svg>
                <div class="label"><div class="pct"></div><div class="sub">Avg Score</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recommended -->
      <div class="dash-section" data-anim>
        <div class="dash-section-header">
          <i class="fas fa-compass" style="color:var(--gold-dark)"></i>
          <div class="dash-section-title">Recommended Topics</div>
        </div>
        <div class="dash-section-body">
          <?php
          $recs = !empty($subjPerf) ? array_slice(array_reverse($subjPerf), 0, 4) : [];
          if(empty($recs)):
          ?>
            <p style="font-family:var(--font-serif);color:var(--text-muted)">Take some tests first to get personalised recommendations.</p>
          <?php else: foreach($recs as $rec): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
              <div style="width:8px;height:8px;border-radius:50%;background:var(--maroon);flex-shrink:0"></div>
              <div>
                <div style="font-size:.84rem;font-weight:700;color:var(--text-dark)"><?= h($rec['subject']) ?></div>
                <div style="font-size:.72rem;font-weight:600;color:var(--text-muted)">Avg: <?= round($rec['avg_score'],1) ?>%</div>
              </div>
              <a href="test.php?subject=<?= urlencode($rec['subject']) ?>" class="btn btn-sm btn-primary" style="margin-left:auto">Practice</a>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- History Tab -->
<div id="dash-history" class="dash-pane">
  <div class="dash-section">
    <div class="dash-section-header">
      <i class="fas fa-history" style="color:var(--gold-dark)"></i>
      <div class="dash-section-title">Test History</div>
    </div>
    <div style="overflow-x:auto">
      <table class="hist-table">
        <thead>
          <tr>
            <th>#</th><th>Group</th><th>Subject</th><th>Score</th><th>Correct</th><th>Wrong</th><th>Time</th><th>Date</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($history)): ?>
            <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text-muted)">No tests taken yet. <a href="test.php" style="color:var(--maroon)">Start your first test →</a></td></tr>
          <?php else: foreach($history as $i=>$h): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><span class="badge badge-maroon"><?= h($h['exam_group']) ?></span></td>
            <td><?= h($h['subject']) ?></td>
            <td><span class="badge <?= $h['score_pct']>=60?'badge-green':($h['score_pct']>=40?'badge-gold':'badge-red') ?>"><?= $h['score_pct'] ?>%</span></td>
            <td style="color:#2e7d32;font-weight:700"><?= $h['correct'] ?></td>
            <td style="color:#C62828;font-weight:700"><?= $h['wrong'] ?></td>
            <td><?= gmdate('i:s',$h['time_taken']) ?></td>
            <td style="font-size:.78rem"><?= $h['dt'] ?></td>
            <td><a href="result.php?test_id=<?= $h['id'] ?>" class="btn btn-sm btn-primary">View</a></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tips Tab -->
<div id="dash-tips" class="dash-pane">
  <div class="dash-section">
    <div class="dash-section-header">
      <i class="fas fa-lightbulb" style="color:var(--gold-dark)"></i>
      <div class="dash-section-title">AI-Powered Study Tips</div>
      <span class="badge badge-gold" style="margin-left:auto">Personalised</span>
    </div>
    <div class="dash-section-body">
      <?php foreach($tips as $tip): ?>
      <div class="tip-card">
        <div class="tip-icon-wrap" style="background:<?= $tip[1] ?>20">
          <i class="<?= $tip[0] ?>" style="color:<?= $tip[1] ?>;font-size:1rem"></i>
        </div>
        <div>
          <div class="tip-title"><?= $tip[2] ?></div>
          <div class="tip-text"><?= $tip[3] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</div>
</div>

<script src="assets/js/main.js"></script>
<script>
// Animate weekly chart
window.addEventListener('load',()=>{
  setTimeout(()=>{
    document.querySelectorAll('.chart-bar').forEach(b=>{
      b.style.height = b.dataset.h+'%';
    });
  },300);
});

function switchDash(id, btn) {
  document.querySelectorAll('.dash-tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.dash-pane').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('dash-'+id).classList.add('active');
}
</script>
</body>
</html>
