<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error=''; $success=''; $f=['name'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password']   ?? '';
    $conf  = $_POST['confirm']    ?? '';
    $f = ['name'=>h($name),'email'=>h($email)];

    if (!$name||!$email||!$pass) { $error='All required fields must be filled.'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $error='Invalid email address.'; }
    elseif (strlen($name)<3) { $error='Name must be at least 3 characters.'; }
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',$pass)) {
        $error='Password needs 8+ chars, 1 uppercase, 1 number, 1 special char.';
    } elseif ($pass!==$conf) { $error='Passwords do not match.'; }
    else {
        $chk=$conn->prepare("SELECT id FROM users WHERE email=? OR name=?");
        $chk->bind_param('ss',$email,$name); $chk->execute(); $chk->store_result();
        if ($chk->num_rows>0) { $error='Email or username already exists.'; }
        else {
            $hash=password_hash($pass,PASSWORD_BCRYPT,['cost'=>12]);
            $ins=$conn->prepare("INSERT INTO users (name,email,password) VALUES(?,?,?)");
            $ins->bind_param('sss',$name,$email,$hash);
            if ($ins->execute()) { $success='Account created! You can now <a href="login.php">sign in</a>.'; $f=['name'=>'','email'=>'']; }
            else { $error='Registration failed. Try again.'; }
            $ins->close();
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Register – Skill Edge</title>
<link rel="stylesheet" href="assets/css/style.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
body::before{content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(128,0,0,0.2) 0%,transparent 65%),
  radial-gradient(ellipse 50% 40% at 80% 90%,rgba(244,196,48,0.1) 0%,transparent 60%);z-index:0;}
.orb{position:fixed;border-radius:50%;filter:blur(70px);pointer-events:none;z-index:0;animation:orbF 16s ease-in-out infinite alternate;}
.orb-1{width:350px;height:350px;background:rgba(128,0,0,0.12);top:-100px;left:-80px;}
.orb-2{width:250px;height:250px;background:rgba(244,196,48,0.09);bottom:-60px;right:-60px;animation-delay:-7s;}
.orb-3{width:180px;height:180px;background:rgba(92,0,0,0.08);top:40%;right:5%;animation-delay:-4s;}
@keyframes orbF{0%{transform:translateY(0)}100%{transform:translateY(-35px)}}
.auth-wrap{position:relative;z-index:1;width:100%;max-width:520px;}
.auth-card{background:rgba(255,255,255,0.97);border-radius:18px;border:1px solid rgba(244,196,48,0.2);
  box-shadow:0 8px 48px rgba(92,0,0,0.18);overflow:hidden;
  animation:cardIn .7s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes cardIn{from{opacity:0;transform:translateY(40px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
.auth-header{background:linear-gradient(135deg,var(--maroon-deep),var(--maroon),var(--maroon-mid));
  padding:28px 32px 22px;text-align:center;position:relative;overflow:hidden;}
.auth-header::before{content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(-45deg,transparent,transparent 20px,rgba(244,196,48,0.05) 20px,rgba(244,196,48,0.05) 22px);}
.auth-emblem{width:62px;height:62px;background:radial-gradient(circle,var(--gold-light),var(--gold));
  border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;
  font-size:26px;box-shadow:0 0 0 3px rgba(255,255,255,0.2);position:relative;z-index:1;
  animation:embPop .8s cubic-bezier(0.34,1.56,0.64,1) .2s both;}
@keyframes embPop{from{transform:scale(0) rotate(-180deg);opacity:0}to{transform:scale(1);opacity:1}}
.auth-emblem::after{content:'';position:absolute;inset:-6px;border:2px dashed rgba(244,196,48,0.4);
  border-radius:50%;animation:spin-slow 20s linear infinite;}
.auth-title{font-family:var(--font-display);font-size:1.45rem;font-weight:900;color:#FFF;
  position:relative;z-index:1;}
.auth-title span{color:var(--gold-light);}
.auth-sub{font-size:.76rem;font-weight:600;color:rgba(255,255,255,.6);margin-top:3px;
  letter-spacing:.09em;text-transform:uppercase;position:relative;z-index:1;}
.auth-body{padding:28px 32px 24px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:480px){.form-row{grid-template-columns:1fr;} .auth-body{padding:20px 18px;}}
.input-wrap{position:relative;}
.inp-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--maroon-mid);font-size:.85rem;pointer-events:none;transition:color .25s;}
.input-wrap:focus-within .inp-icon{color:var(--maroon);}
.form-control{padding-left:44px;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--maroon-mid);font-size:.85rem;}
.toggle-pw:hover{color:var(--maroon);}
.pw-bar{height:4px;border-radius:2px;background:rgba(128,0,0,.1);overflow:hidden;margin-top:6px;}
.pw-fill{height:100%;border-radius:2px;width:0;transition:width .4s ease,background .4s ease;}
.pw-hint{font-size:.7rem;font-weight:600;color:var(--text-muted);margin-top:3px;}
.req{color:var(--maroon-light);}
.btn-submit{width:100%;background:linear-gradient(135deg,var(--maroon-deep),var(--maroon),var(--maroon-mid));
  border:none;color:#FFF;font-family:var(--font-body);font-size:1rem;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;padding:13px;border-radius:10px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px;
  box-shadow:0 4px 18px rgba(128,0,0,.3);position:relative;overflow:hidden;margin-top:6px;transition:var(--transition);}
.btn-submit::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--gold-dark),var(--gold));opacity:0;transition:opacity .35s;}
.btn-submit:hover::before{opacity:1;} .btn-submit:hover{transform:translateY(-2px);}
.btn-submit span,.btn-submit i{position:relative;z-index:1;}
.btn-submit:hover span,.btn-submit:hover i{color:var(--maroon-deep);}
.auth-divider{display:flex;align-items:center;gap:12px;margin:18px 0;}
.auth-divider::before,.auth-divider::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,transparent,rgba(128,0,0,.2),transparent);}
.auth-divider span{font-size:.73rem;font-weight:700;color:var(--text-muted);letter-spacing:.08em;text-transform:uppercase;}
.auth-footer{text-align:center;font-size:.85rem;font-weight:600;color:var(--text-muted);}
.auth-footer a{color:var(--maroon);font-weight:700;text-decoration:none;}
.form-group{animation:fldIn .5s cubic-bezier(.4,0,.2,1) both;}
.form-group:nth-child(1){animation-delay:.08s}.form-group:nth-child(2){animation-delay:.14s}
.form-group:nth-child(3){animation-delay:.20s}.form-group:nth-child(4){animation-delay:.26s}
.form-group:nth-child(5){animation-delay:.32s}.form-group:nth-child(6){animation-delay:.38s}
@keyframes fldIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
</style>
</head>
<body>
<div class="orb orb-1"></div><div class="orb orb-2"></div><div class="orb orb-3"></div>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-emblem">🏛️</div>
      <div class="auth-title"><span>Skill</span>Edge</div>
      <div class="auth-sub">Create your free account</div>
    </div>
    <div class="gold-line"></div>
    <div class="auth-body">
      <?php if($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><?= h($error) ?></div><?php endif; ?>
      <?php if($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i><?= $success ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><i class="fas fa-user"></i>Username <span class="req">*</span></label>
            <div class="input-wrap"><i class="fas fa-user inp-icon"></i>
            <input type="text" name="name" class="form-control" placeholder="Your name" value="<?= $f['name'] ?>" required minlength="3"/>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-envelope"></i>Email <span class="req">*</span></label>
            <div class="input-wrap"><i class="fas fa-envelope inp-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= $f['email'] ?>" required/>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-lock"></i>Password <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-lock inp-icon"></i>
          <input type="password" name="password" class="form-control" id="pw1" placeholder="Min 8 chars, 1 upper, 1 num, 1 special" required/>
          <button type="button" class="toggle-pw" onclick="tp('pw1','e1')"><i class="fas fa-eye" id="e1"></i></button>
          </div>
          <div class="pw-bar"><div class="pw-fill" id="pwFill"></div></div>
          <div class="pw-hint" id="pwHint">Enter a strong password</div>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-lock"></i>Confirm Password <span class="req">*</span></label>
          <div class="input-wrap"><i class="fas fa-shield-halved inp-icon"></i>
          <input type="password" name="confirm" class="form-control" id="pw2" placeholder="Re-enter password" required/>
          <button type="button" class="toggle-pw" onclick="tp('pw2','e2')"><i class="fas fa-eye" id="e2"></i></button>
          </div>
        </div>
        <button type="submit" class="btn-submit ripple-btn">
          <i class="fas fa-user-plus"></i><span>Create Account</span>
        </button>
      </form>
      <div class="auth-divider"><span>already have an account?</span></div>
      <div class="auth-footer"><a href="login.php"><i class="fas fa-arrow-right-to-bracket"></i> Sign in instead</a></div>
    </div>
  </div>
</div>
<script src="assets/js/main.js"></script>
<script>
function tp(id,eid){const i=document.getElementById(id),e=document.getElementById(eid),s=i.type==='password';i.type=s?'text':'password';e.className=s?'fas fa-eye-slash':'fas fa-eye';}
const pw1=document.getElementById('pw1'),fill=document.getElementById('pwFill'),hint=document.getElementById('pwHint');
pw1.addEventListener('input',function(){
  const v=this.value,cols=['#e53935','#e53935','#FFA726','#66BB6A','#2e7d32'],lbs=['Too weak','Weak','Fair','Strong','Very strong'];
  let s=0; if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/\d/.test(v))s++;if(/[\W_]/.test(v))s++;if(v.length>=12)s++;
  fill.style.width=(s/5*100)+'%';fill.style.background=cols[s-1]||'#e0e0e0';
  hint.textContent=s?lbs[s-1]:'Enter a strong password';hint.style.color=cols[s-1]||'';
});
document.getElementById('pw2').addEventListener('input',function(){
  const m=this.value===pw1.value;
  this.style.borderColor=this.value.length>0?(m?'#2e7d32':'#e53935'):'';
});
</script>
</body>
</html>
