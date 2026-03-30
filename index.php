<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
$user = isLoggedIn() ? currentUser() : null;

// Stats
$totalQ = $conn->query("SELECT COUNT(*) c FROM questions")->fetch_assoc()['c'] ?? 0;
$totalU = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0;
$totalT = $conn->query("SELECT COUNT(*) c FROM tests")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en" class="<?= ($user && $user['dark']) ? 'dark' : '' ?>">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Skill Edge – Master Your TNPSC Journey</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<?php if($user && $user['dark']): ?><script>document.body.classList.add('dark-mode');</script><?php endif; ?>
<style>
/* ── Hero ── */
.hero {
  background: linear-gradient(135deg, var(--maroon-deep) 0%, var(--maroon) 50%, var(--maroon-mid) 100%);
  min-height: 92vh;
  display: flex; align-items: center;
  position: relative; overflow: hidden;
  padding: 60px 0;
}

.hero::before {
  content: '';
  position: absolute; inset: 0;
  background:
    repeating-linear-gradient(-45deg, transparent, transparent 30px, rgba(244,196,48,0.04) 30px, rgba(244,196,48,0.04) 32px),
    radial-gradient(ellipse 70% 60% at 80% 50%, rgba(244,196,48,0.08) 0%, transparent 60%);
}

.hero-orb {
  position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none;
  animation: orbFloat 15s ease-in-out infinite alternate;
}
.hero-orb-1 { width:400px;height:400px;background:rgba(244,196,48,0.08);top:-100px;right:-100px;animation-delay:0s; }
.hero-orb-2 { width:280px;height:280px;background:rgba(255,255,255,0.04);bottom:0;left:-60px;animation-delay:-6s; }

@keyframes orbFloat {
  0%   { transform: translateY(0) scale(1); }
  100% { transform: translateY(-40px) scale(1.06); }
}

.hero-content { position: relative; z-index: 1; }

.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: rgba(244,196,48,0.15);
  border: 1px solid rgba(244,196,48,0.4);
  color: var(--gold-light);
  font-size: 0.75rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
  padding: 6px 16px; border-radius: 20px; margin-bottom: 20px;
  animation: badgePop 0.7s cubic-bezier(0.34,1.56,0.64,1) 0.2s both;
}
@keyframes badgePop { from{transform:scale(0.7);opacity:0} to{transform:scale(1);opacity:1} }

.hero-title {
  font-family: var(--font-display);
  font-size: clamp(2rem, 5vw, 3.6rem);
  font-weight: 900; color: #FFF; line-height: 1.1;
  letter-spacing: -0.01em;
  text-shadow: 0 4px 20px rgba(0,0,0,0.4);
  animation: heroTitleIn 0.8s cubic-bezier(0.4,0,0.2,1) 0.3s both;
}
.hero-title .gold { color: var(--gold-light); }

@keyframes heroTitleIn {
  from { opacity:0; transform: translateY(30px); }
  to   { opacity:1; transform: translateY(0); }
}

.hero-sub {
  font-family: var(--font-serif);
  font-size: clamp(1rem, 2vw, 1.2rem);
  color: rgba(255,255,255,0.75);
  margin: 16px 0 32px; line-height: 1.7;
  animation: heroTitleIn 0.8s cubic-bezier(0.4,0,0.2,1) 0.45s both;
}

.hero-btns {
  display: flex; flex-wrap: wrap; gap: 14px;
  animation: heroTitleIn 0.8s cubic-bezier(0.4,0,0.2,1) 0.6s both;
}

.hero-stats {
  display: flex; gap: 32px; margin-top: 48px;
  animation: heroTitleIn 0.8s cubic-bezier(0.4,0,0.2,1) 0.75s both;
}
.hero-stat-num {
  font-family: var(--font-display); font-size: 1.8rem; font-weight: 900;
  color: var(--gold-light); line-height: 1;
}
.hero-stat-label {
  font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
  color: rgba(255,255,255,0.5); margin-top: 3px;
}

