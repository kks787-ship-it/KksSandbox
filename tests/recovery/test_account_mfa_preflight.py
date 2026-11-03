"""Synthetic Toolkit-help and read-only compatibility guard checks."""
import importlib.util
from pathlib import Path
import subprocess
import types
import unittest
from unittest import mock
import test_backup as fixture
b=fixture.b
spec=importlib.util.spec_from_file_location('account_preflight',b.BASE/'scripts/pilot/account_mfa_preflight.py');p=importlib.util.module_from_spec(spec);spec.loader.exec_module(p)

class AccountPreflight(unittest.TestCase):
    def test_help_features_are_exact_not_operation_execution(self):
        flags=p.help_features('--wp-cli --login --info --login-token --get-password')
        self.assertTrue(all(flags.values()));self.assertFalse(any(p.help_features('--wp-cli-other --login-token --information').values()))
    def test_only_help_is_invoked_with_bounds(self):
        run=mock.Mock(return_value=types.SimpleNamespace(returncode=0,stdout=b'--wp-cli --login --info',stderr=b''))
        result=p.toolkit_metadata(run);self.assertTrue(result['help_read']);args,kwargs=run.call_args
        self.assertEqual(['/usr/sbin/plesk','ext','wp-toolkit','--help'],args[0]);self.assertEqual(20,kwargs['timeout']);self.assertEqual(subprocess.DEVNULL,kwargs['stdin'])
    def test_cli_errors_timeouts_do_not_export_details(self):
        for error in (OSError('synthetic private path'),subprocess.TimeoutExpired('synthetic private command',20)):
            result=p.toolkit_metadata(mock.Mock(side_effect=error));self.assertFalse(result['help_read']);self.assertNotIn('synthetic',str(result))
    def test_excess_output_and_nonzero_are_unknown_not_pass(self):
        for code,out in ((1,b'--login'),(0,b'x'*(512*1024+1))):
            result=p.toolkit_metadata(mock.Mock(return_value=types.SimpleNamespace(returncode=code,stdout=out,stderr=b'')));self.assertFalse(result['help_read'])
    def test_nonzero_usage_is_evidence_not_success(self):
        result=p.toolkit_metadata(mock.Mock(return_value=types.SimpleNamespace(returncode=1,stdout=b'Usage: --wp-cli --info --instance-id',stderr=b'')))
        self.assertFalse(result['help_read']);self.assertTrue(result['usage_output_observed']);self.assertTrue(result['cli_wrapper_documented']);self.assertEqual(1,result['help_exit_code'])
    def test_target_counts_only_and_no_wp_bootstrap(self):
        text=(b.BASE/'scripts/pilot/account_mfa_preflight.py').read_text()
        for banned in ('wp-load.php','--get-password','--login\'', 'UPDATE ', 'INSERT ', 'CREATE ', 'user_login','user_email','SELECT meta_value'):
            self.assertNotIn(banned,text)
        self.assertIn('SELECT COUNT(*)',text)

if __name__=='__main__':unittest.main()
