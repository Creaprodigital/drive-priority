"""Régression du cache : modifier une image doit renouveler son URL ET la feuille CSS.
PHP_BIN=php python3 tests/cache.py
"""
import json, os, shutil, subprocess, tempfile, unittest
from pathlib import Path
ROOT = Path(__file__).resolve().parent.parent
PHP = os.environ.get('PHP_BIN', 'php')

class CacheTest(unittest.TestCase):
    def test_image_change_with_preserved_timestamp(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            (root / 'src').mkdir()
            (root / 'assets').mkdir()
            shutil.copy(ROOT / 'src/assets.php', root / 'src/assets.php')
            image = root / 'assets/test.webp'
            image.write_bytes(b'original')
            (root / 'assets/style.css').write_text(".hero{background:url('/assets/test.webp')}")
            runner = root / 'check.php'
            runner.write_text("<?php require __DIR__.'/src/assets.php'; echo json_encode([assetUrl('/assets/test.webp'),stylesheetUrl(),renderedStylesheet()]);")
            def read():
                return json.loads(subprocess.check_output([PHP, str(runner)]))
            original = read()
            self.assertEqual(original, read(), 'Le cache doit rester stable sans changement')
            timestamp = image.stat().st_mtime_ns
            image.write_bytes(b'modified')  # Même longueur et même date, contenu différent.
            os.utime(image, ns=(timestamp, timestamp))
            updated = read()
            self.assertNotEqual(original[0], updated[0], 'URL de l’image inchangée')
            self.assertNotEqual(original[1], updated[1], 'La feuille doit changer avec ses images')
            self.assertIn(updated[0], updated[2])
            css = root / 'assets/style.css'
            css.write_text(css.read_text() + '\nbody{color:black}')
            self.assertNotEqual(updated[1], read()[1], 'URL CSS inchangée après modification de style')

if __name__ == '__main__':
    unittest.main(verbosity=2)
