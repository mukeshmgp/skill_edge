<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();
$user = currentUser();

$group   = $_GET['group']   ?? '';
$subject = $_GET['subject'] ?? '';
$action  = $_POST['action'] ?? '';

// Valid groups
$validGroups = ['Group1','Group2','Group2A','Group4'];
$subjects = [
  'Group1'  => ['General Science','Current Affairs','Geography','History & Culture','Indian Polity','Economy','Tamil Nadu Administration','Aptitude & Mental Ability'],
  'Group2'  => ['General Science','History','Polity','Geography','Economy','Current Affairs','Development Administration','Aptitude','General Tamil'],
  'Group2A' => ['General Science','History','Polity','Geography','Economy','Current Affairs','Aptitude','General Tamil'],
  'Group4'  => ['General Science','Current Affairs','Geography','History','Polity','Economy','Aptitude','Language'],
];

// Handle test submission
if ($action === 'submit') {
    $testGroup   = $_POST['test_group']   ?? '';
    $testSubject = $_POST['test_subject'] ?? '';
    $answers     = $_POST['answers']      ?? [];
    $timeTaken   = (int)($_POST['time_taken'] ?? 0);

    // Fetch questions
    $qids = array_keys($answers);
    if (!empty($qids)) {
        $placeholders = implode(',', array_fill(0, count($qids), '?'));
        $types = str_repeat('i', count($qids));
        $stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$qids);
        $stmt->execute();
        $res = $stmt->get_result();
        $correct_map = [];
        while ($r = $res->fetch_assoc()) $correct_map[$r['id']] = $r['correct_option'];
        $stmt->close();

        $correct = 0; $wrong = 0; $skipped = 0;
        foreach ($qids as $qid) {
            $sel = $answers[$qid] ?? 'X';
            if ($sel === 'X') $skipped++;
            elseif ($sel === ($correct_map[$qid] ?? '')) $correct++;
            else $wrong++;
        }
        $total   = count($qids);
        $scorePct = $total > 0 ? round(($correct/$total)*100, 2) : 0;

        // Insert test record
        $ins = $conn->prepare("INSERT INTO tests (user_id,exam_group,subject,total_q,correct,wrong,skipped,score_pct,time_taken) VALUES(?,?,?,?,?,?,?,?,?)");
        $ins->bind_param('issiiiidi', $user['id'], $testGroup, $testSubject, $total, $correct, $wrong, $skipped, $scorePct, $timeTaken);
        $ins->execute();
        $testId = $conn->insert_id;
        $ins->close();

        // Insert per-question results
        $ri = $conn->prepare("INSERT INTO results (test_id,user_id,question_id,selected_option,is_correct) VALUES(?,?,?,?,?)");
        foreach ($qids as $qid) {
            $sel = $answers[$qid] ?? 'X';
            $isC = ($sel !== 'X' && $sel === ($correct_map[$qid] ?? '')) ? 1 : 0;
            $ri->bind_param('iiisi', $testId, $user['id'], $qid, $sel, $isC);
            $ri->execute();
        }
        $ri->close();

        header("Location: result.php?test_id=$testId");
        exit;
    }
}

// If group + subject selected, load questions
$questions = [];
if ($group && $subject && in_array($group, $validGroups)) {
    $stmt = $conn->prepare("SELECT * FROM questions WHERE exam_group=? AND subject=? ORDER BY RAND() LIMIT 20");
    $stmt->bind_param('ss', $group, $subject);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (empty($questions)) {
        // Fallback: get any from group
        $stmt2 = $conn->prepare("SELECT * FROM questions WHERE exam_group=? ORDER BY RAND() LIMIT 15");
        $stmt2->bind_param('s', $group);
        $stmt2->execute();
        $questions = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Mock Test – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
/* Test selector */
.test-selector{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);
  box-shadow:var(--shadow);padding:32px;margin-bottom:28px;}
.group-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;
  padding:20px 16px;background:var(--card-bg);border:2px solid var(--border);
  border-radius:var(--radius);cursor:pointer;transition:var(--transition);text-decoration:none;color:inherit;}
