<?php
use PHPMailer\PHPMailer\PHPMailer;
function processContact(array $site): array {
 $data=[]; $errors=[];
 foreach (['name','email','phone','subject','service','date','departure','arrival','message'] as $key) {
  $v=$_POST[$key] ?? ''; $data[$key]=is_string($v) ? trim($v) : '';
  if (strlen($data[$key])>($key==='message'?5000:250)) $errors[$key]='Ce champ est trop long.';
 }
 if (!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) $errors['form']='La session a expiré. Rechargez la page et réessayez.';
 if (($_POST['website']??'')!=='' || time()-($_SESSION['form_time']??time())<2) $errors['form']??='Veuillez patienter quelques instants avant de réessayer.';
 foreach (['name','email','phone','subject','service','message'] as $k) if ($data[$k]==='') $errors[$k]='Ce champ est obligatoire.';
 if (!filter_var($data['email'],FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/',$data['email'])) $errors['email']='Indiquez une adresse email valide.';
 if (!preg_match('/^[+0-9 ().-]{6,30}$/',$data['phone'])) $errors['phone']='Indiquez un numéro de téléphone valide.';
 if (!in_array($data['service'],['Transfert Aéroports','Transfert Gares','Mise à disposition','Autre'],true)) $errors['service']='Choisissez un service proposé.';
 if ($data['date']!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data['date']) || date('Y-m-d',strtotime($data['date']))!==$data['date'])) $errors['date']='Indiquez une date valide.';
 if (($_POST['consent']??'')!=='1') $errors['consent']='Veuillez prendre connaissance du traitement des données.';
 if ($errors) return [$data,$errors,false];
 // Compteur global par adresse réseau, conservé hors de la racine publique. Aucun contenu du formulaire stocké.
 $dir=getenv('CONTACT_RATE_DIR') ?: sys_get_temp_dir().'/driver-priority-rates';
 if (!is_dir($dir) && !mkdir($dir,0700,true)) return [$data,['form'=>'Le service est momentanément indisponible. Vous pouvez nous appeler.'],false];
 $file=$dir.'/'.hash('sha256',$_SERVER['REMOTE_ADDR']??'unknown').'.json';
 $fh=fopen($file,'c+');
 if (!$fh || !flock($fh,LOCK_EX)) return [$data,['form'=>'Le service est momentanément indisponible.'],false];
 $attempts=json_decode(stream_get_contents($fh),true) ?: [];
 $attempts=array_values(array_filter($attempts,fn($t)=>is_int($t) && $t>time()-3600));
 if (count($attempts)>=5) { fclose($fh); return [$data,['form'=>'Trop de tentatives. Réessayez dans une heure ou appelez-nous.'],false]; }
 $attempts[]=time(); ftruncate($fh,0); rewind($fh); fwrite($fh,json_encode($attempts)); flock($fh,LOCK_UN); fclose($fh); chmod($file,0600);
 foreach (['SMTP_HOST','SMTP_FROM','CONTACT_TO'] as $key) if (!getenv($key)) return [$data,['form'=>'L’envoi en ligne n’est pas encore configuré. Appelez le '.$site['phone'].' pour votre demande. Aucun message n’a été envoyé.'],false];
 require_once __DIR__.'/../vendor/phpmailer/Exception.php'; require_once __DIR__.'/../vendor/phpmailer/SMTP.php'; require_once __DIR__.'/../vendor/phpmailer/PHPMailer.php';
 try {
  $m=new PHPMailer(true); $m->isSMTP(); $m->Host=getenv('SMTP_HOST'); $m->Port=(int)(getenv('SMTP_PORT')?:587);
  $security=getenv('SMTP_SECURITY')?:'tls';
  if (!in_array($security,['tls','ssl'],true)) throw new RuntimeException('TLS required');
  $m->SMTPSecure=$security; $m->SMTPAuth=(bool)getenv('SMTP_USER'); $m->Username=getenv('SMTP_USER')?:''; $m->Password=getenv('SMTP_PASSWORD')?:'';
  $m->Timeout=15; $m->CharSet='UTF-8'; $m->setFrom(getenv('SMTP_FROM'),'DRIVER PRIORITY'); $m->addAddress(getenv('CONTACT_TO')); $m->addReplyTo($data['email'],$data['name']);
  $m->Subject='Demande depuis le site DRIVER PRIORITY'; $m->Body=implode("\n",array_map(fn($k,$v)=>$k.': '.$v,array_keys($data),$data)); $m->send();
  $_SESSION['csrf']=bin2hex(random_bytes(32)); return [[],[],true];
 } catch (Throwable $e) { return [$data,['form'=>'L’envoi a échoué. Réessayez plus tard ou appelez-nous. Aucun envoi n’a pu être confirmé.'],false]; }
}
