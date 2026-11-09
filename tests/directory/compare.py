"""Compare emitted native form contracts, independent of visual layout."""
from html.parser import HTMLParser
from collections import Counter
import json,sys
class Forms(HTMLParser):
 def __init__(self):super().__init__();self.forms=[];self.form=None;self.stack=[];self.unbalanced=False
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='form':
   assert self.form is None,'nested form';self.form={'kind':a.get('data-kind'),'fields':[],'body':None}
  if tag=='input' and self.form is not None:
   if a.get('name')=='body':
    b=json.loads(a['value'])
    if not b.get('expected'):b['uuid']='NEW'
    self.form['body']=b
   if a.get('data-field'):self.form['fields'].append((a['data-field'],a.get('type'),a.get('value'), 'required'in a,'disabled'in a,'readonly'in a))
  if tag in ('section','details','div'):self.stack.append(tag)
 def handle_endtag(self,tag):
  if tag=='form':self.forms.append(self.form);self.form=None
  if tag in ('section','details','div'):
   assert self.stack and self.stack.pop()==tag,'unbalanced '+tag
old,new=Forms(),Forms()
old.feed(open(sys.argv[1]).read());new.feed(open(sys.argv[2]).read())
key=lambda f:json.dumps(f,sort_keys=True)
assert Counter(map(key,old.forms))==Counter(map(key,new.forms)),'command or field contract changed'
assert not new.stack
assert '&lt;script&gt;' in open(sys.argv[2]).read(),'hostile label unescaped'
print(f'{len(new.forms)} native command/field contracts unchanged; markup balanced; hostile label escaped')
