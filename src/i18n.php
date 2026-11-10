<?php
declare(strict_types=1);
// Les URL françaises existantes sont conservées ; chaque page a un équivalent anglais.
$englishRoutes = [
 '/' => '/en', '/services' => '/en/services', '/vehicules' => '/en/vehicles',
 '/destinations' => '/en/destinations', '/guides' => '/en/guides', '/faq' => '/en/faq', '/contact' => '/en/contact',
 '/services/transfert-aeroports' => '/en/services/airport-transfers',
 '/services/transfert-gares' => '/en/services/station-transfers',
 '/services/mise-a-disposition' => '/en/services/hourly-chauffeur',
 '/mentions-legales' => '/en/legal-notice', '/politique-confidentialite' => '/en/privacy-policy', '/cgv' => '/en/terms-and-conditions',
 '/guides/chauffeur-prive-van-paris' => '/en/guides/private-van-chauffeur-paris',
 '/guides/transfert-aeroport-chauffeur-prive-van' => '/en/guides/airport-transfer-private-van-chauffeur',
 '/guides/transfert-gare-chauffeur-prive-van' => '/en/guides/station-transfer-private-van-chauffeur',
];
foreach (['paris'=>'paris','paris-nuit'=>'paris-by-night','versailles'=>'versailles','giverny'=>'giverny','fontainebleau'=>'fontainebleau','champagne'=>'champagne','loire'=>'loire-valley','normandie'=>'normandy','mont-saint-michel'=>'mont-saint-michel','bourgogne'=>'burgundy','parc-asterix'=>'parc-asterix','disneyland-paris'=>'disneyland-paris'] as $fr=>$en) {
 $englishRoutes['/destinations/'.$fr]='/en/destinations/'.$en;
}
$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$requestPath = $requestPath==='/' ? '/' : rtrim($requestPath,'/');
$lang = $requestPath==='/en' || str_starts_with($requestPath,'/en/') ? 'en' : 'fr';
$routePath = $lang==='en' ? (array_flip($englishRoutes)[$requestPath] ?? substr($requestPath,3)) : $requestPath;
$routePath = $routePath ?: '/';

function languagePath(string $path, string $language): string {
 global $englishRoutes;
 return $language==='en' ? ($englishRoutes[$path] ?? '/en'.($path==='/'?'':$path)) : $path;
}

function translateText(string $text): string {
 global $site;
 static $catalogue;
 if ($catalogue===null) {
  $replacements=[];
  foreach(['company','address','phone','email'] as $key) $replacements['__'.strtoupper($key).'__']=$site[$key];
  $catalogue=[];
  foreach(json_decode(file_get_contents(__DIR__.'/translations/en.json'),true,512,JSON_THROW_ON_ERROR) as $source=>$translation) {
   $catalogue[strtr($source,$replacements)]=strtr($translation,$replacements);
  }
 }
 $key=trim($text);
 if (isset($catalogue[$key])) return preg_replace_callback('/^(\s*)(.*?)(\s*)$/us',fn($m)=>$m[1].$catalogue[$key].$m[3],$text);
 if (preg_match('/^(\d+) destination(s?) à découvrir$/u',$key,$m)) return $m[1].' destination'.$m[2].' to discover';
 if (preg_match('/^© (\d{4}) DRIVER PRIORITY\. Tous droits réservés\.$/u',$key,$m)) return '© '.$m[1].' DRIVER PRIORITY. All rights reserved.';
 return $text;
}

function englishUrl(string $url): string {
 global $site,$englishRoutes;
 $absolute=str_starts_with($url,$site['url'].'/') || $url===$site['url'];
 $local=$absolute ? substr($url,strlen($site['url'])) : $url;
 if ($local==='' && $absolute) $local='/';
 if (!str_starts_with($local,'/') || str_starts_with($local,'//')) return $url;
 $parts=parse_url($local); if ($parts===false) return $url;
 $path=$parts['path']??'/';
 if (!isset($englishRoutes[$path])) return $url;
 return ($absolute?$site['url']:'').$englishRoutes[$path].(isset($parts['query'])?'?'.$parts['query']:'').(isset($parts['fragment'])?'#'.$parts['fragment']:'');
}

/** Traduction HTML côté serveur : contenu indexable, sans API ni widget externe. */
function englishHtml(string $html): string {
 $dom=new DOMDocument();
 $previous=libxml_use_internal_errors(true);
 $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
 libxml_clear_errors(); libxml_use_internal_errors($previous);
 $xpath=new DOMXPath($dom);
 // Le libellé anglais d’une option ne doit pas changer sa valeur attendue par le serveur.
 foreach($xpath->query('//option[not(@value)]') as $option) $option->setAttribute('value',$option->textContent);
 foreach($xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::textarea) and not(ancestor::svg)]') as $node) $node->nodeValue=translateText($node->nodeValue);
 foreach($xpath->query('//*') as $element) {
  foreach(['alt','title','aria-label','placeholder'] as $attribute) if($element->hasAttribute($attribute)) $element->setAttribute($attribute,translateText($element->getAttribute($attribute)));
  if($element->tagName==='meta' && $element->hasAttribute('content')) {
   $value=$element->getAttribute('content');
   $element->setAttribute('content',$element->getAttribute('property')==='og:locale'?'en_GB':englishUrl(translateText($value)));
  }
  foreach(['href','action'] as $attribute) {
   if(!$element->hasAttribute($attribute) || $element->hasAttribute('data-language') || ($element->tagName==='link' && $element->getAttribute('rel')==='alternate')) continue;
   $element->setAttribute($attribute,englishUrl($element->getAttribute($attribute)));
  }
 }
 $dom->documentElement->setAttribute('lang','en');
 foreach($xpath->query('//script[@type="application/ld+json"]') as $script) {
  $data=json_decode($script->textContent,true);
  if(is_array($data)) {
   $walk=function($value,$key=null) use (&$walk) {
    if(is_array($value)) { foreach($value as $k=>$v) $value[$k]=$walk($v,$k); return $value; }
    if($key==='inLanguage') return 'en';
    return is_string($value)?englishUrl(translateText($value)):$value;
   };
   $script->nodeValue=json_encode($walk($data),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
  }
 }
 return $dom->saveHTML();
}