.group-btn:hover,.group-btn.active{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon));
  border-color:var(--gold);color:#FFF;transform:translateY(-4px);box-shadow:var(--shadow-hover);}
.group-btn .g-icon{width:44px;height:44px;border-radius:10px;
  background:linear-gradient(135deg,var(--gold-dark),var(--gold));
  display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--maroon-deep);}
.group-btn:hover .g-icon,.group-btn.active .g-icon{background:rgba(255,255,255,0.2);color:var(--gold-light);}
.group-btn .g-name{font-family:var(--font-display);font-size:.95rem;font-weight:700;}
.group-btn .g-count{font-size:.7rem;font-weight:600;color:var(--text-muted);}
.group-btn:hover .g-count,.group-btn.active .g-count{color:rgba(255,255,255,.6);}

.subject-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:12px;}
.subj-btn{padding:10px 14px;background:rgba(128,0,0,0.05);border:1.5px solid rgba(128,0,0,0.15);
  border-radius:var(--radius-sm);cursor:pointer;font-family:var(--font-body);font-size:.84rem;font-weight:700;
  color:var(--maroon);transition:var(--transition);text-align:center;}
.subj-btn:hover{background:var(--maroon);color:#FFF;border-color:var(--maroon);}

/* Quiz area */
.quiz-header{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon));
  padding:20px 28px;border-radius:var(--radius) var(--radius) 0 0;
  display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.quiz-title{font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:#FFF;}
.quiz-meta{display:flex;align-items:center;gap:16px;}
.timer{background:rgba(244,196,48,.2);border:1px solid rgba(244,196,48,.4);color:var(--gold-light);
  font-family:var(--font-display);font-size:1.1rem;font-weight:700;
  padding:6px 16px;border-radius:20px;display:flex;align-items:center;gap:6px;}
.timer.warning{background:rgba(200,0,0,.3);border-color:rgba(200,0,0,.5);color:#FF8080;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.6}}
.q-counter{color:rgba(255,255,255,.7);font-size:.84rem;font-weight:700;}

.quiz-progress{height:5px;background:rgba(244,196,48,.15);}
.quiz-progress-fill{height:100%;background:linear-gradient(90deg,var(--gold-dark),var(--gold));transition:width .5s ease;}

.question-card{background:var(--card-bg);padding:28px;border:1px solid var(--border);
  box-shadow:var(--shadow);animation:qSlide .4s cubic-bezier(.4,0,.2,1) both;}
@keyframes qSlide{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

.q-number{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--gold-dark);margin-bottom:8px;}
.q-text{font-family:var(--font-serif);font-size:1.15rem;line-height:1.7;color:var(--text-dark);margin-bottom:24px;}

.options{display:flex;flex-direction:column;gap:10px;}
.option{display:flex;align-items:center;gap:14px;padding:14px 18px;
  background:rgba(128,0,0,.03);border:2px solid rgba(128,0,0,.12);
  border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition);}
.option:hover{background:rgba(128,0,0,.07);border-color:rgba(128,0,0,.3);}
.option.selected{background:rgba(128,0,0,.1);border-color:var(--maroon);box-shadow:0 0 0 3px rgba(128,0,0,.1);}
.option.selected .opt-letter{background:var(--maroon);color:#FFF;}
.opt-letter{width:32px;height:32px;border-radius:8px;
  background:rgba(128,0,0,.1);border:1.5px solid rgba(128,0,0,.2);
  display:flex;align-items:center;justify-content:center;
  font-family:var(--font-display);font-size:.85rem;font-weight:700;color:var(--maroon);
  flex-shrink:0;transition:var(--transition);}
.opt-text{font-family:var(--font-body);font-size:.95rem;font-weight:600;color:var(--text-dark);flex:1;}

/* Bookmark */
.bookmark-btn{background:none;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  padding:6px 12px;cursor:pointer;font-size:.78rem;font-weight:700;color:var(--text-muted);
  display:flex;align-items:center;gap:5px;transition:var(--transition);}
.bookmark-btn:hover,.bookmark-btn.bookmarked{background:rgba(244,196,48,.15);border-color:var(--gold-dark);color:var(--gold-dark);}

/* Nav buttons */
.quiz-nav{display:flex;align-items:center;justify-content:space-between;padding:20px 28px;
  background:var(--card-bg);border-top:1px solid var(--border);border-radius:0 0 var(--radius) var(--radius);
  flex-wrap:wrap;gap:10px;}

/* Question palette */
.palette{background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);
  box-shadow:var(--shadow);padding:20px;position:sticky;top:80px;}
.palette-title{font-family:var(--font-display);font-size:.85rem;font-weight:700;color:var(--maroon);margin-bottom:14px;}
.palette-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;}
.pal-btn{width:36px;height:36px;border:2px solid var(--border);border-radius:8px;
  background:rgba(128,0,0,.04);font-family:var(--font-display);font-size:.78rem;font-weight:700;
  color:var(--text-muted);cursor:pointer;transition:var(--transition);display:flex;align-items:center;justify-content:center;}
