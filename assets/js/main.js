
document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".navlinks");
  if (toggle && nav) toggle.addEventListener("click", () => nav.classList.toggle("open"));

  const path = location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".navlinks a").forEach(a => {
    if (a.getAttribute("href") === path) a.classList.add("active");
  });

  document.querySelectorAll("[data-year]").forEach(el => el.textContent = new Date().getFullYear());

  document.querySelectorAll('input[name="ts"]').forEach(inp => {
    inp.value = String(Math.floor(Date.now() / 1000));
  });


  // Production forms: AJAX submission with a branded success popup.
  document.querySelectorAll('form[data-bdj-ajax="success-popup"]').forEach(form => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!form.reportValidity()) return;

      const submit = form.querySelector('button[type="submit"]');
      const originalLabel = submit ? submit.innerHTML : '';
      if (submit) {
        submit.disabled = true;
        submit.classList.add('bdj-form-submit-loading');
        submit.innerHTML = 'Envoi en cours…';
      }

      const ts = form.querySelector('input[name="ts"]');
      if (ts) ts.value = String(Math.floor(Date.now() / 1000));

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json, text/html;q=0.9'
          },
          credentials: 'same-origin'
        });

        const contentType = response.headers.get('content-type') || '';
        const raw = await response.text();
        let payload = null;
        if (contentType.includes('application/json')) {
          try { payload = JSON.parse(raw); } catch (_) {}
        }

        if (!response.ok || (payload && payload.ok === false)) {
          let message = (payload && payload.message) || 'Votre demande n\'a pas pu être envoyée. Merci de vérifier les informations saisies et de réessayer.';
          if (!payload) {
            try {
              const doc = new DOMParser().parseFromString(raw, 'text/html');
              const list = [...doc.querySelectorAll('li')].map(el => el.textContent.trim()).filter(Boolean);
              const paragraph = doc.querySelector('.notice p');
              if (list.length) message = list.join(' ');
              else if (paragraph && paragraph.textContent.trim()) message = paragraph.textContent.trim();
            } catch (_) {}
          }
          showBdjFormError(message, form);
          return;
        }

        const successTitle = (payload && payload.title) || form.dataset.successTitle || 'Votre demande a été envoyée avec succès !';
        const successMessage = (payload && payload.message) || form.dataset.successMessage || 'Votre demande a bien été transmise à BDJ Consulting. Un e-mail de confirmation a été envoyé à votre adresse e-mail.';
        showBdjFormSuccess(successTitle, successMessage, () => {
          form.reset();
          if (ts) ts.value = String(Math.floor(Date.now() / 1000));
          const anchor = form.closest('section[id]');
          if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
      } catch (_) {
        showBdjFormError('Une erreur technique est survenue. Vérifiez votre connexion puis réessayez.', form);
      } finally {
        if (submit) {
          submit.disabled = false;
          submit.classList.remove('bdj-form-submit-loading');
          submit.innerHTML = originalLabel;
        }
      }
    });
  });

  function showBdjFormSuccess(title, message, onClose) {
    const old = document.getElementById('bdj-form-success-modal');
    if (old) old.remove();

    const modal = document.createElement('div');
    modal.id = 'bdj-form-success-modal';
    modal.className = 'bdj-form-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'bdj-form-success-title');
    modal.innerHTML = `
      <div class="bdj-form-modal__card">
        <div class="bdj-form-modal__icon" aria-hidden="true">✓</div>
        <h2 class="bdj-form-modal__title" id="bdj-form-success-title"></h2>
        <p class="bdj-form-modal__message"></p>
        <button type="button" class="bdj-form-modal__close">Fermer</button>
        <div class="bdj-form-modal__progress" aria-hidden="true"><span></span></div>
      </div>`;
    modal.querySelector('.bdj-form-modal__title').textContent = title;
    modal.querySelector('.bdj-form-modal__message').textContent = message;
    document.body.appendChild(modal);

    const close = () => {
      modal.classList.remove('is-visible');
      window.setTimeout(() => {
        modal.remove();
        if (typeof onClose === 'function') onClose();
      }, 220);
    };
    modal.querySelector('.bdj-form-modal__close').addEventListener('click', close);
    modal.addEventListener('click', (ev) => { if (ev.target === modal) close(); });
    document.addEventListener('keydown', function esc(ev){
      if (ev.key === 'Escape') {
        close();
        document.removeEventListener('keydown', esc);
      }
    }, { once: true });

    requestAnimationFrame(() => modal.classList.add('is-visible'));
    window.setTimeout(() => {
      if (document.body.contains(modal)) close();
    }, 4000);
  }

  function showBdjFormError(message, sourceForm) {
    const old = document.getElementById('bdj-form-error-message');
    if (old) old.remove();
    const box = document.createElement('div');
    box.id = 'bdj-form-error-message';
    box.setAttribute('role', 'alert');
    box.style.cssText = 'margin:0 0 18px;padding:14px 16px;border-radius:10px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;line-height:1.5;font-weight:600;';
    box.textContent = message;
    const form = sourceForm || document.querySelector('form[data-bdj-ajax="success-popup"]');
    if (form) {
      form.parentNode.insertBefore(box, form);
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  document.querySelectorAll("form[data-demo]").forEach(form => {
    form.addEventListener("submit", e => {
      e.preventDefault();
      const msg = form.querySelector(".form-message");
      if (msg) {
        msg.textContent = "Merci. Votre demande a bien été préparée. Connectez ce formulaire à votre CRM ou à votre adresse e-mail avant la mise en production.";
        msg.style.display = "block";
      }
    });
  });
});

