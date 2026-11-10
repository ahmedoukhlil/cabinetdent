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
const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true,
    userAgent: 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/123 Mobile Safari/537.36',
});
const page = await context.newPage();

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

await capture('mobile-accueil', '/', 'h1');
await capture('mobile-agenda', '/agenda', 'text=Nouveau rendez-vous');
await capture('mobile-patient', '/patients?fiche=2', 'text=Dossier dentaire');
await capture('mobile-dossier', '/patients/2/dossier', '#odonto-titre');

await browser.close();
console.log(output);
