const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({ executablePath: '/usr/bin/chromium', args: ['--no-sandbox'] });
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const base = 'http://localhost:8080';
  const response = await page.request.get(base + '/sitemap.xml');
  const sitemap = await response.text();
  const routes = [...sitemap.matchAll(/<loc>(.*?)<\/loc>/g)].map(item => new URL(item[1]).pathname);
  if (routes.length !== 56 || routes.filter(route => route.startsWith('/en')).length !== 28) throw Error('Sitemap bilingue incomplet');
  const checkedLinks = new Set();
  for (const width of [320, 390, 768, 1024, 1280, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    for (const route of routes) {
      const response = await page.goto(base + route);
      if (response.status() !== 200) throw Error('Route ' + route);
      const english = route.startsWith('/en');
      if (await page.locator('html').getAttribute('lang') !== (english ? 'en' : 'fr')) throw Error('Langue ' + route);
      if (await page.locator('h1').count() !== 1) throw Error('H1 ' + route);
      if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw Error('Débordement ' + width + route);
      if ((await page.locator('footer').innerText()).includes('8 rue de Cergy')) throw Error('Adresse dans le footer');
      if (await page.locator('link[rel="canonical"]').getAttribute('href') !== 'http://localhost:8080' + route) throw Error('Canonical ' + route);
      for (const language of ['fr', 'en', 'x-default']) {
        if (await page.locator('link[hreflang="' + language + '"]').count() !== 1) throw Error('Hreflang ' + route);
      }
      if (width === 1440) {
        const links = await page.locator('a[href^="/"],form[action^="/"]').evaluateAll(items => items.map(item => item.getAttribute('href') || item.getAttribute('action')));
        for (const link of links) {
          const pathname = new URL(link, base).pathname;
          if (english && !pathname.startsWith('/en') && !await page.locator('.language-switch[href="' + link + '"]').count()) throw Error('Lien sort de la version anglaise : ' + route + ' -> ' + link);
          if (!checkedLinks.has(pathname)) {
            if ((await page.request.get(base + pathname)).status() !== 200) throw Error('Lien cassé ' + pathname);
            checkedLinks.add(pathname);
          }
        }
      }
    }
  }
  await page.goto(base + '/guides/chauffeur-prive-van-paris');
  await page.locator('.language-switch').click();
  await page.waitForURL('**/en/guides/private-van-chauffeur-paris');
  if (!(await page.locator('h1').innerText()).includes('Private VAN chauffeur')) throw Error('Guide anglais');
  await page.locator('.language-switch').click();
  await page.waitForURL('**/guides/chauffeur-prive-van-paris');
  await page.goto(base + '/en/destinations');
  await page.getByRole('button', { name: 'Family days out', exact: true }).click();
  if (await page.locator('.journey-card:visible').count() !== 2 || await page.locator('[data-destination-count]').innerText() !== '2 destinations to discover') throw Error('Filtre anglais');
  await page.goto(base + '/en/destinations/versailles');
  await page.getByRole('link', { name: 'Request my quotation', exact: true }).click();
  await page.waitForURL('**/en/contact?**');
  if (await page.locator('#subject').inputValue() !== 'Trip: Versailles' || await page.locator('#service').inputValue() !== 'Mise à disposition') throw Error('Préremplissage anglais');
  await page.goto(base + '/en');
  await page.setViewportSize({width:1440,height:1000});
  await page.screenshot({path:'/tmp/english-home.png',fullPage:true});
  await page.goto(base + '/en/guides/private-van-chauffeur-paris');
  await page.setViewportSize({width:390,height:844});
  await page.screenshot({path:'/tmp/english-guide-mobile.png'});
  if (errors.length) throw Error(errors.join('\n'));
  await browser.close();
  console.log('PASS: 56 pages × 6 largeurs, canonical/hreflang, liens, footer sans adresse, langues équivalentes, guides et filtres anglais, devis prérempli.');
})();
