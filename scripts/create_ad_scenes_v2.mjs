import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const captures = path.join(root, 'storage', 'app', 'publicite', 'captures');
const output = path.join(root, 'storage', 'app', 'publicite', 'scenes-v2');
fs.mkdirSync(output, { recursive: true });
const data = (file) => `data:image/png;base64,${fs.readFileSync(path.join(captures, file)).toString('base64')}`;
const logo = `data:image/png;base64,${fs.readFileSync(path.join(root, 'public', 'icons', 'icon-192.png')).toString('base64')}`;

const scenes = [
    { word: 'RÉINVENTÉ.', pre: 'LE CABINET.', sub: 'Plus simple. Plus rapide. Plus connecté.', intro: true },
    { word: 'CENTRALISEZ.', pre: 'TOUT COMMENCE ICI.', sub: 'Votre journée et vos priorités sur un seul écran.', desktop: '01-accueil.png' },
    { word: 'PLANIFIEZ.', pre: 'EN QUELQUES CLICS.', sub: 'Agenda, créneaux et praticiens parfaitement synchronisés.', desktop: '02-agenda.png' },
    { word: 'SUIVEZ.', pre: 'EN TEMPS RÉEL.', sub: 'De l’arrivée du patient jusqu’à la fin des soins.', desktop: '03-attente.png' },
    { word: 'SOIGNEZ.', pre: 'AVEC PRÉCISION.', sub: 'Un odontogramme clair et un dossier clinique complet.', desktop: '05-dossier.png' },
    { word: 'ENCAISSEZ.', pre: 'SANS FRICTION.', sub: 'Facturation, assurances et paiements maîtrisés.', desktop: '06-facturation.png' },
    { word: 'PILOTEZ.', pre: 'AVEC LES BONS CHIFFRES.', sub: 'Visualisez immédiatement la performance du cabinet.', desktop: '07-statistiques.png' },
    { word: 'SUR PC.', pre: 'CONFORT MAXIMAL.', sub: 'Une vue complète pour travailler efficacement au cabinet.', desktop: '01-accueil.png', pc: true },
    { word: 'SUR MOBILE.', pre: 'TOUJOURS À PORTÉE.', sub: 'Retrouvez les fonctions essentielles sur votre téléphone.', mobile: 'mobile-accueil.png' },
    { word: 'PARTOUT.', pre: 'UNE SEULE APPLICATION.', sub: 'Sysdental — votre cabinet, simplement organisé.', desktop: '05-dossier.png', mobile: 'mobile-dossier.png', outro: true },
];

const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
const page = await browser.newPage({ viewport: { width: 1080, height: 1920 } });

