<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = ''; $stored = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stored = h($login);
    if (!$login || !$pass) {
        $error = 'Please enter your email/username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id,name,email,password,role,dark_mode FROM users WHERE email=? OR name=? LIMIT 1");
        $stmt->bind_param('ss',$login,$login);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$u || !password_verify($pass,$u['password'])) {
            $error = 'Invalid credentials. Please try again.';
        } else {
            $_SESSION['user_id']   = $u['id'];
            $_SESSION['name']      = $u['name'];
            $_SESSION['email']     = $u['email'];
            $_SESSION['role']      = $u['role'];
            $_SESSION['dark_mode'] = $u['dark_mode'];
            header('Location: ' . ($_GET['redirect'] ?? 'index.php'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Login – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
body::before{content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% -5%,rgba(128,0,0,0.2) 0%,transparent 65%),
  radial-gradient(ellipse 50% 40% at 90% 100%,rgba(244,196,48,0.1) 0%,transparent 60%);
  z-index:0;}
.orb{position:fixed;border-radius:50%;filter:blur(70px);pointer-events:none;z-index:0;animation:orbF 16s ease-in-out infinite alternate;}
.orb-1{width:350px;height:350px;background:rgba(128,0,0,0.12);top:-100px;right:-80px;}
.orb-2{width:250px;height:250px;background:rgba(244,196,48,0.09);bottom:-60px;left:-60px;animation-delay:-7s;}
@keyframes orbF{0%{transform:translateY(0) scale(1)}100%{transform:translateY(-35px) scale(1.06)}}
.auth-wrap{position:relative;z-index:1;width:100%;max-width:440px;}
.auth-card{background:rgba(255,255,255,0.97);border-radius:18px;border:1px solid rgba(244,196,48,0.2);
  box-shadow:0 8px 48px rgba(92,0,0,0.18);overflow:hidden;
  animation:cardIn .7s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(40px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
.auth-header{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon),var(--maroon-mid));
  padding:32px 32px 26px;text-align:center;position:relative;overflow:hidden;}
.auth-header::before{content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(-45deg,transparent,transparent 20px,rgba(244,196,48,0.05) 20px,rgba(244,196,48,0.05) 22px);}
.auth-emblem{width:68px;height:68px;background:radial-gradient(circle,var(--gold-light),var(--gold));
  border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;
  font-size:28px;box-shadow:0 0 0 3px rgba(255,255,255,0.2),0 4px 20px rgba(0,0,0,0.3);
  position:relative;z-index:1;animation:embPop .8s cubic-bezier(0.34,1.56,0.64,1) .2s both;}
@keyframes embPop{from{transform:scale(0) rotate(-180deg);opacity:0}to{transform:scale(1);opacity:1}}
.auth-emblem::after{content:'';position:absolute;inset:-6px;border:2px dashed rgba(244,196,48,0.4);
  border-radius:50%;animation:spin-slow 20s linear infinite;}
.auth-title{font-family:var(--font-display);font-size:1.55rem;font-weight:900;color:#FFF;
  position:relative;z-index:1;text-shadow:0 2px 10px rgba(0,0,0,0.4);}
.auth-title span{color:var(--gold-light);}
.auth-sub{font-size:.78rem;font-weight:600;color:rgba(255,255,255,.6);margin-top:4px;
  letter-spacing:.09em;text-transform:uppercase;position:relative;z-index:1;}
.auth-body{padding:32px;}
.input-wrap{position:relative;}
.inp-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:var(--maroon-mid);font-size:.85rem;pointer-events:none;transition:color .25s;}
.input-wrap:focus-within .inp-icon{color:var(--maroon);}
.form-control{padding-left:44px;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:var(--maroon-mid);font-size:.85rem;transition:color .2s;}
.toggle-pw:hover{color:var(--maroon);}
.forgot{display:block;text-align:right;font-size:.75rem;font-weight:700;
  color:var(--maroon);text-decoration:none;margin-top:5px;}
.forgot:hover{color:var(--maroon-light);text-decoration:underline;}
.btn-submit{width:100%;background:linear-gradient(135deg,var(--maroon-deep),var(--maroon),var(--maroon-mid));
  border:none;color:#FFF;font-family:var(--font-body);font-size:1rem;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;padding:14px;border-radius:10px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:0 4px 18px rgba(128,0,0,.3);position:relative;overflow:hidden;margin-top:8px;
  transition:var(--transition);}
.btn-submit::before{content:'';position:absolute;inset:0;
  background:linear-gradient(135deg,var(--gold-dark),var(--gold));opacity:0;transition:opacity .35s;}
.btn-submit:hover::before{opacity:1;}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(244,196,48,.4);}
.btn-submit:hover span,.btn-submit:hover i{color:var(--maroon-deep);}
.btn-submit span,.btn-submit i{position:relative;z-index:1;}
.auth-divider{display:flex;align-items:center;gap:12px;margin:22px 0;}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;
  background:linear-gradient(90deg,transparent,rgba(128,0,0,.2),transparent);}