.pal-btn.answered{background:var(--maroon);border-color:var(--maroon);color:#FFF;}
.pal-btn.current{border-color:var(--gold);box-shadow:0 0 0 2px rgba(244,196,48,.3);}
.pal-btn.bookmarked-q{background:rgba(244,196,48,.2);border-color:var(--gold-dark);}
.palette-legend{margin-top:14px;display:flex;flex-direction:column;gap:6px;}
.leg-item{display:flex;align-items:center;gap:8px;font-size:.72rem;font-weight:600;color:var(--text-muted);}
.leg-dot{width:14px;height:14px;border-radius:4px;flex-shrink:0;}
</style>
</head>
<body <?= $user['dark'] ? 'class="dark-mode"' : '' ?>>

<!-- Navbar -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand"><div class="brand-icon">🏛️</div><span>Skill</span>Edge</a>
  <button class="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></button>
  <div class="nav-links" id="navLinks">
    <a href="index.php" class="nav-link">Home</a>
    <a href="test.php" class="nav-link active">Mock Tests</a>
    <a href="profile.php" class="nav-link">Profile</a>
    <a href="logout.php" class="nav-btn">Logout</a>
    <button class="dark-toggle" onclick="toggleDarkMode()"><i id="darkIcon" class="fas fa-moon"></i></button>
  </div>
</nav>
<div class="gold-line"></div>

<div class="main-content">
<div class="container">

<?php if (empty($questions)): ?>
<!-- ── SELECTOR VIEW ── -->
<div data-anim style="margin-bottom:24px">
  <div class="section-label">Prepare Smart</div>
  <div class="section-title">Mock Test Centre</div>
  <p class="section-desc">Select an exam group and subject to begin your test.</p>
</div>

<!-- Group selector -->
<div class="test-selector" data-anim>
  <div class="section-label" style="margin-bottom:16px">Step 1 – Choose Exam Group</div>
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px" id="groupGrid">
    <?php foreach(['Group1'=>['Group 1','fas fa-crown'],'Group2'=>['Group 2','fas fa-shield-halved'],'Group2A'=>['Group 2A','fas fa-graduation-cap'],'Group4'=>['Group 4','fas fa-building-columns']] as $gid=>[$gn,$gi]):
      $qc=$conn->query("SELECT COUNT(*) c FROM questions WHERE exam_group='$gid'")->fetch_assoc()['c']??0;
    ?>
    <button class="group-btn <?= ($group===$gid)?'active':'' ?>" onclick="selectGroup('<?= $gid ?>','<?= $gn ?>')">
      <div class="g-icon"><i class="<?= $gi ?>"></i></div>
      <div class="g-name"><?= $gn ?></div>
      <div class="g-count"><?= $qc ?> questions</div>
    </button>
    <?php endforeach; ?>
  </div>

  <div id="subjectSection" style="margin-top:24px;display:<?= $group ? 'block' : 'none' ?>">
    <div class="section-label" style="margin-bottom:12px">Step 2 – Choose Subject</div>
    <div class="subject-grid" id="subjectGrid">
      <?php if($group && isset($subjects[$group])): foreach($subjects[$group] as $s): ?>
        <button class="subj-btn" onclick="startTest('<?= $group ?>','<?= addslashes($s) ?>')"><?= h($s) ?></button>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Recent tests -->
<?php
$recentTests = $conn->query("SELECT t.*,DATE_FORMAT(taken_at,'%d %b %Y') as dt FROM tests t WHERE user_id={$user['id']} ORDER BY taken_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
if (!empty($recentTests)):
?>
<div data-anim style="margin-top:32px">
  <h3 style="font-family:var(--font-display);color:var(--maroon);margin-bottom:16px">Recent Tests</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php foreach($recentTests as $t): ?>
    <div class="card" style="padding:18px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
        <div>
          <div style="font-family:var(--font-display);font-weight:700;font-size:.9rem;color:var(--maroon)"><?= h($t['exam_group']) ?></div>
          <div style="font-size:.75rem;font-weight:600;color:var(--text-muted);margin-top:2px"><?= h($t['subject']) ?></div>
        </div>
        <span class="badge <?= $t['score_pct']>=60?'badge-green':($t['score_pct']>=40?'badge-gold':'badge-red') ?>"><?= $t['score_pct'] ?>%</span>
      </div>
      <div class="progress-wrap"><div class="progress-bar" style="width:<?= $t['score_pct'] ?>%"></div></div>
      <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:.72rem;font-weight:600;color:var(--text-muted)">
        <span>✓ <?= $t['correct'] ?> &nbsp; ✗ <?= $t['wrong'] ?></span>
        <span><?= $t['dt'] ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<script>
const subjectsMap = <?= json_encode($subjects) ?>;
function selectGroup(gid, gname) {
  document.querySelectorAll('.group-btn').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
  const grid = document.getElementById('subjectGrid');
  const sec  = document.getElementById('subjectSection');
  grid.innerHTML = '';
  (subjectsMap[gid]||[]).forEach(s => {
    const b = document.createElement('button');
    b.className = 'subj-btn';
    b.textContent = s;
    b.onclick = () => startTest(gid, s);
    grid.appendChild(b);
  });
  sec.style.display = 'block';
  sec.scrollIntoView({behavior:'smooth',block:'nearest'});
}
function startTest(group, subject) {
  window.location.href = `test.php?group=${encodeURIComponent(group)}&subject=${encodeURIComponent(subject)}`;
}
</script>

<?php else: ?>
<!-- ── QUIZ VIEW ── -->
<?php $totalQ = count($questions); $timeLimit = $totalQ * 90; // 90s per question ?>
<form method="POST" id="quizForm">
  <input type="hidden" name="action" value="submit"/>
  <input type="hidden" name="test_group" value="<?= h($group) ?>"/>
  <input type="hidden" name="test_subject" value="<?= h($subject) ?>"/>
  <input type="hidden" name="time_taken" id="timeTakenInput" value="0"/>
  <?php foreach($questions as $q): ?>
    <input type="hidden" name="answers[<?= $q['id'] ?>]" id="ans_<?= $q['id'] ?>" value="X"/>
  <?php endforeach; ?>

  <div style="display:grid;grid-template-columns:1fr 220px;gap:20px;align-items:start">
    <div>
      <!-- Quiz header -->
      <div class="quiz-header">
        <div>
          <div class="quiz-title"><i class="fas fa-layer-group" style="color:var(--gold-light);margin-right:6px"></i><?= h($group) ?> – <?= h($subject) ?></div>
          <div style="font-size:.74rem;color:rgba(255,255,255,.55);margin-top:2px"><?= $totalQ ?> Questions • Auto-submit when timer ends</div>
        </div>
        <div class="quiz-meta">
          <div class="timer" id="timer">
            <i class="fas fa-clock"></i><span id="timerDisplay"><?= gmdate('i:s',$timeLimit) ?></span>
          </div>
          <div class="q-counter"><span id="currentQNum">1</span> / <?= $totalQ ?></div>
        </div>
      </div>
      <div class="quiz-progress"><div class="quiz-progress-fill" id="progressFill" style="width:<?= round(1/$totalQ*100) ?>%"></div></div>

      <!-- Question display -->
      <div id="questionArea">
        <?php foreach($questions as $qi => $q): ?>
        <div id="q_<?= $qi ?>" class="question-card" style="display:<?= $qi===0?'block':'none' ?>">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <div class="q-number">Question <?= $qi+1 ?> of <?= $totalQ ?></div>
            <button type="button" class="bookmark-btn" id="bm_<?= $q['id'] ?>" onclick="toggleBookmark(<?= $q['id'] ?>)">
              <i class="fas fa-bookmark"></i> Bookmark
            </button>
          </div>
          <div class="q-text"><?= nl2br(h($q['question'])) ?></div>
          <div class="options">
            <?php foreach(['A'=>$q['option_a'],'B'=>$q['option_b'],'C'=>$q['option_c'],'D'=>$q['option_d']] as $key=>$val): ?>
            <div class="option" id="opt_<?= $q['id'] ?>_<?= $key ?>" onclick="selectAnswer(<?= $q['id'] ?>,'<?= $key ?>',<?= $qi ?>)">
              <div class="opt-letter"><?= $key ?></div>
              <div class="opt-text"><?= h($val) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Nav -->
      <div class="quiz-nav">
        <button type="button" class="btn btn-primary ripple-btn" id="prevBtn" onclick="navigate(-1)" disabled>
          <i class="fas fa-chevron-left"></i> Previous
        </button>
        <button type="button" class="btn btn-gold ripple-btn" onclick="confirmSubmit()">
          <i class="fas fa-paper-plane"></i> Submit Test
        </button>
        <button type="button" class="btn btn-primary ripple-btn" id="nextBtn" onclick="navigate(1)"
          <?= $totalQ<=1?'disabled':'' ?>>
          Next <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Palette -->
    <div class="palette">
      <div class="palette-title"><i class="fas fa-th" style="color:var(--gold-dark);margin-right:5px"></i>Question Palette</div>
      <div class="palette-grid" id="palGrid">
        <?php foreach($questions as $qi => $q): ?>
        <button type="button" class="pal-btn <?= $qi===0?'current':'' ?>" id="pal_<?= $qi ?>" onclick="goTo(<?= $qi ?>)"><?= $qi+1 ?></button>
        <?php endforeach; ?>
      </div>
      <div class="palette-legend">
        <div class="leg-item"><div class="leg-dot" style="background:var(--maroon)"></div>Answered</div>
        <div class="leg-item"><div class="leg-dot" style="background:rgba(128,0,0,.08);border:2px solid var(--border)"></div>Not Answered</div>
        <div class="leg-item"><div class="leg-dot" style="background:rgba(244,196,48,.2);border:1.5px solid var(--gold-dark)"></div>Bookmarked</div>
      </div>
      <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
        <div style="font-size:.72rem;font-weight:700;color:var(--text-muted);margin-bottom:8px">Progress</div>
        <div class="progress-wrap"><div class="progress-bar" id="palProgress" style="width:0%"></div></div>
        <div style="font-size:.72rem;font-weight:600;color:var(--text-muted);margin-top:4px">
          <span id="answeredCount">0</span> / <?= $totalQ ?> answered
        </div>
      </div>
    </div>
  </div>
</form>

<script>
const totalQ    = <?= $totalQ ?>;
const timeLimit = <?= $timeLimit ?>;
const qIds      = <?= json_encode(array_column($questions,'id')) ?>;
let current     = 0;
let answers     = {};
let bookmarks   = new Set();
let timeLeft    = timeLimit;
let startTime   = Date.now();

// ── Timer ──
const timerEl = document.getElementById('timer');
const timerDisplay = document.getElementById('timerDisplay');
const timerInterval = setInterval(() => {
  timeLeft--;
  if (timeLeft <= 0) { clearInterval(timerInterval); submitTest(); return; }
  const m = Math.floor(timeLeft/60), s = timeLeft%60;
  timerDisplay.textContent = String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
  if (timeLeft <= 60) timerEl.classList.add('warning');
}, 1000);

// ── Navigate ──
function navigate(dir) {
  goTo(current + dir);
}

function goTo(idx) {
  if (idx < 0 || idx >= totalQ) return;
  document.getElementById('q_'+current).style.display = 'none';
  document.getElementById('pal_'+current).classList.remove('current');
  current = idx;
  document.getElementById('q_'+current).style.display = 'block';
  document.getElementById('pal_'+current).classList.add('current');
  document.getElementById('currentQNum').textContent = current + 1;
  document.getElementById('prevBtn').disabled = current === 0;
  document.getElementById('nextBtn').disabled = current === totalQ - 1;
  document.getElementById('progressFill').style.width = ((current+1)/totalQ*100)+'%';
  // Re-animate
  const qc = document.getElementById('q_'+current);
  qc.style.animation = 'none';
  void qc.offsetHeight;
  qc.style.animation = '';
}

// ── Select answer ──
function selectAnswer(qId, opt, qi) {
  // Clear previous selection for this question
  ['A','B','C','D'].forEach(k => {
    document.getElementById('opt_'+qId+'_'+k)?.classList.remove('selected');
  });
  document.getElementById('opt_'+qId+'_'+opt).classList.add('selected');
  answers[qId] = opt;
  document.getElementById('ans_'+qId).value = opt;
  // Update palette
  document.getElementById('pal_'+qi).classList.add('answered');
  // Update count
  updateProgress();
}

function updateProgress() {
  const cnt = Object.keys(answers).length;
  document.getElementById('answeredCount').textContent = cnt;
  document.getElementById('palProgress').style.width = (cnt/totalQ*100)+'%';
}

// ── Bookmark ──
function toggleBookmark(qId) {
  const btn = document.getElementById('bm_'+qId);
  if (bookmarks.has(qId)) {
    bookmarks.delete(qId);
    btn.classList.remove('bookmarked');
    btn.innerHTML = '<i class="fas fa-bookmark"></i> Bookmark';
    // Find qi
    const qi = qIds.indexOf(qId);
    if (qi>=0) document.getElementById('pal_'+qi).classList.remove('bookmarked-q');
  } else {
    bookmarks.add(qId);
    btn.classList.add('bookmarked');
    btn.innerHTML = '<i class="fas fa-bookmark" style="color:var(--gold-dark)"></i> Bookmarked';
    const qi = qIds.indexOf(qId);
    if (qi>=0) document.getElementById('pal_'+qi).classList.add('bookmarked-q');
    // Save to DB via fetch
    fetch('ajax/bookmark.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'qid='+qId+'&action=add'}).catch(()=>{});
  }
}

// ── Submit ──
function confirmSubmit() {
  const unanswered = totalQ - Object.keys(answers).length;
  const msg = unanswered > 0
    ? `You have ${unanswered} unanswered question(s). Submit anyway?`
    : 'Submit this test?';
  if (confirm(msg)) submitTest();
}

function submitTest() {
  clearInterval(timerInterval);
  document.getElementById('timeTakenInput').value = timeLimit - timeLeft;
  document.getElementById('quizForm').submit();
}

// Keyboard shortcuts
document.addEventListener('keydown', e => {
  if (e.key === 'ArrowRight') navigate(1);
  if (e.key === 'ArrowLeft')  navigate(-1);
  if (['a','b','c','d'].includes(e.key.toLowerCase())) {
    const qId = qIds[current];
    selectAnswer(qId, e.key.toUpperCase(), current);
  }
});
</script>
<?php endif; ?>

</div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
