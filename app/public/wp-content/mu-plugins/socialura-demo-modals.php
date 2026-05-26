<?php
/**
 * Plugin Name: Socialura Demo Modals
 * Description: Demo-only login and payment/product modals for the VPS clone. No real payments/API calls.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action('wp_footer', function () {
    if ( is_admin() ) { return; }
    $success_url = home_url('/payment-successful/');
    ?>
<style id="socialura-demo-modals-css">
.sdm-lock{overflow:hidden}.sdm-overlay{position:fixed;inset:0;z-index:999999;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(15,15,20,.62);backdrop-filter:blur(4px)}.sdm-overlay.active{display:flex}.sdm-modal{width:min(760px,100%);max-height:92vh;overflow:auto;background:#fff;color:#222;border-radius:22px;box-shadow:0 24px 80px rgba(0,0,0,.35);position:relative;font-family:inherit}.sdm-login-modal{width:min(440px,100%)}.sdm-close{position:absolute;right:14px;top:12px;border:0!important;background:transparent!important;color:#555!important;font-size:28px;width:38px;height:38px;line-height:1;padding:0!important;box-shadow:none!important;cursor:pointer}.sdm-inner{padding:30px}.sdm-title{margin:0 42px 6px 0;font-size:28px;color:#1e293b}.sdm-subtitle{margin:0 0 22px;color:#64748b}.sdm-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:18px 0 22px}.sdm-card{position:relative;border:2px solid #eee!important;border-radius:16px!important;background:#fafafa!important;padding:22px 12px 32px!important;cursor:pointer;text-align:center;transition:.18s ease;color:#222!important;box-shadow:none!important}.sdm-card:hover,.sdm-card.selected{border-color:#ee2a7b!important;transform:translateY(-2px);box-shadow:0 8px 22px rgba(238,42,123,.16)!important;background:#fff7fb!important}.sdm-popular{position:absolute;top:-11px;left:50%;transform:translateX(-50%);white-space:nowrap;color:#fff;font-size:11px;font-weight:700;padding:5px 10px;border-radius:999px;background:linear-gradient(90deg,#ff6b35,#ee2a7b,#6228d7)}.sdm-discount{position:absolute;right:7px;bottom:7px;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;background:#111}.sdm-followers{font-size:22px;font-weight:800;color:#222;margin-bottom:8px}.sdm-price{font-size:18px;font-weight:800}.sdm-old{font-size:13px;color:#999;text-decoration:line-through;margin-left:6px}.sdm-button{width:100%;border:0!important;border-radius:999px!important;padding:15px 22px!important;color:#fff!important;font-weight:800!important;font-size:17px!important;cursor:pointer;background:linear-gradient(90deg,#ff6b35,#ee2a7b,#6228d7)!important;box-shadow:none!important}.sdm-button:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(238,42,123,.28)!important}.sdm-note{margin:14px 0 0;padding:11px 13px;background:#fff8e1;border:1px solid #ffe08a;color:#704d00;border-radius:12px;font-size:14px}.sdm-form{display:none;margin-top:18px;padding-top:0}.sdm-form.active{display:block}.sdm-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.sdm-field{margin-bottom:12px}.sdm-field label{display:block;font-weight:700;font-size:13px;margin-bottom:5px;color:#334155}.sdm-field input{width:100%;border:1px solid #ddd!important;border-radius:10px!important;padding:12px 14px!important;height:auto!important;color:#111!important;background:#fff!important}.sdm-summary{margin:0 0 14px;padding:12px 14px;border-radius:12px;background:#f8fafc;color:#334155}.sdm-success{display:none;text-align:center;padding:20px 4px 4px}.sdm-success.active{display:block}.sdm-check{width:70px;height:70px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#22c55e;color:#fff;font-size:38px;margin-bottom:12px}@media(max-width:560px){.sdm-inner{padding:24px 16px}.sdm-grid{grid-template-columns:1fr}.sdm-row{grid-template-columns:1fr}.sdm-title{font-size:23px}}
</style>

<div class="sdm-overlay" id="sdm-payment" aria-hidden="true">
  <div class="sdm-modal" role="dialog" aria-modal="true">
    <button class="sdm-close" type="button" aria-label="Close">×</button>
    <div class="sdm-inner">
      <div class="sdm-products-step">
        <h2 class="sdm-title">Choisissez votre pack</h2>
        <p class="sdm-subtitle">Sélectionnez un objectif d’abonnés puis continuez vers le paiement démo.</p>
        <div class="sdm-grid" id="sdm-grid"></div>
        <button class="sdm-button" type="button" id="sdm-continue">Continuer</button>
        <div class="sdm-note">Mode démo : aucun paiement réel ne sera traité. Vous pouvez utiliser de fausses informations.</div>
      </div>
      <form class="sdm-form" id="sdm-payment-form">
        <h2 class="sdm-title">Paiement démo</h2>
        <p class="sdm-summary" id="sdm-summary"></p>
        <div class="sdm-field"><label for="sdm-username">Profil TikTok ou Instagram</label><input id="sdm-username" type="text" placeholder="instagram.com/votreprofil" required></div>
        <div class="sdm-field"><label for="sdm-email">Email</label><input id="sdm-email" type="email" placeholder="demo@example.com" required></div>
        <div class="sdm-field"><label for="sdm-card">Numéro de carte</label><input id="sdm-card" type="text" value="4242 4242 4242 4242" required></div>
        <div class="sdm-row"><div class="sdm-field"><label for="sdm-exp">Expiration</label><input id="sdm-exp" type="text" value="12/34" required></div><div class="sdm-field"><label for="sdm-cvc">CVC</label><input id="sdm-cvc" type="text" value="123" required></div></div>
        <button class="sdm-button" type="submit">Payer en mode démo</button>
      </form>
      <div class="sdm-success" id="sdm-payment-success"><div class="sdm-check">✓</div><h2 class="sdm-title">Paiement démo réussi</h2><p class="sdm-subtitle">Commande enregistrée localement. Aucun débit réel n’a été effectué.</p><button class="sdm-button" type="button" id="sdm-finish">Voir la confirmation</button></div>
    </div>
  </div>
</div>

<div class="sdm-overlay" id="sdm-login" aria-hidden="true">
  <div class="sdm-modal sdm-login-modal" role="dialog" aria-modal="true">
    <button class="sdm-close" type="button" aria-label="Close">×</button>
    <div class="sdm-inner">
      <h2 class="sdm-title">Content de vous revoir</h2>
      <p class="sdm-subtitle">Veuillez saisir vos informations de connexion.</p>
      <form id="sdm-login-form">
        <div class="sdm-field"><label for="sdm-login-user">Nom d’utilisateur</label><input id="sdm-login-user" type="text" placeholder="Votre nom d’utilisateur" required></div>
        <div class="sdm-field"><label for="sdm-login-pass">Mot de passe</label><input id="sdm-login-pass" type="password" placeholder="Votre mot de passe" required></div>
        <button class="sdm-button" type="submit" style="margin-top:14px">S'identifier</button>
      </form>
      <div class="sdm-success" id="sdm-login-success"><div class="sdm-check">✓</div><h2 class="sdm-title">Connexion démo réussie</h2><p class="sdm-subtitle">Bienvenue sur votre espace démo.</p></div>
    </div>
  </div>
</div>

<script id="socialura-demo-modals-js">
(function(){
  const successUrl = <?php echo wp_json_encode($success_url); ?>;
  const products=[['500',5,10],['1 000',10,20,true],['2 500',20,40],['5 000',35,70],['10 000',50,100],['20 000',80,160],['35 000',120,240],['50 000',150,300],['100 000',300,600]];
  const pay=document.getElementById('sdm-payment'), login=document.getElementById('sdm-login'), grid=document.getElementById('sdm-grid');
  let selected=1;
  function openModal(m){m.classList.add('active');m.setAttribute('aria-hidden','false');document.documentElement.classList.add('sdm-lock')}
  function closeModal(m){m.classList.remove('active');m.setAttribute('aria-hidden','true');document.documentElement.classList.remove('sdm-lock')}
  function openPayment(){
    const input=document.getElementById('username'); if(input && input.value) localStorage.setItem('username', input.value);
    const stored=localStorage.getItem('username') || input?.value || '';
    const user=document.getElementById('sdm-username'); if(user) user.value=stored;
    pay.querySelector('.sdm-products-step').style.display=''; document.getElementById('sdm-payment-form').classList.remove('active'); document.getElementById('sdm-payment-success').classList.remove('active');
    openModal(pay);
  }
  function openLogin(){document.getElementById('sdm-login-form').style.display='';document.getElementById('sdm-login-success').classList.remove('active');openModal(login)}
  grid.innerHTML=products.map((p,i)=>`<button type="button" class="sdm-card ${p[3]?'selected':''}" data-i="${i}">${p[3]?'<span class="sdm-popular">Le plus populaire</span>':''}<span class="sdm-discount">-50%</span><div class="sdm-followers">+${p[0]}</div><span class="sdm-price">${p[1]}€</span><span class="sdm-old">${p[2]}€</span></button>`).join('');
  grid.addEventListener('click',e=>{const c=e.target.closest('.sdm-card'); if(!c)return; selected=Number(c.dataset.i); grid.querySelectorAll('.sdm-card').forEach(x=>x.classList.remove('selected')); c.classList.add('selected')});
  document.getElementById('sdm-continue').addEventListener('click',()=>{const p=products[selected]; pay.querySelector('.sdm-products-step').style.display='none'; document.getElementById('sdm-summary').textContent=`Pack sélectionné : +${p[0]} abonnés — ${p[1]}€ (démo)`; document.getElementById('sdm-payment-form').classList.add('active')});
  document.getElementById('sdm-payment-form').addEventListener('submit',e=>{e.preventDefault(); const p=products[selected]; localStorage.setItem('socialura_demo_order',JSON.stringify({demo:true,followers:p[0],price:p[1],username:document.getElementById('sdm-username').value,email:document.getElementById('sdm-email').value,createdAt:new Date().toISOString()})); e.currentTarget.classList.remove('active'); document.getElementById('sdm-payment-success').classList.add('active')});
  document.getElementById('sdm-finish').addEventListener('click',()=>{location.href=successUrl});
  document.getElementById('sdm-login-form').addEventListener('submit',e=>{e.preventDefault(); e.currentTarget.style.display='none'; document.getElementById('sdm-login-success').classList.add('active')});
  document.querySelectorAll('.sdm-overlay').forEach(m=>{m.querySelector('.sdm-close').addEventListener('click',()=>closeModal(m));m.addEventListener('click',e=>{if(e.target===m)closeModal(m)})});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal(pay);closeModal(login)}});
  function attachHeroUsernamePopup(){
    const usernameInput = document.querySelector('input#username[placeholder="@johndoe"], input#username, input[placeholder="@johndoe"]');
    if(!usernameInput) return;
    const wrap = usernameInput.closest('.username-form') || usernameInput.parentElement;
    const btn = (wrap && wrap.querySelector('button, a, input[type="submit"], input[type="button"]')) || document.querySelector('.hero-form-submit-button');
    if(btn){
      if(btn.tagName === 'BUTTON') btn.setAttribute('type','button');
      btn.setAttribute('data-socialura-demo-payment-trigger','1');
      btn.onclick = function(ev){
        if(ev){ ev.preventDefault(); ev.stopPropagation(); }
        if(usernameInput.value) localStorage.setItem('username', usernameInput.value);
        openPayment();
        return false;
      };
    }
    usernameInput.addEventListener('keydown', function(ev){
      if(ev.key === 'Enter'){
        ev.preventDefault();
        if(usernameInput.value) localStorage.setItem('username', usernameInput.value);
        openPayment();
      }
    });
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attachHeroUsernamePopup); else attachHeroUsernamePopup();
  setTimeout(attachHeroUsernamePopup, 500);
  setTimeout(attachHeroUsernamePopup, 1500);

  document.addEventListener('click',function(e){
    const explicitHeroBtn = e.target.closest('[data-socialura-demo-payment-trigger="1"], .username-form button, .hero-form-submit-button');
    if(explicitHeroBtn){
      e.preventDefault(); e.stopPropagation();
      const usernameInput = document.querySelector('input#username[placeholder="@johndoe"], input#username, input[placeholder="@johndoe"]');
      if(usernameInput && usernameInput.value) localStorage.setItem('username', usernameInput.value);
      openPayment(); return;
    }
    const a=e.target.closest('a,button'); if(!a || a.closest('.sdm-overlay')) return;
    const text=(a.textContent||'').trim().toLowerCase();
    if(text.includes('mon compte') || a.closest('#menu-item-724,.menu-item-724')){e.preventDefault();e.stopPropagation();openLogin();return;}
    if(a.matches('.continue-button,.pricing-card,[class*="asp-attach-product"]') || /^(continue|continuer|achetez maintenant|acheter maintenant)$/.test(text)){e.preventDefault();e.stopPropagation();openPayment();return;}
  }, true);
  window.socialuraOpenPayment=openPayment; window.socialuraOpenLogin=openLogin; window.socialuraAttachHeroUsernamePopup=attachHeroUsernamePopup;
})();
</script>
    <?php
}, 9999);
