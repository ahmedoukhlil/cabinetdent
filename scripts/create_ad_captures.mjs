import { chromium } from 'playwright-core';
import path from 'node:path';

const root = process.cwd();
const output = path.join(root, 'storage', 'app', 'publicite', 'captures');
const baseUrl = 'http://localhost:8000';
const browser = await chromium.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: true,
    args: ['--hide-scrollbars'],
});
const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });

await page.goto(`${baseUrl}/connexion`, { waitUntil: 'domcontentloaded' });
await page.fill('input[name="email"]', 'proprietaire@demo.test');
await page.fill('input[name="password"]', 'sysdental-demo');
await Promise.all([
    page.waitForURL(`${baseUrl}/`),
    page.click('button:has-text("Se connecter")'),
]);
await page.evaluate(() => {
    localStorage.setItem('sysdental-install-masque', '1');
    localStorage.setItem('sysdental-dossier-onglet', 'etat');
});

const capture = async (name, url, ready) => {
    await page.goto(`${baseUrl}${url}`, { waitUntil: 'domcontentloaded' });
    if (ready) await page.locator(ready).first().waitFor({ state: 'visible' });
    await page.waitForTimeout(700);
    await page.screenshot({ path: path.join(output, `${name}.png`) });
};

await capture('01-accueil', '/', 'h1');
await capture('02-agenda', '/agenda', 'text=Nouveau rendez-vous');
await capture('03-attente', '/salle-attente', 'h1');
await capture('04-patient', '/patients?fiche=2', 'text=Dossier dentaire');
await capture('05-dossier', '/patients/2/dossier', '#odonto-titre');
await capture('06-facturation', '/factures?filtre=toutes', 'h1');
await capture('07-statistiques', '/statistiques', 'h1');

await browser.close();
console.log(output);
