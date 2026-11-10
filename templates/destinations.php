<section class="journey-intro">
 <div class="container journey-intro-grid">
  <div class="journey-intro-copy">
   <nav class="breadcrumb" aria-label="Fil d’Ariane"><a href="/">Accueil</a><span>/</span><span aria-current="page">Destinations</span></nav>
   <p class="eyebrow">PARIS POUR POINT DE DÉPART. VOS ENVIES POUR HORIZON.</p>
   <h1>Une destination.<br><em>Votre façon de voyager.</em></h1>
   <p class="journey-lead">Une capitale à redécouvrir, un château à explorer, une parenthèse au grand air. Prenez le temps de choisir ; nous préparons le trajet avec vous.</p>
   <a href="#nos-destinations" class="button">Trouver mon escapade <?= icon('arrow') ?></a>
   <p class="journey-signature">Chauffeur privé · Départ de Paris & d’Île-de-France · Sur devis</p>
  </div>
  <figure class="journey-cover"><img src="<?= e(assetUrl('/assets/destination-paris.webp')) ?>" width="1400" height="933" alt="<?= e($destinations['paris']['alt']) ?>" fetchpriority="high"><figcaption><span>01 / L’INVITATION AU VOYAGE</span><strong>Paris, et tout ce qui vous attend au-delà.</strong></figcaption></figure>
 </div>
</section>
<section class="section container journey-collection" id="nos-destinations">
 <div class="journey-section-heading"><div><p class="eyebrow">CHOISISSEZ VOTRE PROCHAINE PARENTHÈSE</p><h2>Des lieux à vivre.<br>Des trajets à votre mesure.</h2></div><p>Douze idées de destinations pour composer votre programme. Une simple inspiration ou une envie précise : chaque demande se prépare individuellement.</p></div>
 <div class="journey-filters" hidden role="group" aria-label="Filtrer les destinations">
 <?php foreach($destinationCategories as $key=>$label): ?><button type="button" data-destination-filter="<?= e($key) ?>" aria-pressed="<?= $key==='all'?'true':'false' ?>" aria-controls="destination-grid"><?= e($label) ?></button><?php endforeach; ?>
 </div>
 <p class="journey-count" role="status" aria-live="polite" aria-atomic="true"><span data-destination-count>12 destinations à découvrir</span></p>
 <div class="journey-grid" id="destination-grid">
 <?php $number=0; foreach($destinations as $slug=>$destination): $number++; ?>
  <article class="journey-card" data-destination-category="<?= e($destination['category']) ?>">
   <a class="journey-card-photo" href="/destinations/<?= e($slug) ?>" aria-label="Découvrir <?= e($destination['title']) ?>"><img src="<?= e(assetUrl('/assets/destination-'.$slug.'.webp')) ?>" width="1400" height="933" loading="lazy" decoding="async" alt="<?= e($destination['alt']) ?>"><span class="journey-number"><?= str_pad((string)$number,2,'0',STR_PAD_LEFT) ?></span></a>
   <div class="journey-card-copy"><p class="eyebrow"><?= e($destination['region']) ?></p><h3><a href="/destinations/<?= e($slug) ?>"><?= e($destination['title']) ?></a></h3><p><?= e($destination['intro']) ?></p><a class="journey-discover" href="/destinations/<?= e($slug) ?>">Explorer cette destination <?= icon('arrow') ?><span class="sr-only"> : <?= e($destination['title']) ?></span></a></div>
  </article>
 <?php endforeach; ?>
 </div>
 <p class="muted journey-illustrations">Visuels d’illustration créés pour Driver Priority. Accès, visites et activités à confirmer selon votre programme.</p>
</section>
<section class="journey-custom"><div class="container journey-custom-grid"><span class="journey-custom-icon"><?= icon('pin') ?></span><div><p class="eyebrow">LE VOYAGE NE S’ARRÊTE PAS À CETTE SÉLECTION</p><h2>Une autre adresse en tête ?</h2><p>Partagez vos étapes, votre date et le nombre de voyageurs. Nous étudierons le transport et les conditions adaptées à votre projet.</p></div><a class="button" href="/contact?service=Mise%20%C3%A0%20disposition&amp;destination=Itin%C3%A9raire%20personnalis%C3%A9">Composer mon itinéraire <?= icon('arrow') ?></a></div></section>
<section class="section container journey-how"><p class="eyebrow">BIEN PRÉPARER VOTRE ESCAPADE</p><h2>Votre programme se construit ensemble.</h2><div class="journey-steps"><div><span>01</span><h3>Votre envie</h3><p>Indiquez la destination, la date, le point de départ et votre groupe.</p></div><div><span>02</span><h3>Votre proposition</h3><p>Le trajet, le véhicule, les horaires et le prix sont précisés sur devis.</p></div><div><span>03</span><h3>Votre confirmation</h3><p>Le déplacement est organisé après confirmation explicite de la prestation.</p></div></div><p class="muted">Les visites guidées, billets, repas et dégustations ne sont pas inclus par défaut. Les modalités d’attente, les étapes et le retour sont à confirmer avant réservation.</p></section>
