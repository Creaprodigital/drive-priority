<?php
declare(strict_types=1);
$site=require __DIR__.'/config/site.php';
require __DIR__.'/src/content.php'; require __DIR__.'/src/legal.php';
function siteText(array $items, array $site): array {
 $replacements=[]; foreach(['siret','siren','vat','capital','address','phone','company'] as $key) $replacements['__'.strtoupper($key).'__']=$site[$key];
 foreach($items as $key=>$value) $items[$key]=is_array($value)?siteText($value,$site):strtr($value,$replacements);
 return $items;
}
$services=siteText($services,$site); $faq=siteText($faq,$site); $legal=siteText($legal,$site);

function e($v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function icon($name): string {
 $paths=['arrow'=>'M5 12h14m-6-6 6 6-6 6','phone'=>'M5 3h4l2 5-3 2c2 4 3 5 7 7l2-3 5 2v4C10 23 1 14 5 3Z','plane'=>'m3 10 7 1 5-8 2 1-2 8 6 3v2l-7-1-3 5H9l1-6-7-3Z','train'=>'M6 17V5c0-3 12-3 12 0v12H6Zm0-8h12M8 21l2-4m6 4-2-4M9 13h.1m6 0h.1','clock'=>'M12 7v5l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0','diamond'=>'m3 8 4-5h10l4 5-9 13L3 8Zm0 0h18M7 3l5 18 5-18','shield'=>'m12 2 8 3v7c0 5-8 10-8 10S4 17 4 12V5l8-3Zm-4 9 3 3 5-6','people'=>'M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-6 9v-3c0-6 12-6 12 0v3m3-17c6 0 6 8 0 8m0 3c4 0 5 2 5 6','bag'=>'M4 7h16v14H4V7Zm4 0V3h8v4M8 7v14m8-14v14','car'=>'m4 9 2-6h12l2 6M3 9h18v10H3V9Zm3 10v3m12-3v3M6 13h2m8 0h2','pin'=>'M12 22S4 14 4 9a8 8 0 0 1 16 0c0 5-8 13-8 13Zm3-13a3 3 0 1 1-6 0 3 3 0 0 1 6 0'];
 return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="'.($paths[$name]??$paths['arrow']).'"/></svg>';
}
function accordion($items): void { foreach($items as $q=>$a) echo '<details><summary>'.e($q).'<span>+</span></summary><p>'.e($a).'</p></details>'; }
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)?:'/');
$path=$path==='/'?'/':rtrim($path,'/');
$pages=['/'=>['Chauffeur privé à Paris & en Île-de-France','Vos transferts privés, trajets professionnels et mises à disposition avec DRIVER PRIORITY. Demandez votre devis.'],'/services'=>['Nos services','Transferts aéroports, gares et chauffeur à disposition : découvrez les services DRIVER PRIORITY.'],'/faq'=>['Vos questions, nos réponses','Réservation, tarifs, bagages et transferts : préparez votre déplacement avec la FAQ DRIVER PRIORITY.'],'/contact'=>['Parlons de votre prochain trajet','Contactez DRIVE PRIORITY à Osny : demande de devis, transfert privé ou mise à disposition.'],'/vehicules'=>['Nos véhicules','Découvrez les catégories de véhicules présentées pour vos déplacements privés et professionnels.'],'/destinations'=>['Vos destinations','Paris, Île-de-France, aéroports et gares : préparez votre destination avec un chauffeur privé.']];
foreach($services as $slug=>$s) $pages['/services/'.$slug]=[$s['title'],$s['intro'].' Découvrez ce service et demandez un devis à DRIVER PRIORITY.'];
foreach($legal as $slug=>$s) $pages['/'.$slug]=[$s['title'],$s['intro']];
if($path==='/sitemap.xml') { header('Content-Type: application/xml; charset=utf-8'); echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'; foreach($pages as $url=>$v) echo '<url><loc>'.e($site['url'].($url==='/'?'/':$url)).'</loc></url>'; echo '</urlset>'; exit; }
if($path==='/robots.txt') { header('Content-Type: text/plain'); echo "User-agent: *\nAllow: /\nSitemap: ".$site['url']."/sitemap.xml\n"; exit; }
$notfound=!isset($pages[$path]); if($notfound) { http_response_code(404); $pages[$path]=['Page introuvable','Cette page n’existe pas. Retrouvez les services de DRIVER PRIORITY.']; }
header('X-Content-Type-Options: nosniff'); header('Referrer-Policy: strict-origin-when-cross-origin'); header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
$data=[]; $errors=[]; $sent=false;
if($path==='/contact') {
 ini_set('session.use_strict_mode','1'); session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax']); session_start();
 header('Cache-Control: no-store'); $_SESSION['csrf']??=bin2hex(random_bytes(32)); $_SESSION['form_time']??=time();
 if($_SERVER['REQUEST_METHOD']==='POST') { require __DIR__.'/src/contact.php'; [$data,$errors,$sent]=processContact($site); if($errors) http_response_code(422); if($sent) { $_SESSION['sent']=true; header('Location: /contact',true,303); exit; } }
 else { $sent=$_SESSION['sent']??false; unset($_SESSION['sent']); }
}
[$title,$description]=$pages[$path]; $home=$path==='/';
require __DIR__.'/templates/header.php';
if($home) require __DIR__.'/templates/home.php';
else { echo '<section class="page-banner"><div class="container"><nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="/">Accueil</a><span>/</span><span>'.e($title).'</span></nav><p class="eyebrow">DRIVER PRIORITY · CHAUFFEUR PRIVÉ</p><h1>'.e($title).'</h1><p>'.e($description).'</p></div></section>'; }
if($path==='/services') { echo '<section class="section container"><div class="section-heading"><p class="eyebrow">CHAQUE TRAJET MÉRITE UNE ATTENTION PARTICULIÈRE</p><h2>Un service, pour chaque occasion.</h2><p>Le confort d’un chauffeur privé, la liberté de choisir votre destination.</p></div>'; require __DIR__.'/templates/service-cards.php'; echo '</section>'; }
$slug=substr($path,10);
if(str_starts_with($path,'/services/') && isset($services[$slug])) { $s=$services[$slug]; echo '<section class="section container detail-grid"><div><p class="eyebrow">'.e($s['eyebrow']).'</p><h2>'.e($s['intro']).'</h2><p>'.e($s['text']).'</p><div class="benefit-list"><p>'.icon('diamond').' Un trajet privé, pensé pour vous</p><p>'.icon('shield').' Des modalités définies avant confirmation</p><p>'.icon('people').' Un véhicule adapté à vos besoins</p></div><a class="button" href="/contact?service='.rawurlencode($s['title']).'">Demander un devis '.icon('arrow').'</a></div><aside class="destination-panel"><span class="large-icon">'.icon($s['icon']).'</span><h3>'.($slug==='mise-a-disposition'?'À votre rythme':'Vos points de départ et d’arrivée').'</h3><ul>'; foreach($s['places'] as $place) echo '<li>'.e($place).'</li>'; echo '</ul><p>Disponibilité et conditions à confirmer lors de votre demande.</p></aside></section><section class="section soft"><div class="container narrow"><p class="eyebrow">BIEN PRÉPARER VOTRE TRAJET</p><h2>Vos questions sur ce service</h2>'; accordion($s['faq']); echo '</div></section>'; }
if($path==='/faq') { echo '<section class="section container faq-layout"><nav class="faq-nav" aria-label="Catégories de questions">'; $i=0; foreach($faq as $cat=>$items) echo '<a href="#faq-'.($i++).'">'.e($cat).' '.icon('arrow').'</a>'; echo '</nav><div>'; $i=0; foreach($faq as $cat=>$items) { echo '<section class="faq-category" id="faq-'.($i++).'"><p class="eyebrow">VOS QUESTIONS</p><h2>'.e($cat).'</h2>'; accordion($items); echo '</section>'; } echo '<div class="inline-callout"><h3>Une autre question ?</h3><p>Nous sommes à votre écoute pour préparer votre déplacement.</p><a class="text-link" href="/contact">Contactez-nous '.icon('arrow').'</a></div></div></section>'; }
if($path==='/contact') require __DIR__.'/templates/contact.php';
if($path==='/vehicules') { echo '<section class="section container"><div class="section-heading"><p class="eyebrow">LE CONFORT À CHAQUE KILOMÈTRE</p><h2>Choisissez votre façon de voyager.</h2></div>'; require __DIR__.'/templates/vehicles.php'; echo '<p class="muted centered">Visuels d’illustration. Modèle, millésime, équipements et capacités à confirmer selon le véhicule disponible.</p></section>'; }
if($path==='/destinations') { echo '<section class="section container"><div class="section-heading"><p class="eyebrow">PARIS & AU-DELÀ</p><h2>Votre destination, notre attention.</h2><p>Paris, Osny et l’Île-de-France. Pour un déplacement ailleurs en France, consultez-nous pour étudier votre demande.</p></div>'; require __DIR__.'/templates/service-cards.php'; echo '</section>'; }
$l=substr($path,1); if(isset($legal[$l])) { $doc=$legal[$l]; echo '<section class="section container legal-layout"><nav aria-label="Sommaire" class="legal-toc"><h2>Sommaire</h2>'; $i=0; foreach($doc['sections'] as $h=>$v) echo '<a href="#section-'.(++$i).'">'.$i.'. '.e($h).'</a>'; echo '</nav><article class="legal-copy"><div class="notice">Document à compléter et à valider avant publication. Mise à jour : '.e($site['updated']).'.</div>'; $i=0; foreach($doc['sections'] as $h=>$v) echo '<section id="section-'.(++$i).'"><h2>'.$i.'. '.e($h).'</h2><p>'.e($v).'</p></section>'; echo '</article></section>'; }
if($notfound) echo '<section class="section container centered"><h2>Retrouvons le bon chemin.</h2><a href="/" class="button">Revenir à l’accueil '.icon('arrow').'</a></section>';
if(!$home && !isset($legal[$l??'']) && $path!='/contact' && !$notfound) require __DIR__.'/templates/cta.php';
require __DIR__.'/templates/footer.php';