/* Hero right image area */
.hero-visual {
  position: relative; z-index: 1;
  display: flex; align-items: center; justify-content: center;
}
.hero-emblem-wrap {
  width: 220px; height: 220px;
  background: radial-gradient(circle, rgba(244,196,48,0.15), transparent 70%);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  position: relative;
  animation: heroTitleIn 0.9s 0.5s both;
}
.hero-emblem-wrap::before {
  content: '';
  position: absolute; inset: -2px;
  border: 2px dashed rgba(244,196,48,0.3);
  border-radius: 50%;
  animation: spin-slow 25s linear infinite;
}
@keyframes spin-slow { to { transform: rotate(360deg); } }

.hero-emblem-wrap::after {
  content: '';
  position: absolute; inset: -24px;
  border: 1px dashed rgba(244,196,48,0.15);
  border-radius: 50%;
  animation: spin-slow 40s linear infinite reverse;
}
.hero-emblem-icon {
  font-size: 5rem; color: var(--gold);
  filter: drop-shadow(0 0 20px rgba(244,196,48,0.4));
  animation: iconPulse 3s ease-in-out infinite;
}
@keyframes iconPulse {
  0%,100% { transform: scale(1); filter: drop-shadow(0 0 20px rgba(244,196,48,0.4)); }
  50%      { transform: scale(1.05); filter: drop-shadow(0 0 35px rgba(244,196,48,0.65)); }
}

/* Floating cards around emblem */
.float-card {
  position: absolute;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(244,196,48,0.2);
  border-radius: 10px; padding: 8px 14px;
  font-family: var(--font-body); font-size: 0.75rem; font-weight: 700;
  color: rgba(255,255,255,0.9); white-space: nowrap;
  animation: floatCard 4s ease-in-out infinite;
  backdrop-filter: blur(4px);
}
.float-card i { color: var(--gold-light); margin-right: 6px; }
.float-card:nth-child(2) { top: 5%; left: -30%; animation-delay: -1s; }
.float-card:nth-child(3) { top: 20%; right: -20%; animation-delay: -2s; }
.float-card:nth-child(4) { bottom: 20%; left: -25%; animation-delay: -0.5s; }
.float-card:nth-child(5) { bottom: 5%; right: -15%; animation-delay: -3s; }

@keyframes floatCard {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-10px); }
}

/* ── Section: Groups ── */
.groups-section { padding: 80px 0; }

.group-card {
  background: var(--card-bg);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
  transition: var(--transition);
  cursor: pointer;
  text-decoration: none; color: inherit;
  display: block;
}
.group-card:hover {
  transform: translateY(-8px) scale(1.01);
  box-shadow: var(--shadow-hover);
  border-color: rgba(244,196,48,0.4);
}
.group-card-header {
  padding: 28px 24px 20px;
  background: linear-gradient(135deg, var(--maroon-deep), var(--maroon));
  position: relative; overflow: hidden;
}
.group-card-header::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(-45deg,transparent,transparent 15px,rgba(244,196,48,0.05) 15px,rgba(244,196,48,0.05) 17px);
}
.group-card-header::after {
  content: '';
  position: absolute; bottom: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--gold), transparent);
}
.group-icon {
  width: 52px; height: 52px;
  background: radial-gradient(circle, var(--gold-light), var(--gold));
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem; color: var(--maroon-deep);
  box-shadow: 0 4px 14px rgba(244,196,48,0.35);
  position: relative; z-index: 1;
  margin-bottom: 12px;
}
.group-name {
  font-family: var(--font-display); font-size: 1.2rem; font-weight: 900;
  color: #FFF; position: relative; z-index: 1;
}
.group-sub { font-size: 0.75rem; font-weight: 600; color: rgba(255,255,255,0.55);
  margin-top: 2px; letter-spacing: 0.06em; text-transform: uppercase;
  position: relative; z-index: 1; }

