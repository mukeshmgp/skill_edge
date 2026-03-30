<?php
require_once 'includes/auth.php';
$user = isLoggedIn() ? currentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Syllabus – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
.syl-card{background:var(--card-bg);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);overflow:hidden;}
.syl-header{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon));padding:20px 24px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;}
.syl-title{font-family:var(--font-display);font-size:1.05rem;font-weight:700;color:#FFF;}
.syl-body{padding:20px 24px;display:none;}
.syl-body.open{display:block;}
.syl-subject{margin-bottom:16px;}
.syl-subj-name{font-family:var(--font-display);font-size:.9rem;font-weight:700;color:var(--maroon);margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.syl-subj-name::before{content:'';width:4px;height:16px;background:var(--gold);border-radius:2px;display:inline-block;}
.topic-list{display:flex;flex-wrap:wrap;gap:6px;}
.topic-pill{background:rgba(128,0,0,.07);border:1px solid rgba(128,0,0,.15);color:var(--maroon);font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:20px;}
</style>
</head>
<body <?= ($user&&$user['dark'])?'class="dark-mode"':'' ?>>
<nav class="navbar">
  <a href="index.php" class="navbar-brand"><div class="brand-icon">🏛️</div><span>Skill</span>Edge</a>
  <div class="nav-links" id="navLinks">
    <a href="index.php" class="nav-link">Home</a>
    <a href="test.php" class="nav-link">Mock Tests</a>
    <a href="syllabus.php" class="nav-link active">Syllabus</a>
    <?php if($user): ?>
    <a href="profile.php" class="nav-link">Profile</a>
    <a href="logout.php" class="nav-btn">Logout</a>
    <?php else: ?>
    <a href="login.php" class="nav-btn">Login</a>
    <?php endif; ?>
    <button class="dark-toggle" onclick="toggleDarkMode()"><i id="darkIcon" class="fas fa-moon"></i></button>
  </div>
</nav>
<div class="gold-line"></div>
<div class="main-content">
<div class="container">
  <div style="margin-bottom:32px" data-anim>
    <div class="section-label">Complete Coverage</div>
    <div class="section-title">TNPSC Syllabus</div>
    <p class="section-desc">Detailed topic-wise syllabus for all TNPSC exam groups.</p>
  </div>
  <div style="display:flex;flex-direction:column;gap:16px">
    <?php
    $syllabi = [
      'Group 1' => [
        'General Science' => ['Physics – Laws, Units, Mechanics','Chemistry – Elements, Compounds','Biology – Cell, Genetics, Ecology'],
        'History & Culture' => ['Ancient India','Medieval India','Modern India','Tamil Nadu History'],
        'Indian Polity' => ['Constitution','Fundamental Rights','Parliament','Judiciary'],
        'Economy' => ['National Income','Agriculture','Banking & Finance','Five Year Plans'],
        'Geography' => ['Physical Geography','Indian Geography','Tamil Nadu Geography'],
        'Current Affairs' => ['National Events','International Events','Awards & Honours','Science & Technology'],
        'Aptitude & Mental Ability' => ['Number Series','Reasoning','Data Interpretation','Simplification'],
      ],
      'Group 2 / 2A' => [
        'General Science' => ['Basic Physics','Basic Chemistry','Basic Biology'],
        'History' => ['Indian History','Freedom Struggle','Tamil History'],
        'Polity' => ['Constitution Basics','Local Government','Elections'],
        'Geography' => ['India & World Geography','Climate','Natural Resources'],
        'Economy' => ['Economic Development','Agriculture','Trade & Commerce'],
        'Aptitude' => ['Arithmetic','Reasoning','Data Interpretation'],
        'General Tamil' => ['Grammar','Literature','Comprehension'],
      ],
      'Group 4' => [
        'General Science' => ['Everyday Science','Basic Concepts'],
        'History' => ['Indian History','Tamil History'],
        'Polity' => ['Constitution','Democratic System'],
        'Economy' => ['Basic Economy','Banking Basics'],
        'Aptitude' => ['Arithmetic','Logical Reasoning'],
        'Language' => ['Tamil / English Grammar','Comprehension'],
      ],
    ];
    foreach($syllabi as $grp => $subjects):
    ?>
    <div class="syl-card" data-anim>
      <div class="syl-header" onclick="toggleSyl(this)">
        <div class="syl-title"><i class="fas fa-layer-group" style="color:var(--gold-light);margin-right:8px"></i><?= $grp ?></div>
        <i class="fas fa-chevron-down" style="color:rgba(255,255,255,.6);transition:transform .35s"></i>
      </div>
      <div class="syl-body">
        <?php foreach($subjects as $subj => $topics): ?>
        <div class="syl-subject">
          <div class="syl-subj-name"><?= $subj ?></div>
          <div class="topic-list">
            <?php foreach($topics as $topic): ?><span class="topic-pill"><?= $topic ?></span><?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</div>
<script src="assets/js/main.js"></script>
<script>
function toggleSyl(hdr){
  const body=hdr.nextElementSibling, icon=hdr.querySelector('.fa-chevron-down');
  body.classList.toggle('open');
  icon.style.transform=body.classList.contains('open')?'rotate(180deg)':'';
}
</script>
</body>
</html>
