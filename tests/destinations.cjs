const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({ executablePath: '/usr/bin/chromium', args: ['--no-sandbox'] });
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const base = 'http://localhost:8080';
  await page.goto(base + '/destinations');
  if (await page.locator('.journey-card').count() !== 12) throw Error('12 destinations attendues');
  const routes = await page.locator('.journey-card h3 a').evaluateAll(items => items.map(item => item.getAttribute('href')));
  for (const image of await page.locator('.journey-card img').all()) {
    await image.scrollIntoViewIfNeeded();
    await image.evaluate(item => item.decode());
    if (!await image.evaluate(item => item.naturalWidth > 0 && item.currentSrc.includes('?v='))) throw Error('Image absente ou non versionnée');
  }
  await page.evaluate(() => scrollTo(0, 0));
  await page.screenshot({ path: '/tmp/destinations-desktop.png', fullPage: true });
  for (const width of [320, 390, 640, 768, 1024, 1200, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of ['/destinations', ...routes]) {
      const response = await page.goto(base + route);
      if (response.status() !== 200 || await page.locator('h1').count() !== 1) throw Error('Route invalide : ' + route);
      if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw Error('Débordement : ' + route + ', ' + width);
      if (route !== '/destinations') {
        await page.locator('.journey-detail-photo img').evaluate(item => item.decode());
        const active = page.locator('#main-nav a[aria-current="page"]');
        if (await active.getAttribute('href') !== '/destinations') throw Error('Menu actif incorrect');
      }
    }
  }
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(base + '/destinations');
  for (const [label, count] of [['Paris', 2], ['Châteaux & jardins', 3], ['Vignobles', 2], ['Grandes escapades', 3], ['En famille', 2], ['Toutes les envies', 12]]) {
    const button = page.getByRole('button', { name: label, exact: true });
    await button.focus();
    await page.keyboard.press('Enter');
    if (await page.locator('.journey-card:visible').count() !== count || await button.getAttribute('aria-pressed') !== 'true') throw Error('Filtre incorrect : ' + label);
  }
  await page.evaluate(() => scrollTo(0, 0));
  await page.screenshot({ path: '/tmp/destinations-mobile-top.png' });
  await page.goto(base + '/destinations/versailles');
  await page.getByRole('link', { name: 'Demander mon devis' }).click();
  if (await page.locator('#subject').inputValue() !== 'Excursion : Versailles' || await page.locator('#service').inputValue() !== 'Mise à disposition') throw Error('Demande non préremplie');
  await page.goto(base + '/contact?destination=%3Cscript%3Ealert(1)%3C/script%3E');
  if (await page.locator('#subject').inputValue() !== 'Excursion : <script>alert(1)</script>') throw Error('Préremplissage altéré');
  const noJs = await browser.newContext({ javaScriptEnabled: false });
  const fallback = await noJs.newPage();
  await fallback.goto(base + '/destinations');
  if (await fallback.locator('.journey-card:visible').count() !== 12 || await fallback.locator('.journey-filters').isVisible()) throw Error('Navigation sans JavaScript');
  if (errors.length) throw Error(errors.join('\n'));
  await browser.close();
  console.log('PASS: 12 fiches et images versionnées ; 7 largeurs ; filtres clavier ; menu actif ; devis prérempli et échappé ; accès sans JavaScript.');
})();