.group-card-body { padding: 20px 24px; }
.subject-pill {
  display: inline-block; margin: 3px;
  background: rgba(128,0,0,0.07);
  border: 1px solid rgba(128,0,0,0.15);
  color: var(--maroon); font-size: 0.7rem; font-weight: 700;
  padding: 3px 10px; border-radius: 20px;
  letter-spacing: 0.04em;
  transition: var(--transition);
}
.group-card:hover .subject-pill {
  background: var(--gold); color: var(--maroon-deep);
  border-color: var(--gold);
}
.group-card-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.q-count { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); }
.q-count strong { color: var(--maroon); font-family: var(--font-display); font-size: 1rem; }

/* ── Features ── */
.features-section {
  padding: 80px 0;
  background: linear-gradient(180deg, transparent, rgba(128,0,0,0.04), transparent);
}
.feature-card {
  padding: 28px; background: var(--card-bg);
  border-radius: var(--radius); border: 1px solid var(--border);
  box-shadow: var(--shadow); text-align: center;
  transition: var(--transition);
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-hover);
}
.feature-icon {
  width: 60px; height: 60px; margin: 0 auto 16px;
  background: linear-gradient(135deg, var(--maroon), var(--maroon-mid));
  border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: var(--gold-light);
  box-shadow: 0 6px 18px rgba(128,0,0,0.25);
  transition: var(--transition);
}
.feature-card:hover .feature-icon {
  background: linear-gradient(135deg, var(--gold-dark), var(--gold));
  color: var(--maroon-deep);
  box-shadow: 0 8px 24px rgba(244,196,48,0.4);
}
.feature-title { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--maroon); margin-bottom: 8px; }
.feature-desc  { font-family: var(--font-serif); font-size: 0.92rem; color: var(--text-muted); line-height: 1.6; }