/* BDJ Consulting — V5 robust customer-centric carousel */
document.addEventListener('DOMContentLoaded', function () {
  const carousel = document.querySelector('[data-carousel]');
  if (!carousel) return;

  const track = carousel.querySelector('.customer-track');
  const slides = [...carousel.querySelectorAll('.customer-slide')];
  const dots = [...carousel.querySelectorAll('.carousel-dot')];
  const prev = carousel.querySelector('.carousel-prev');
  const next = carousel.querySelector('.carousel-next');

  if (!track || slides.length === 0) return;

  let current = 0;
  let timer = null;
  const INTERVAL = 5500;

  function render(index) {
    current = ((index % slides.length) + slides.length) % slides.length;
    track.style.transform = `translate3d(-${current * 100}%,0,0)`;
    track.style.transition = 'transform 600ms cubic-bezier(.22,.61,.36,1)';
    dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
  }

  function stop() {
    if (timer !== null) {
      window.clearInterval(timer);
      timer = null;
    }
  }

  function start() {
    stop();
    timer = window.setInterval(() => render(current + 1), INTERVAL);
  }

  function restart() {
    stop();
    start();
  }

  prev?.addEventListener('click', () => {
    render(current - 1);
    restart();
  });

  next?.addEventListener('click', () => {
    render(current + 1);
    restart();
  });

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      render(i);
      restart();
    });
  });

  carousel.addEventListener('mouseenter', stop);
  carousel.addEventListener('mouseleave', start);

  // Keyboard accessibility
  carousel.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      render(current - 1);
      restart();
    }
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      render(current + 1);
      restart();
    }
  });
  carousel.setAttribute('tabindex', '0');

  // Mobile swipe
  let touchStartX = null;
  carousel.addEventListener('touchstart', e => {
    touchStartX = e.touches[0].clientX;
    stop();
  }, {passive:true});

  carousel.addEventListener('touchend', e => {
    if (touchStartX === null) return;
    const delta = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(delta) > 45) render(delta < 0 ? current + 1 : current - 1);
    touchStartX = null;
    start();
  }, {passive:true});

  // If a Pexels image fails, fall back to another known diverse call-center image.
  carousel.querySelectorAll('img').forEach(img => {
    img.addEventListener('error', function () {
      const fallback = 'https://images.pexels.com/photos/8867249/pexels-photo-8867249.jpeg?auto=compress&cs=tinysrgb&w=1200';
      if (this.src !== fallback) this.src = fallback;
    });
  });

  render(0);
  start();
});


/* ==========================================================
   BDJ V6.1.4 — COOKIE CONSENT
   ========================================================== */
document.addEventListener('DOMContentLoaded', function(){
  const box = document.getElementById('cookie-consent');
  if(!box) return;

  const key = 'bdj_cookie_choice_v2';
  let choice = null;
  try { choice = localStorage.getItem(key); } catch(e) {}

  if(!choice) box.classList.add('show');

  function save(value){
    try { localStorage.setItem(key, value); } catch(e) {}
    box.classList.remove('show');
  }

  const accept = document.getElementById('cookie-accept');
  const settings = document.getElementById('cookie-settings');

  if(accept) accept.addEventListener('click', function(){
    save('accepted');
    if(window.BDJTracking) window.BDJTracking.load();
  });

  const refuse = document.getElementById('cookie-refuse');
  if(refuse) refuse.addEventListener('click', function(){
    save('refused');
  });

  if(settings) settings.addEventListener('click', function(){
    window.location.href = 'politique-confidentialite.html#cookies';
  });
});