.auth-divider span{font-size:.73rem;font-weight:700;color:var(--text-muted);letter-spacing:.08em;text-transform:uppercase;}
.auth-footer{text-align:center;font-size:.85rem;font-weight:600;color:var(--text-muted);}
.auth-footer a{color:var(--maroon);font-weight:700;text-decoration:none;}
.auth-footer a:hover{color:var(--maroon-light);text-decoration:underline;}
.form-group{animation:fldIn .5s cubic-bezier(.4,0,.2,1) both;}
.form-group:nth-child(1){animation-delay:.1s} .form-group:nth-child(2){animation-delay:.18s}
.form-group:nth-child(3){animation-delay:.26s} .form-group:nth-child(4){animation-delay:.34s}
@keyframes fldIn{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
.btn-submit{animation:fldIn .5s .4s both;}
.auth-footer{animation:fldIn .5s .5s both;}
</style>
</head>
<body>
<div class="orb orb-1"></div><div class="orb orb-2"></div>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-emblem">🏛️</div>
      <div class="auth-title"><span>Skill</span>Edge</div>
      <div class="auth-sub">Sign in to your account</div>
    </div>
    <div class="gold-line"></div>
    <div class="auth-body">
      <?php if($error): ?>
        <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><?= h($error) ?></div>
      <?php endif; ?>
      <form method="POST" id="lf">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i>Email or Username</label>
          <div class="input-wrap">
            <i class="fas fa-user inp-icon"></i>
            <input type="text" name="login" class="form-control" placeholder="Enter email or username"
              value="<?= $stored ?>" required autofocus autocomplete="username"/>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-lock"></i>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock inp-icon"></i>
            <input type="password" name="password" class="form-control" id="lpw"
              placeholder="Enter password" required autocomplete="current-password"/>
            <button type="button" class="toggle-pw" onclick="tp()"><i class="fas fa-eye" id="leye"></i></button>
          </div>
          <a href="forgot_password.php" class="forgot"><i class="fas fa-key" style="font-size:.65rem"></i> Forgot password?</a>
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
          <input type="checkbox" name="remember" id="rem" style="width:16px;height:16px;accent-color:var(--maroon)"/>
          <label for="rem" style="font-size:.82rem;font-weight:600;color:var(--text-muted);cursor:pointer">Keep me signed in</label>
        </div>
        <button type="submit" class="btn-submit ripple-btn" id="sb">
          <i class="fas fa-arrow-right-to-bracket"></i><span>Sign In</span>
        </button>
      </form>
      <div class="auth-divider"><span>new to skill edge?</span></div>
      <div class="auth-footer">Don't have an account? <a href="register.php"><i class="fas fa-user-plus"></i> Register free</a></div>
    </div>
  </div>
</div>
<script src="assets/js/main.js"></script>
<script>
function tp(){const i=document.getElementById('lpw'),e=document.getElementById('leye'),s=i.type==='password';i.type=s?'text':'password';e.className=s?'fas fa-eye-slash':'fas fa-eye';}
document.getElementById('lf').addEventListener('submit',function(){
  const b=document.getElementById('sb');b.innerHTML='<i class="fas fa-spinner fa-spin"></i><span>Signing in…</span>';b.disabled=true;
});
</script>
</body>
</html>
