"""Synthetic path classification and no-auth-file-read probe tests."""
import importlib.util
from pathlib import Path
import tempfile
import unittest

path=Path(__file__).resolve().parents[2]/'scripts/recovery/probe_auth_scope.py'
spec=importlib.util.spec_from_file_location('auth_scope_probe',path);module=importlib.util.module_from_spec(spec);spec.loader.exec_module(module)

class Probe(unittest.TestCase):
    def test_categories_no_value_export(self):
        for value,kind in [(module.AUTH_TARGET/'x','SANDBOX_PD_REFERENCE'),(module.AUTH_PARENT/'x','SUBSCRIPTION_PD_REFERENCE'),(module.ROOT/'.htpasswd','SANDBOX_ROOT_REFERENCE'),(Path('/unapproved/secret'),'OUTSIDE_SCOPE')]:self.assertEqual(kind,module.classify(str(value)))
        self.assertEqual('OUTSIDE_SCOPE',module.classify(str(module.AUTH_TARGET/'..'/'secret')))
    def test_nested_context_probe_categorizes_without_returning_locations(self):
        text='server {\nroot /synthetic/sandbox;\nlocation ^~ /.well-known/acme-challenge/ { if ($x) { return 1; }\nroot /var/www/vhosts/default/htdocs;\n}\n}'
        result=module.nginx_roots(text);self.assertEqual('ACME_CHALLENGE',result[-1][1])
    def test_references_never_opened_and_only_categories_returned(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);conf=root/'conf';conf.mkdir();(conf/'httpd.conf').write_text('AuthUserFile "/unapproved/not-present"\n')
            result=module.inspect(conf,root);self.assertEqual(0,result['auth_files_read']);self.assertEqual({'OUTSIDE_SCOPE':1},result['auth_reference_categories']);self.assertNotIn('not-present',str(result))
    def test_outside_includes_counted_without_read(self):
        with tempfile.TemporaryDirectory() as tmp:
            root=Path(tmp);conf=root/'conf';conf.mkdir();(conf/'httpd.conf').write_text('include /etc/*;\n')
            self.assertEqual(1,module.inspect(conf,root)['out_of_scope_includes'])

if __name__=='__main__':unittest.main()
