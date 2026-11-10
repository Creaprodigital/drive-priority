const {chromium}=require('playwright');
const fs=require('fs');
(async()=>{const browser=await chromium.launch({executablePath:'/usr/bin/chromium',headless:true,args:['--no-sandbox']});const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(String(e)));await page.goto('http://localhost:8080/');await page.screenshot({path:'/tmp/driver-desktop.png',fullPage:true});const links=await page.locator('a[href^="/"]').evaluateAll(a=>[...new Set(a.map(x=>x.getAttribute('href').split('?')[0]))]);for(const link of links){const r=await page.goto('http://localhost:8080'+link);if(r.status()!==200)throw Error(link+' status '+r.status());if(await page.locator('h1').count()!==1)throw Error(link+' h1');}await page.goto('http://localhost:8080/faq');await page.locator('summary').first().focus();await page.keyboard.press('Enter');if(!await page.locator('details').first().getAttribute('open').then(x=>x!==null))throw Error('accordion');await page.setViewportSize({width:390,height:844});for(const route of ['/',...links]){await page.goto('http://localhost:8080'+route);const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);if(overflow)throw Error('Mobile overflow '+route);}await page.goto('http://localhost:8080/');await page.locator('.menu-toggle').click();await page.getByRole('navigation',{name:'Navigation principale'}).getByRole('link',{name:'Contact',exact:true}).click();await page.waitForURL('**/contact');await page.screenshot({path:'/tmp/driver-mobile-contact.png',fullPage:true});await page.goto('http://localhost:8080/');await page.screenshot({path:'/tmp/driver-mobile.png',fullPage:true});// Régression : les photos ne doivent pas étirer les cartes en grandes colonnes.
for (const width of [320, 390, 768, 1024, 1440]) {
  await page.setViewportSize({width, height: 900});
  for (const route of ['/', '/vehicules']) {
    await page.goto('http://localhost:8080' + route);
    const cards = await page.locator('.vehicle-card').evaluateAll(elements => elements.map(card => {
      const bounds = card.getBoundingClientRect();
      const info = card.querySelector('.vehicle-info').getBoundingClientRect();
      const visual = card.querySelector('.vehicle-visual');
      const photo = visual.getBoundingClientRect();
      const background = getComputedStyle(visual).backgroundImage;
      return {height: bounds.height, textBottom: info.bottom, cardBottom: bounds.bottom, photoWidth: photo.width, cardWidth: bounds.width, photoHeight: photo.height, background};
    }));
    if (cards.length === 3 && ['c','e','v'].some((model, index) => !cards[index].background.includes('/assets/classe-' + model + '-paris-card.webp?v='))) throw Error('Chaque véhicule doit conserver sa photo dédiée et versionnée');
    if (cards.length !== 3 || cards.some(card => card.height > 600 || card.photoWidth < card.cardWidth - 3 || card.photoHeight < 120 || card.textBottom > card.cardBottom + 1 || card.background === 'none')) {
      throw Error('Cartes véhicules : dimensions, texte ou fond incorrects à ' + width + 'px sur ' + route);
    }
  }
}
if(errors.length)throw Error(errors.join('\n'));console.log('PASS: '+links.length+' internal routes; unique H1; desktop/mobile screenshots; no mobile overflow; keyboard accordion; mobile menu; no JS errors; vehicle-card sizing/background regression.');await browser.close();})();
