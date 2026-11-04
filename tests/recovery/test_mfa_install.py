"""Local installer guard tests; never connects to the farm."""
import hashlib
import importlib.util
from pathlib import Path
import unittest

ROOT=Path(__file__).resolve().parents[2]
spec=importlib.util.spec_from_file_location('install_mfa',ROOT/'scripts/pilot/install_mfa.py')
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)


class MFAInstallTests(unittest.TestCase):
    def rows(self):
        paths=['wp-content/plugins/two-factor/'+('two-factor.php' if i==0 else str(i)+'.php') for i in range(21)]
        paths+=['wp-content/plugins/csf-mfa/'+('csf-mfa.php' if i==0 else str(i)+'.php') for i in range(4)]
        return [{'path':p,'text':'<?php','sha256':hashlib.sha256(b'<?php').hexdigest()} for p in paths]

    def test_configuration_changes_only_approved_switch(self):
        raw=b'<?php\n// Synthetic fixture\ndefine("DB_NAME","fixture");'
        changed=m.enabled_config(raw)
        self.assertTrue(changed.endswith(raw[5:]))
        self.assertEqual(1,changed.count(b"define( 'CSF_MFA_ENABLED', true );"))
        self.assertNotIn(b'CSF_MFA_ENFORCE_ADMIN',changed)

    def test_existing_authentication_switches_are_not_overwritten(self):
        for raw in (b'not php',b'<?php define("CSF_MFA_ENABLED",false);',b'<?php define("CSF_MFA_ENFORCE_ADMIN",true);'):
            with self.assertRaises(ValueError):m.enabled_config(raw)

    def test_exact_safe_scoped_entries(self):
        m.validate_entries(self.rows())
        for bad in ('wp-content/plugins/csf-mfa/../x.php','wp-content/plugins/csf-scheduling/x.php','/outside.php','wp-content/plugins/csf-mfa/a\nx.php'):
            rows=self.rows();rows[-1]['path']=bad
            with self.assertRaises(ValueError):m.validate_entries(rows)

    def test_hash_duplicates_counts_and_main_files(self):
        for mutate in (lambda r:r.pop(),lambda r:r[-1].update(sha256='0'*64),lambda r:r.__setitem__(-1,r[0]),lambda r:r[0].update(path='wp-content/plugins/two-factor/missing-main.php')):
            rows=self.rows();mutate(rows)
            with self.assertRaises(ValueError):m.validate_entries(rows)

    def test_native_response_never_returns_extra_sensitive_fields(self):
        self.assertEqual({'state':'blocked'},m.activation_result({'state':'blocked'}))
        for response in ({'state':'activated','enforcement':True},{'state':'blocked','secret':'private'},{'state':'verified'}):
            with self.assertRaises(ValueError):m.activation_result(response)

    def test_unverified_vendor_download_is_rejected_before_installation(self):
        for raw in (b'', b'x'*91837, b'x'*91838):
            with self.assertRaises(ValueError):m.vendor_entries(self.rows(),lambda:raw)
