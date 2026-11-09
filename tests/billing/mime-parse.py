"""Independent standard-library MIME parser; never prints attachment or recipient contents."""
import email,email.policy,hashlib

def verify_mime(raw,pdf_sha256,terms_sha256):
 m=email.message_from_bytes(raw,policy=email.policy.default)
 assert not m.defects and m.get_content_type()=='multipart/mixed'
 assert set(m.keys())=={'From','To','Subject','Date','Message-ID','MIME-Version','Content-Type'}
 assert all(len(m.get_all(k))==1 for k in m.keys())
 assert str(m['To']).endswith('.invalid') and str(m['From']).endswith('@fixture.invalid>')
 assert 'CSF-' not in str(m['Subject']) and m['Date'].datetime is not None
 parts=list(m.iter_parts());assert len(parts)==3 and all(not p.defects for p in parts)
 assert parts[0].get_content_type()=='text/plain' and parts[0].get_content_charset()=='utf-8'
 assert parts[1].get_content_type()=='application/pdf' and parts[1].get_filename()=='gift-certificate.pdf'
 assert parts[2].get_content_type()=='text/plain' and parts[2].get_filename()=='accepted-terms.txt'
 assert parts[1].get_content_disposition()==parts[2].get_content_disposition()=='attachment'
 assert hashlib.sha256(parts[1].get_payload(decode=True)).hexdigest()==pdf_sha256
 assert hashlib.sha256(parts[2].get_payload(decode=True)).hexdigest()==terms_sha256
 assert parts[1].get_payload(decode=True).startswith(b'%PDF-1.4')
 assert all(p['Content-Transfer-Encoding']=='base64' for p in parts)
 return True