/* ==========================================================
   WHATSAPP CHAT WIDGET — V2 (all enhancements)
   ========================================================== */
document.addEventListener('DOMContentLoaded', function(){
  var bubble = document.getElementById('wa-chat-bubble');
  var box = document.getElementById('wa-chat-box');
  var close = document.getElementById('wa-chat-close');
  var input = document.getElementById('wa-chat-input');
  var send = document.getElementById('wa-chat-send');
  var body = document.getElementById('wa-chat-body');
  var badge = document.getElementById('wa-chat-badge');
  if(!bubble || !box) return;

  var waNumber = '2250102440707';
  var userName = '';
  var lastQuestion = '';
  var unanswered = '';
  try { userName = localStorage.getItem('wa_user_name') || ''; } catch(e){}

  /* --- Business hours: Mon-Fri 08h-17h (Abidjan = GMT, UTC+0) --- */
  function isOnline(){
    var now = new Date();
    var day = now.getUTCDay();
    var hour = now.getUTCHours();
    return day >= 1 && day <= 5 && hour >= 8 && hour < 17;
  }

  /* --- Page-aware welcome messages --- */
  function getWelcomeMessage(){
    var path = location.pathname.split('/').pop() || 'index.html';
    var map = {
      'contact.html':'Besoin d\'un devis ? Parlons-en !',
      'solutions.html':'Intéressé par nos solutions ? Comment pouvons-nous vous aider ?',
      'carrieres.html':'Vous souhaitez rejoindre notre équipe ?',
      'a-propos.html':'Envie d\'en savoir plus sur BDJ Consulting ?',
      'secteurs.html':'Vous cherchez un partenaire pour votre secteur ?'
    };
    return map[path] || 'Comment pouvons-nous vous aider ?';
  }

  /* --- Default quick replies --- */
  var DEFAULT_REPLIES = [
    'Vos services',
    'Horaires et contact',
    'Je voudrais un devis',
    'Postuler maintenant',
    'Parler à un conseiller'
  ];

  /* ==========================================================
     BASE DE CONNAISSANCES — le bot répond avant tout transfert.
     Chaque entrée : k = mots-clés (normalisés), a = réponse.
     Pour enrichir : dupliquez une ligne { k:[...], a:'...' }.
     ========================================================== */
  var KNOWLEDGE = [
    { k:['service','services','solution','solutions','offre','prestation','activite','que faites','externalisation','relation client','centre d appel','call center'],
      a:'BDJ Consulting accompagne les entreprises dans l\'externalisation de leur relation client : service client entrant, campagnes sortantes et télévente, génération de leads, prise de rendez-vous, BPO/back-office et enquêtes de satisfaction.' },
    { k:['bpo','back office','saisie de donnees','traitement de donnees','gestion documentaire','support administratif'],
      a:'Notre offre BPO/back-office couvre la saisie et le traitement de données, la gestion documentaire, le support administratif et les tâches non stratégiques, afin de libérer vos équipes.' },
    { k:['telemarketing','televente','campagne sortante','appels sortants','prospection telephonique','vente par telephone','relance'],
      a:'Nous menons vos campagnes sortantes : télévente, relance, enquêtes et prise de rendez-vous, avec des scripts sur mesure et un suivi des résultats.' },
    { k:['lead','leads','prospect','prospection','acquisition','contact qualifie','contacts qualifies'],
      a:'Nous générons des leads qualifiés pour votre équipe commerciale : qualification par téléphone, prise de rendez-vous et transfert des contacts chauds selon vos critères.' },
    { k:['rendez vous','rdv','prise de rendez vous','agenda','planifier'],
      a:'Nos équipes prennent les rendez-vous pour vos commerciaux : appel, qualification puis planification directement dans votre agenda.' },
    { k:['enquete','enquetes','satisfaction','sondage','nps','qualite percue'],
      a:'Nous réalisons vos enquêtes de satisfaction et sondages téléphoniques (y compris la mesure du NPS), avec restitution et analyse des résultats.' },
    { k:['secteur','secteurs','telecom','banque','microfinance','assurance','e commerce','commerce en ligne','energie','sante','logistique'],
      a:'Nous intervenons notamment dans les télécoms, la banque et la microfinance, l\'assurance, l\'e-commerce, l\'énergie, la santé et les services. Voir le détail sur la page Nos secteurs.' },
    { k:['horaire','horaires','ouverture','ouvert','ferme','disponibilite','disponible','quand etes vous'],
      a:'Nos équipes sont disponibles du lundi au vendredi, de 8h00 à 17h00 (heure d\'Abidjan). Vous pouvez laisser votre question ici : nous y répondons dès la reprise.' },
    { k:['adresse','localisation','ou etes vous','ou se trouve','situe','bureau','locaux','cocody','faya palace'],
      a:'Nos bureaux sont situés à Faya Palace, Cocody, Abidjan (Côte d\'Ivoire).' },
    { k:['telephone','numero','appeler','joindre','num tel'],
      a:'Vous pouvez nous joindre au +225 01 02 44 07 07. Vous pouvez aussi continuer ici, je réponds à vos questions.' },
    { k:['email','mail','courriel','ecrire','adresse mail'],
      a:'Par e-mail : contact@bdj-consulting.net. Nous répondons dans les meilleurs délais pendant nos jours ouvrés.' },
    { k:['devis','tarif','tarifs','prix','cout','combien','budget','facturation','proposition commerciale'],
      a:'Chaque projet est chiffré sur mesure selon le volume d\'appels, les horaires et le périmètre. Décrivez votre besoin et je transmets pour l\'établissement d\'un devis.' },
    { k:['delai','temps de reponse','sous combien de temps','delais de reponse'],
      a:'Nous traitons les demandes dans les meilleurs délais pendant nos jours ouvrés. Pour une urgence, le téléphone ou WhatsApp est le plus rapide.' },
    { k:['recrutement','recrute','postuler','candidature','emploi','job','carriere','cv','stage','offre d emploi'],
      a:'Vous pouvez postuler depuis la page Carrières (carrieres.html#recrutement) ou nous envoyer votre CV. Les candidatures sont étudiées en continu.' },
    { k:['langue','langues','francais','anglais','english','bilingue'],
      a:'Nos équipes travaillent en français et en anglais.' },
    { k:['qui etes vous','a propos','presentation','entreprise','societe','bdj consulting'],
      a:'BDJ Consulting est un centre d\'appel et un partenaire d\'externalisation basé à Abidjan. Nous aidons les entreprises à gérer leur relation client et leurs opérations commerciales.' },
    { k:['partenaire','partenariat','collaboration','travailler avec vous'],
      a:'Nous étudions les partenariats avec plaisir. Expliquez-moi votre projet et je transmets à l\'équipe.' },
    { k:['site web','site internet','reseaux sociaux','linkedin','facebook','instagram','tiktok'],
      a:'Retrouvez toutes nos informations sur bdj-consulting.net et sur nos réseaux sociaux (Facebook, Instagram, LinkedIn, TikTok, YouTube).' },
    { k:['bonjour','salut','bonsoir','coucou','hello','ca va'],
      a:'Bonjour ! Comment puis-je vous aider ?' }
  ];

  /* --- Normaliser une chaîne (minuscules, sans accents ni ponctuation) --- */
  function norm(s){
    return (s || '').toString().toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
  }

  /* --- Trouver la meilleure réponse pour une question --- */
  function findAnswer(question){
    var nq = norm(question);
    if(!nq) return null;
    var best = null, bestScore = 0;
    KNOWLEDGE.forEach(function(entry){
      var score = 0;
      entry.k.forEach(function(kw){
        var nk = norm(kw);
        if(nk && nq.indexOf(nk) !== -1) score += nk.indexOf(' ') !== -1 ? 2 : 1;
      });
      if(score > bestScore){ bestScore = score; best = entry; }
    });
    return bestScore > 0 ? best : null;
  }

  /* --- Show badge on load --- */
  if(badge) badge.style.display = 'block';

  /* --- Toggle chat --- */
  bubble.addEventListener('click', function(){
    box.classList.toggle('open');
    if(box.classList.contains('open')){
      if(badge) badge.style.display = 'none';
      input.focus();
      if(body.children.length === 0) initChat();
    } else {
      if(badge) badge.style.display = 'block';
    }
  });

  if(close) close.addEventListener('click', function(){
    box.classList.remove('open');
    if(badge) badge.style.display = 'block';
  });

  /* --- Init chat --- */
  function initChat(){
    body.innerHTML = '';
    if(userName){
      showTyping(function(){
        addBotMessage('Bienvenue ' + userName + ' ! ' + getWelcomeMessage());
        showQuickReplies();
        updateStatus();
      });
    } else {
      showTyping(function(){
        addBotMessage('Bonjour ! Comment vous appelez-vous ?');
      });
    }
  }

  /* --- Typing animation --- */
  function showTyping(cb){
    var t = document.createElement('div');
    t.className = 'wa-typing';
    t.innerHTML = '<span></span><span></span><span></span>';
    body.appendChild(t);
    body.scrollTop = body.scrollHeight;
    setTimeout(function(){ t.remove(); if(cb) cb(); }, 1200);
  }

  /* --- Add bot message --- */
  function addBotMessage(text){
    var m = document.createElement('div');
    m.className = 'wa-chat-msg bot';
    m.textContent = text;
    body.appendChild(m);
    body.scrollTop = body.scrollHeight;
  }

  /* --- Show quick reply buttons --- */
  function showQuickReplies(list){
    var options = list || DEFAULT_REPLIES;
    var old = body.querySelector('.wa-quick-replies');
    if(old) old.remove();
    var c = document.createElement('div');
    c.className = 'wa-quick-replies';
    options.forEach(function(text){
      var b = document.createElement('button');
      b.className = 'wa-quick-btn';
      b.textContent = text;
      b.addEventListener('click', function(){
        c.remove();
        if(text === 'Postuler maintenant'){
          window.location.href = 'carrieres.html#recrutement';
          return;
        }
        if(text === 'Parler à un conseiller' || text === 'Transférer à un conseiller'){
          appendUser(text);
          input.value = '';
          escalate();
          return;
        }
        if(text === 'Autre question'){
          addBotMessage('Posez votre question, je suis là pour y répondre.');
          showQuickReplies();
          return;
        }
        handleQuestion(text);
      });
      c.appendChild(b);
    });
    body.appendChild(c);
    body.scrollTop = body.scrollHeight;
  }

  /* --- Update online/offline status --- */
  function updateStatus(){
    var el = document.querySelector('.wa-chat-header-info p');
    if(!el) return;
    if(isOnline()){
      el.textContent = 'En ligne';
      el.style.opacity = '';
    } else {
      el.textContent = 'Hors ligne';
      el.style.opacity = '.55';
    }
  }

  /* --- Append a user bubble --- */
  function appendUser(text){
    var m = document.createElement('div');
    m.className = 'wa-chat-msg user';
    m.textContent = text;
    body.appendChild(m);
    body.scrollTop = body.scrollHeight;
  }

  /* --- Answer locally first, then offer next steps --- */
  function handleQuestion(text){
    lastQuestion = text;
    appendUser(text);
    input.value = '';
    var found = findAnswer(text);
    showTyping(function(){
      if(found){
        addBotMessage(found.a);
        showQuickReplies(['Autre question', 'Parler à un conseiller']);
      } else {
        unanswered = text;
        addBotMessage('Je n\'ai pas encore la réponse exacte à cette question. Je peux la transmettre directement à un conseiller BDJ Consulting sur WhatsApp.');
        showQuickReplies(['Transférer à un conseiller', 'Vos services', 'Horaires et contact']);
      }
      updateStatus();
    });
  }

  /* --- Hand the conversation over to a human on WhatsApp --- */
  function escalate(){
    var payload = lastQuestion || unanswered || 'Demande de contact';
    if(unanswered && lastQuestion && unanswered !== lastQuestion) payload += '\n' + unanswered;
    trackLead();
    window.open('https://wa.me/' + waNumber + '?text=' + encodeURIComponent(buildMessage(payload)), '_blank');
  }

  /* --- Send message (name first, then questions) --- */
  function sendMessage(){
    var text = (input ? input.value : '').trim();
    if(!text) return;
    if(!userName){
      userName = text;
      try { localStorage.setItem('wa_user_name', userName); } catch(e){}
      appendUser(userName);
      input.value = '';
      showTyping(function(){
        addBotMessage('Bienvenue ' + userName + ' ! ' + getWelcomeMessage());
        showQuickReplies();
        updateStatus();
      });
      return;
    }
    handleQuestion(text);
  }

  /* --- Build a context-rich prefilled message --- */
  function buildMessage(text){
    var page = location.pathname.split('/').pop() || 'index.html';
    var lines = ['Bonjour BDJ Consulting,'];
    if(userName) lines.push('Nom : ' + userName);
    lines.push('Page : ' + page);
    lines.push('Demande : ' + text);
    return lines.join('\n');
  }

  /* --- Fire a conversion event (only if pixels loaded via consent) --- */
  function trackLead(){
    if(window.BDJTracking && window.BDJTracking.track){
      window.BDJTracking.track('generate_lead', { method: 'whatsapp', page: location.pathname });
    }
  }

  if(send) send.addEventListener('click', sendMessage);
  if(input) input.addEventListener('keydown', function(e){
    if(e.key === 'Enter') sendMessage();
  });
});
