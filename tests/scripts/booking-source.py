"""Source audit supplements (does not replace) WordPress render/browser tests."""
from pathlib import Path
import re
import unittest

ROOT = Path(__file__).resolve().parents[2]
CORE = ROOT / 'wp-content/plugins/csf-core'


class SchedulingSourceAudit(unittest.TestCase):
    def test_generated_content_has_no_provider_or_online_booking_claims(self):
        pages = list((CORE / 'content/pages').glob('*.php'))
        self.assertEqual(len(pages), 21)
        for path in pages + list((CORE / 'patterns').glob('*.php')):
            with self.subTest(path=path.name):
                self.assertIsNone(re.search(
                    r'acuity|as\.me|book(?:ed)? online|online booking|see available times|'
                    r'current availability|confirmation after booking', path.read_text(), re.I))

    def test_production_plugin_has_no_canceled_provider(self):
        for path in CORE.rglob('*.php'):
            with self.subTest(path=str(path.relative_to(CORE))):
                self.assertIsNone(re.search(r'acuity|cornerstonefarm\.as\.me', path.read_text(), re.I))



if __name__ == '__main__':
    unittest.main()
