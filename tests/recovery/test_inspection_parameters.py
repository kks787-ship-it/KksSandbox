"""Non-secret launch parameter regressions; no server access."""
import datetime as dt
import importlib.util
from pathlib import Path
import unittest
from unittest import mock

p=Path(__file__).resolve().parents[2]/'scripts/recovery/prepare_inspection.py'
s=importlib.util.spec_from_file_location('prepare_inspection',p);module=importlib.util.module_from_spec(s);s.loader.exec_module(module)

class Parameters(unittest.TestCase):
    def test_token_is_exactly_32_hex_and_unique(self):
        first=module.prepare('synthetic','synthetic');second=module.prepare('synthetic','synthetic')
        self.assertRegex(first['run_token'],r'^[a-f0-9]{32}$');self.assertNotEqual(first['run_token'],second['run_token'])
    def test_only_approved_nonsecret_fields_and_exact_window(self):
        value=module.prepare('synthetic','synthetic',dt.datetime(2026,11,3,tzinfo=dt.timezone.utc))
        self.assertEqual({'actor_ref','approval_ref','start','end','run_token'},set(value))
        self.assertEqual('2026-11-03T00:00:00Z',value['start']);self.assertEqual('2026-11-03T00:02:00Z',value['end'])
    def test_overlong_generated_token_rejected_before_submission(self):
        with mock.patch.object(module.secrets,'token_hex',return_value='a'*34):
            with self.assertRaises(ValueError):module.prepare('synthetic','synthetic')
    def test_invalid_reference_or_naive_clock_rejected(self):
        with self.assertRaises(ValueError):module.prepare('invalid reference','synthetic')
        with self.assertRaises(ValueError):module.prepare('synthetic','synthetic',dt.datetime(2026,11,3))

if __name__=='__main__':unittest.main()