/* ── CTA banner ── */
.cta-section {
  background: linear-gradient(135deg, var(--maroon-deep), var(--maroon), var(--maroon-mid));
  padding: 70px 0; text-align: center; position: relative; overflow: hidden;
}
.cta-section::before {
  content: '';
  position: absolute; inset: 0;
  background: repeating-linear-gradient(-45deg,transparent,transparent 25px,rgba(244,196,48,0.04) 25px,rgba(244,196,48,0.04) 27px);
}
.cta-title { font-family: var(--font-display); font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 900; color: #FFF; position: relative; z-index: 1; }
.cta-title span { color: var(--gold-light); }
.cta-desc { font-family: var(--font-serif); color: rgba(255,255,255,0.7); font-size: 1.05rem; margin: 12px 0 28px; position: relative; z-index: 1; }

/* ── Footer ── */
.footer {
  background: var(--maroon-deep);
  color: rgba(255,255,255,0.5);
  padding: 32px 0; text-align: center;
  font-size: 0.82rem; font-weight: 600;
  letter-spacing: 0.04em;
}
.footer a { color: var(--gold); text-decoration: none; }
.footer-brand { font-family: var(--font-display); font-size: 1rem; color: rgba(255,255,255,0.8); margin-bottom: 6px; }

/* Grid helpers */
.grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

@media(max-width:992px) { .grid-4 { grid-template-columns: repeat(2,1fr); } .grid-3 { grid-template-columns: repeat(2,1fr); } }
@media(max-width:640px) { .grid-4,.grid-3,.grid-2 { grid-template-columns: 1fr; } .hero-btns { flex-direction: column; } .hero-stats { gap: 18px; } }
</style>
</head>
<body <?= ($user && $user['dark']) ? 'class="dark-mode"' : '' ?>>

<!-- ── Navbar ── -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="brand-icon">🏛️</div>
    <span>Skill</span>Edge
  </a>
  <button class="hamburger" onclick="toggleMenu()" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
  <div class="nav-links" id="navLinks">
    <a href="index.php" class="nav-link active">Home</a>
    <a href="test.php" class="nav-link">Mock Tests</a>
    <a href="download.html" class="nav-link">Download</a>
    <a href="planner.html" class="nav-link">Planner</a>
    <a href="syllabus.php" class="nav-link">Syllabus</a>
    <?php if($user): ?>
      <a href="profile.php" class="nav-link">Profile</a>
      <?php if($user['role']==='admin'): ?>
        <a href="admin/manage_questions.php" class="nav-link">Admin</a>
      <?php endif; ?>
      <a href="logout.php" class="nav-btn">Logout</a>
    <?php else: ?>
      <a href="login.php" class="nav-link">Login</a>
      <a href="register.php" class="nav-btn ripple-btn">Get Started</a>
    <?php endif; ?>
    <button class="dark-toggle" onclick="toggleDarkMode()">
      <i id="darkIcon" class="fas fa-moon"></i>
    </button>
  </div>
</nav>
<div class="gold-line"></div>

<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center">
      <div class="hero-content">
        <div class="hero-badge">
          <i class="fas fa-star"></i> Tamil Nadu's #1 Exam Portal
        </div>
        <h1 class="hero-title">
          Master Your<br>
          <span class="gold">TNPSC Journey</span><br>
          with Skill Edge
        </h1>
        <p class="hero-sub">
          Comprehensive mock tests, previous year questions, and smart analytics to crack Group 1, 2, 2A & Group 4 exams.
        </p>
        <div class="hero-btns">
          <a href="test.php" class="btn btn-gold btn-lg ripple-btn">
            <i class="fas fa-play-circle"></i> Start Mock Test
          </a>
          <a href="syllabus.php" class="btn btn-outline btn-lg">
            <i class="fas fa-book-open"></i> View Syllabus
          </a>
          <a href="test.php?type=pyq" class="btn btn-primary btn-lg ripple-btn">
            <i class="fas fa-file-circle-question"></i> PYQ Practice
          </a>
        </div>
        <div class="hero-stats">
          <div>
            <div class="hero-stat-num"><?= number_format($totalQ) ?>+</div>
            <div class="hero-stat-label">Questions</div>
          </div>
          <div>
            <div class="hero-stat-num"><?= number_format($totalU) ?>+</div>
            <div class="hero-stat-label">Students</div>
          </div>
          <div>
            <div class="hero-stat-num"><?= number_format($totalT) ?>+</div>
            <div class="hero-stat-label">Tests Taken</div>
          </div>
        </div>
      </div>
      <div class="hero-visual" style="display:none" id="heroVisual">
        <!-- hidden on mobile via media query -->
      </div>
      <div class="hero-visual">
        <div class="hero-emblem-wrap">
          <div class="float-card"><i class="fas fa-bolt"></i>1000+ MCQs</div>
          <div class="float-card"><i class="fas fa-chart-line"></i>Smart Analytics</div>
          <div class="float-card"><i class="fas fa-trophy"></i>Top Rankers</div>
          <div class="float-card"><i class="fas fa-clock"></i>Timed Tests</div>
          <div class="hero-emblem-icon">🎓</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Exam Groups ── -->
<section class="groups-section">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px" data-anim>
      <div class="section-label">Choose Your Path</div>
      <div class="section-title" style="display:inline-block">Exam Groups</div>
      <p class="section-desc">Targeted preparation for every TNPSC examination level.</p>
    </div>

    <div class="grid-4" data-stagger>
      <?php
      $groups = [
        ['Group1',  'Group 1',  'IAS Level',      'fas fa-crown',       ['General Science','History & Culture','Indian Polity','Economy','Geography','Current Affairs','Aptitude']],
        ['Group2',  'Group 2',  'DSP / Deputy Collector','fas fa-shield-halved', ['General Science','History','Polity','Geography','Economy','Current Affairs','Aptitude']],
        ['Group2A', 'Group 2A', 'Non-Interview',   'fas fa-graduation-cap',['General Science','History','Polity','Geography','Economy','Aptitude','General Tamil']],
        ['Group4',  'Group 4',  'VAO / Village Admin','fas fa-building-columns',['General Science','Current Affairs','Geography','History','Polity','Economy','Aptitude','Language']],
      ];
      foreach ($groups as [$gid, $gname, $gsub, $gicon, $subjects]):
        $qc = $conn->query("SELECT COUNT(*) c FROM questions WHERE exam_group='$gid'")->fetch_assoc()['c'] ?? 0;
      ?>
      <a href="test.php?group=<?= $gid ?>" class="group-card" data-anim="scale">
        <div class="group-card-header">
          <div class="group-icon"><i class="<?= $gicon ?>"></i></div>
          <div class="group-name"><?= $gname ?></div>
          <div class="group-sub"><?= $gsub ?></div>
        </div>
        <div class="group-card-body">
          <?php foreach(array_slice($subjects,0,5) as $s): ?>
            <span class="subject-pill"><?= $s ?></span>
          <?php endforeach; ?>
          <?php if(count($subjects)>5): ?>
            <span class="subject-pill">+<?= count($subjects)-5 ?> more</span>
          <?php endif; ?>
        </div>
        <div class="group-card-footer">
          <span class="q-count"><strong><?= $qc ?></strong> Questions</span>
          <span class="btn btn-sm btn-primary">Start →</span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Features ── -->
<section class="features-section">
  <div class="container">
    <div style="text-align:center;margin-bottom:48px" data-anim>
      <div class="section-label">Why Skill Edge</div>
      <div class="section-title" style="display:inline-block">Everything You Need</div>
    </div>
    <div class="grid-3" data-stagger>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-stopwatch"></i></div>
        <div class="feature-title">Timed Mock Tests</div>
        <div class="feature-desc">Simulate real exam conditions with countdown timers and auto-submit.</div>
      </div>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="feature-title">Smart Analytics</div>
        <div class="feature-desc">Deep performance charts — identify your strong & weak subjects instantly.</div>
      </div>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-bookmark"></i></div>
        <div class="feature-title">Bookmark Questions</div>
        <div class="feature-desc">Save tricky questions for later review with one click.</div>
      </div>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-shuffle"></i></div>
        <div class="feature-title">Random Order</div>
        <div class="feature-desc">Questions shuffle every attempt so you can't memorise positions.</div>
      </div>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-lightbulb"></i></div>
        <div class="feature-title">Instant Explanations</div>
        <div class="feature-desc">Every question comes with a detailed explanation after submission.</div>
      </div>
      <div class="feature-card" data-anim>
        <div class="feature-icon"><i class="fas fa-moon"></i></div>
        <div class="feature-title">Dark Mode</div>
        <div class="feature-desc">Study comfortably at night with our beautiful dark theme.</div>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA ── -->
<section class="cta-section">
  <div class="container">
    <div data-anim>
      <h2 class="cta-title">Ready to <span>Ace Your TNPSC</span> Exam?</h2>
      <p class="cta-desc">Join thousands of students who are already preparing smarter with Skill Edge.</p>
      <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1">
        <?php if($user): ?>
          <a href="test.php" class="btn btn-gold btn-lg ripple-btn"><i class="fas fa-rocket"></i> Take a Test Now</a>
          <a href="profile.php" class="btn btn-outline btn-lg"><i class="fas fa-chart-line"></i> My Dashboard</a>
        <?php else: ?>
          <a href="register.php" class="btn btn-gold btn-lg ripple-btn"><i class="fas fa-user-plus"></i> Create Free Account</a>
          <a href="login.php" class="btn btn-outline btn-lg"><i class="fas fa-sign-in-alt"></i> Sign In</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Footer ── -->
<footer class="footer">
  <div class="footer-brand">Skill Edge – TNPSC Portal</div>
  <div>Tamil Nadu's Premier Exam Preparation Platform</div>
  <div style="margin-top:8px">
    <a href="admin/manage_questions.php">Admin</a> &nbsp;|&nbsp;
    <a href="test.php">Mock Tests</a> &nbsp;|&nbsp;
    <a href="profile.php">Dashboard</a>
  </div>
  <div style="margin-top:8px;font-size:.75rem">© <?= date('Y') ?> Skill Edge. All rights reserved.</div>
</footer>

<script src="assets/js/main.js"></script>
<script>
// Hero visual: hide on mobile
if (window.innerWidth < 768) {
  const heroGrid = document.querySelector('.hero .container > div');
  if (heroGrid) heroGrid.style.gridTemplateColumns = '1fr';
  const visual = document.querySelector('.hero-visual');
  if (visual) visual.style.display = 'none';
}
</script>
</body>
</html>
