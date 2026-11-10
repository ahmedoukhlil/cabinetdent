import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const capturesDir = path.join(root, 'storage', 'app', 'publicite', 'captures');
const scenesDir = path.join(root, 'storage', 'app', 'publicite', 'scenes');
const logo = fs.readFileSync(path.join(root, 'public', 'icons', 'icon-192.png')).toString('base64');
const asDataUrl = (file) => `data:image/png;base64,${fs.readFileSync(path.join(capturesDir, file)).toString('base64')}`;

const scenes = [
    { kicker: 'GESTION DE CABINET DENTAIRE', title: 'Votre cabinet mérite mieux que le chaos.', subtitle: 'Centralisez votre activité et gagnez du temps, chaque jour.', intro: true },
    { kicker: 'PILOTEZ', title: 'Toute votre activité. Un seul écran.', subtitle: 'Les priorités du cabinet restent visibles immédiatement.', image: '01-accueil.png' },
    { kicker: 'PLANIFIEZ', title: 'Chaque rendez-vous, en quelques secondes.', subtitle: 'Agenda, créneaux disponibles et praticiens réunis.', image: '02-agenda.png' },
    { kicker: 'ACCUEILLEZ', title: 'Suivez chaque patient en temps réel.', subtitle: 'Attendu, en attente, en soins ou terminé.', image: '03-attente.png' },
    { kicker: 'SOIGNEZ', title: 'Un dossier dentaire visuel et précis.', subtitle: 'Odontogramme, plan de traitement et historique.', image: '05-dossier.png' },
    { kicker: 'ENCAISSEZ', title: 'Facturez sans friction.', subtitle: 'Patient, assurance et paiements clairement séparés.', image: '06-facturation.png' },
    { kicker: 'ANALYSEZ', title: 'Décidez avec des chiffres clairs.', subtitle: 'Suivez le facturé, l’encaissé et le bilan du cabinet.', image: '07-statistiques.png' },
    { kicker: 'SYSDENTAL', title: 'Votre cabinet, simplement organisé.', subtitle: 'Demandez votre démonstration.', outro: true },
];

const browser = await chromium.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: true });
const page = await browser.newPage({ viewport: { width: 1080, height: 1920 }, deviceScaleFactor: 1 });

for (let index = 0; index < scenes.length; index++) {
    const scene = scenes[index];
    const image = scene.image ? asDataUrl(scene.image) : null;
    const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
        *{box-sizing:border-box} body{margin:0;width:1080px;height:1920px;overflow:hidden;font-family:"Segoe UI",Arial,sans-serif;color:white;background:
        radial-gradient(circle at 82% 16%,rgba(34,137,211,.38),transparent 28%),radial-gradient(circle at 15% 85%,rgba(14,165,166,.2),transparent 31%),linear-gradient(145deg,#071827,#0a3152 65%,#0f4c81)}
        .orb{position:absolute;border-radius:999px;border:1px solid rgba(255,255,255,.13)} .o1{width:560px;height:560px;right:-280px;top:300px}.o2{width:310px;height:310px;left:-150px;bottom:180px}
        .top{position:absolute;left:72px;right:72px;top:88px;display:flex;align-items:center;justify-content:space-between}.brand{display:flex;align-items:center;gap:18px;font-size:28px;font-weight:700}.brand img{width:58px;height:58px;border-radius:16px}.counter{font-size:22px;color:#a9c9df;letter-spacing:2px}
        .copy{position:absolute;left:72px;right:72px;top:${scene.intro || scene.outro ? 490 : 230}px}.kicker{font-size:24px;font-weight:700;letter-spacing:4px;color:#77d7e2}.title{margin:26px 0 22px;max-width:930px;font-size:${scene.intro || scene.outro ? 78 : 64}px;line-height:1.04;letter-spacing:-2px}.subtitle{max-width:850px;font-size:30px;line-height:1.35;color:#c6ddeb}
        .device{position:absolute;left:50px;right:50px;top:610px;padding:16px;border-radius:34px;background:linear-gradient(150deg,rgba(255,255,255,.98),rgba(221,235,245,.94));box-shadow:0 45px 100px rgba(0,0,0,.34),inset 0 0 0 1px rgba(255,255,255,.8)}.device:before{content:"";display:block;width:80px;height:7px;margin:0 auto 14px;border-radius:8px;background:#9ab3c4}.device img{display:block;width:100%;border-radius:18px}
        .intro-logo{position:absolute;left:72px;top:265px;width:150px;height:150px;border-radius:38px;box-shadow:0 30px 65px rgba(0,0,0,.25)}
        .outro-card{position:absolute;left:72px;right:72px;top:950px;padding:48px;border:1px solid rgba(255,255,255,.2);border-radius:32px;background:rgba(255,255,255,.1);backdrop-filter:blur(14px)}.cta{display:inline-flex;margin-top:26px;padding:22px 34px;border-radius:18px;background:#fff;color:#0f4c81;font-size:28px;font-weight:700}
        .progress{position:absolute;left:72px;right:72px;bottom:72px;height:7px;border-radius:8px;background:rgba(255,255,255,.18);overflow:hidden}.progress span{display:block;width:${((index+1)/scenes.length)*100}%;height:100%;background:#77d7e2}
    </style></head><body><div class="orb o1"></div><div class="orb o2"></div><div class="top"><div class="brand"><img src="data:image/png;base64,${logo}">Sysdental</div><div class="counter">${String(index+1).padStart(2,'0')} / ${String(scenes.length).padStart(2,'0')}</div></div>
    ${scene.intro ? `<img class="intro-logo" src="data:image/png;base64,${logo}">` : ''}
    <div class="copy"><div class="kicker">${scene.kicker}</div><h1 class="title">${scene.title}</h1><div class="subtitle">${scene.subtitle}</div></div>
    ${image ? `<div class="device"><img src="${image}"></div>` : ''}
    ${scene.outro ? `<div class="outro-card"><div style="font-size:26px;color:#c6ddeb">Agenda · Patients · Soins · Facturation · Statistiques</div><div class="cta">Demandez votre démonstration</div></div>` : ''}
    <div class="progress"><span></span></div></body></html>`;
    await page.setContent(html, { waitUntil: 'load' });
    await page.screenshot({ path: path.join(scenesDir, `${String(index + 1).padStart(2, '0')}.png`) });
}

await browser.close();
console.log(scenesDir);