for (let i = 0; i < scenes.length; i++) {
    const s = scenes[i];
    const desktop = s.desktop ? data(s.desktop) : '';
    const mobile = s.mobile ? data(s.mobile) : '';
    const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
      *{box-sizing:border-box}body{margin:0;width:1080px;height:1920px;overflow:hidden;font-family:"Segoe UI",Arial,sans-serif;color:#fff;background:#031523}
      body:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 92% 10%,rgba(25,126,200,.55),transparent 33%),radial-gradient(circle at 8% 92%,rgba(12,184,188,.24),transparent 35%),linear-gradient(145deg,#020d16,#073050 68%,#0f4c81)}
      .grain{position:absolute;inset:0;opacity:.12;background-image:repeating-linear-gradient(115deg,transparent 0 38px,rgba(255,255,255,.08) 39px,transparent 40px)}
      .beam{position:absolute;width:1200px;height:240px;left:-500px;top:790px;transform:rotate(-24deg);background:linear-gradient(90deg,transparent,rgba(78,213,229,.14),transparent);filter:blur(8px)}
      .top{position:absolute;left:64px;right:64px;top:62px;display:flex;justify-content:space-between;align-items:center}.brand{display:flex;align-items:center;gap:16px;font-size:27px;font-weight:800}.brand img{width:54px;height:54px;border-radius:15px}.count{font-size:20px;letter-spacing:3px;color:#9ec8df}
      .copy{position:absolute;left:64px;right:64px;top:${s.intro ? 450 : 190}px}.pre{font-size:24px;font-weight:800;letter-spacing:5px;color:#6de2ec}.word{margin:18px 0 20px;font-size:${s.intro ? 104 : 86}px;line-height:.94;letter-spacing:-4px;text-transform:uppercase}.sub{max-width:880px;font-size:29px;line-height:1.35;color:#c5ddeb}
      .slash{position:absolute;left:64px;top:${s.intro ? 350 : 150}px;width:120px;height:8px;border-radius:9px;background:#6de2ec;box-shadow:0 0 35px #4ccedc}
      .laptop{position:absolute;left:42px;right:42px;top:600px;padding:18px 18px 30px;border-radius:30px;background:linear-gradient(150deg,#fff,#cbdce8);box-shadow:0 55px 120px rgba(0,0,0,.5)}.laptop:before{content:"";display:block;width:76px;height:7px;margin:0 auto 14px;border-radius:9px;background:#91acbd}.laptop img{display:block;width:100%;border-radius:15px}.laptop:after{content:"";position:absolute;left:130px;right:130px;bottom:-24px;height:26px;border-radius:0 0 45px 45px;background:linear-gradient(#b8cad6,#eef5f8)}
      .phone{position:absolute;top:${s.outro ? 790 : 650}px;${s.outro ? 'right:70px;width:335px' : 'left:300px;width:480px'};padding:18px 13px 24px;border-radius:58px;background:linear-gradient(145deg,#eaf3f7,#94acba);box-shadow:0 55px 120px rgba(0,0,0,.55)}.phone:before{content:"";display:block;width:115px;height:19px;margin:0 auto 14px;border-radius:20px;background:#092238}.phone img{display:block;width:100%;height:${s.outro ? 720 : 1020}px;object-fit:cover;object-position:top;border-radius:38px}
      .dual .laptop{left:45px;right:250px;top:770px;padding:13px 13px 22px}.dual .laptop img{border-radius:11px}.dual .laptop:after{left:90px;right:90px;bottom:-18px;height:20px}
      .introMark{position:absolute;left:64px;top:245px;width:150px;height:150px;border-radius:38px;box-shadow:0 35px 90px rgba(0,0,0,.5)}
      .cta{position:absolute;left:64px;bottom:160px;display:inline-flex;padding:22px 34px;border-radius:18px;background:#fff;color:#0f4c81;font-size:28px;font-weight:800;box-shadow:0 25px 55px rgba(0,0,0,.28)}
      .timeline{position:absolute;left:64px;right:64px;bottom:65px;height:7px;border-radius:9px;background:rgba(255,255,255,.16);overflow:hidden}.timeline span{display:block;width:${((i+1)/scenes.length)*100}%;height:100%;background:#6de2ec;box-shadow:0 0 18px #6de2ec}
    </style></head><body><div class="grain"></div><div class="beam"></div><div class="top"><div class="brand"><img src="${logo}">Sysdental</div><div class="count">${String(i+1).padStart(2,'0')} / ${scenes.length}</div></div><div class="slash"></div>
      ${s.intro ? `<img class="introMark" src="${logo}">` : ''}<div class="copy"><div class="pre">${s.pre}</div><h1 class="word">${s.word}</h1><div class="sub">${s.sub}</div></div>
      ${desktop && mobile ? `<div class="dual"><div class="laptop"><img src="${desktop}"></div><div class="phone"><img src="${mobile}"></div></div>` : desktop ? `<div class="laptop"><img src="${desktop}"></div>` : mobile ? `<div class="phone"><img src="${mobile}"></div>` : ''}
      ${s.outro ? `<div class="cta">Demandez votre démonstration</div>` : ''}<div class="timeline"><span></span></div></body></html>`;
    await page.setContent(html, { waitUntil: 'load' });
    await page.screenshot({ path: path.join(output, `${String(i + 1).padStart(2, '0')}.png`) });
}

await browser.close();
console.log(output);
