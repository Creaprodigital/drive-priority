"""Tests locaux sans envoi extérieur : HTTP, protections et SMTP TLS simulé.
Usage : PHP_BIN=php python3 tests/smoke.py
"""
import html, http.cookiejar, json, os, re, shutil, socket, ssl, subprocess, tempfile, threading, time, unittest
import urllib.request as url
import urllib.parse as parse
import urllib.error
import xml.etree.ElementTree as ET
from pathlib import Path
ROOT=Path(__file__).resolve().parent.parent
PHP=os.environ.get('PHP_BIN','php')
class Client:
 def __init__(self,port):
  self.base=f'http://127.0.0.1:{port}'; self.opener=url.build_opener(url.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def get(self,path):
  try:r=self.opener.open(self.base+path)
  except urllib.error.HTTPError as e:r=e
  return r.status,r.read().decode()
 def post(self,data):
  try:r=self.opener.open(self.base+'/contact',parse.urlencode(data).encode())
  except urllib.error.HTTPError as e:r=e
  return r.status,r.read().decode()
 def form(self):
  status,body=self.get('/contact'); assert status==200
  return dict(csrf=re.search(r'name="csrf" value="([^"]+)"',body)[1],name='Test Local',email='person@example.test',phone='0600000000',subject='Test',service='Transfert Aéroports',message='Message de test synthétique',consent='1')
class Smoke(unittest.TestCase):
 @classmethod
 def setUpClass(cls):
  cls.temp=tempfile.TemporaryDirectory(); cls.tmp=Path(cls.temp.name); cls.processes=[]; cls.messages=[]
  cert=cls.tmp/'cert.pem'; key=cls.tmp/'key.pem'
  subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-days','1','-subj','/CN=localhost','-addext','subjectAltName=DNS:localhost'],check=True,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
  ctx=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER); ctx.load_cert_chain(cert,key)
  cls.sock=socket.socket(); cls.sock.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1); cls.sock.bind(('127.0.0.1',25252)); cls.sock.listen()
  def smtp():
   conn,_=cls.sock.accept()
   with ctx.wrap_socket(conn,server_side=True) as s:
    f=s.makefile('rwb'); f.write(b'220 localhost test SMTP\r\n'); f.flush()
    while line:=f.readline():
     command=line.decode().strip()
     if command.startswith('EHLO'):f.write(b'250-localhost\r\n250 SIZE 100000\r\n')
     elif command=='DATA':
      f.write(b'354 Send message\r\n'); f.flush(); data=b''
      while (line:=f.readline())!=b'.\r\n':
       if not line:break
       data+=line
      cls.messages.append(data); f.write(b'250 queued\r\n')
     elif command=='QUIT':f.write(b'221 bye\r\n'); f.flush(); break
     else:f.write(b'250 ok\r\n')
     f.flush()
  cls.thread=threading.Thread(target=smtp,daemon=True); cls.thread.start()
  for port,enabled in [(8090,False),(8091,True)]:
   env=os.environ.copy(); env['CONTACT_RATE_DIR']=str(cls.tmp/f'rates-{port}')
   for k in ['SMTP_HOST','SMTP_FROM','CONTACT_TO','SMTP_USER','SMTP_PASSWORD']:env.pop(k,None)
   if enabled:env.update(SMTP_HOST='localhost',SMTP_PORT='25252',SMTP_SECURITY='ssl',SMTP_FROM='site@example.test',CONTACT_TO='office@example.test')
   log=open(cls.tmp/f'php-{port}.log','w'); proc=subprocess.Popen([PHP,'-d',f'openssl.cafile={cert}','-S',f'127.0.0.1:{port}','router.php'],cwd=ROOT,env=env,stdout=log,stderr=log); cls.processes.append((proc,log))
   for attempt in range(50):
    try:Client(port).get('/');break
    except OSError:time.sleep(.1)
   else:raise RuntimeError('PHP startup failed')
 @classmethod
 def tearDownClass(cls):
  for proc,log in cls.processes:proc.terminate();proc.wait();log.close()
  cls.sock.close();cls.temp.cleanup()
 def test_routes_and_metadata(self):
  c=Client(8090); status,body=c.get('/sitemap.xml');self.assertEqual(status,200)
  urls=ET.fromstring(body); titles=set(); descriptions=set()
  for el in urls:
   path=parse.urlparse(el[0].text).path; status,body=c.get(path);self.assertEqual(status,200,path)
   self.assertEqual(len(re.findall('<h1[ >]',body)),1,path)
   title=re.search('<title>(.*?)</title>',body)[1];self.assertNotIn(title,titles);titles.add(title)
   desc=re.search('name="description" content="([^"]+)"',body)[1];self.assertNotIn(desc,descriptions);descriptions.add(desc)
  self.assertEqual(len(titles),24)
  self.assertEqual(c.get('/inconnue')[0],404)
  for path in ['/config/site.php','/.env','/vendor/phpmailer/SMTP.php','/tests/smoke.py','/README.md'] : self.assertEqual(c.get(path)[0],404)
 def test_cache_headers_and_versioned_backgrounds(self):
  c=Client(8090)
  response=c.opener.open(c.base+'/')
  self.assertIn('no-cache',response.headers.get('Cache-Control',''))
  body=response.read().decode()
  css_url=html.unescape(re.search(r'rel="stylesheet" href="([^"]+)"',body)[1])
  self.assertRegex(css_url,r'^/styles\.css\?v=[a-f0-9]{20}$')
  response=c.opener.open(c.base+css_url)
  self.assertIn('text/css',response.headers.get('Content-Type',''))
  self.assertIn('immutable',response.headers.get('Cache-Control',''))
  css=response.read().decode()
  urls=re.findall(r"url\('(/assets/[^']+)'\)",css)
  self.assertTrue(urls)
  for asset in urls:
   self.assertRegex(asset,r'\?v=[a-f0-9]{20}$')
   self.assertEqual(c.opener.open(c.base+asset).status,200)
  response=c.opener.open(c.base+'/contact')
  self.assertIn('no-store',response.headers.get('Cache-Control',''))
 def test_csrf(self):
  c=Client(8090);data=c.form();data['csrf']='invalid';status,body=c.post(data);self.assertEqual(status,422);self.assertIn('session a expiré',body)
 def test_honeypot(self):
  c=Client(8090);data=c.form();data['website']='spam';status,body=c.post(data);self.assertEqual(status,422);self.assertIn('patienter',body)
 def test_validation_escaping(self):
  c=Client(8090);data=c.form();data.update(name='<script>alert(1)</script>',email='not-an-email',phone='oops',date='2026-02-31',consent='');status,body=c.post(data);self.assertEqual(status,422);self.assertNotIn('<script>alert',body);self.assertIn('&lt;script&gt;',body);self.assertIn('email valide',body);self.assertIn('date valide',body)
 def test_smtp_tls_success(self):
  c=Client(8091);data=c.form();time.sleep(2.1);status,body=c.post(data);self.assertEqual(status,200);self.assertIn('message a bien été envoyé',body);self.assertEqual(len(self.messages),1);self.assertIn(b'Reply-To: Test Local <person@example.test>',self.messages[0]);self.assertIn(b'office@example.test',self.messages[0]);self.assertNotIn('message a bien été envoyé',c.get('/contact')[1])
 def test_unconfigured_and_rate_limit(self):
  c=Client(8090);data=c.form();time.sleep(2.1)
  for _ in range(5):
   status,body=c.post(data);self.assertEqual(status,422);self.assertIn('pas encore configuré',body)
  status,body=c.post(data);self.assertEqual(status,422);self.assertIn('Trop de tentatives',body)
if __name__=='__main__':unittest.main(verbosity=2)
