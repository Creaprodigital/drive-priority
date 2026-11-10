<?php
// Configuration privée facultative pour hébergement mutualisé ; fichier ignoré par Git.
$private=__DIR__.'/local.php';
if (is_file($private)) {
 $values=require $private;
 foreach (['SITE_URL','SMTP_HOST','SMTP_PORT','SMTP_SECURITY','SMTP_USER','SMTP_PASSWORD','SMTP_FROM','CONTACT_TO','CONTACT_RATE_DIR'] as $key) {
  if (isset($values[$key]) && is_string($values[$key]) && !getenv($key)) putenv($key.'='.$values[$key]);
 }
}

return [
 'brand'=>'DRIVER PRIORITY', 'company'=>'DRIVE PRIORITY',
 'siren'=>'942837154', 'siret'=>'94283715400015', 'capital'=>'1 000,00 €', 'vat'=>'FR10942837154',
 'address'=>'8 rue de Cergy, 95520 Osny, France', 'phone'=>'+33 6 08 05 62 75', 'tel'=>'+33608056275', 'email'=>'drivepriority1@icloud.com',
 'url'=>rtrim(getenv('SITE_URL') ?: 'http://localhost:8080', '/'),
 'updated'=>'10 novembre 2026',
];
