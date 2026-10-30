#!/usr/bin/env python3
"""Generate clearly labelled photo placeholders for csf-core.

Each placeholder names the authentic Corner Stone Farm photograph it stands in for. They are
never meant to look like real photos. Re-run after editing SHOTS:
    python3 scripts/dev/generate-placeholders.py
"""
import html
import json
import os
import textwrap

OUT = os.path.join(os.path.dirname(__file__), '..', '..', 'wp-content', 'plugins', 'csf-core', 'assets', 'placeholders')

# slug: (description, width, height)
SHOTS = {
    'hero-home': ('Horse and rider together at Corner Stone Farm — wide, calm, natural light', 1920, 1080),
    'lessons': ('Beth teaching a lesson in the arena', 1600, 1200),
    'youth-riders': ('Young rider with a lesson horse (photo permission from parent)', 1600, 1200),
    'adult-amateurs': ('Adult amateur rider schooling on the flat', 1600, 1200),
    'dressage': ('Horse and rider working on dressage foundations', 1600, 1200),
    'offsite': ('Off-site or haul-in lesson', 1600, 1200),
    'after-school': ('Young riders learning horsemanship on the ground', 1600, 1200),
    'training': ('Beth riding a young horse in training', 1600, 1200),
    'young-horse': ('Young horse development — groundwork or early rides', 1600, 1200),
    'camps': ('Camp group with horses (photo permission for every child)', 1600, 1200),
    'horse-sales': ('Sale horse — clear side-on conformation photo', 1600, 1200),
    'leasing': ('Rider with a lease horse', 1600, 1200),
    'beth-portrait': ('Portrait of Beth Siemon', 1200, 1500),
    'kris-portrait': ('Portrait of Kris Siemon', 1200, 1500),
    'philosophy': ('A quiet moment between horse and rider', 1600, 1200),
    'mentorship': ('Working students or youth mentorship in action', 1600, 1200),
    'herd': ('Corner Stone Farm horses (lesson and farm horses)', 1600, 1200),
    'facility-arena': ('The actual riding arena at Corner Stone Farm', 1600, 1200),
    'facility-barn': ('The actual barn and stabling', 1600, 1200),
    'facility-grounds': ('Pastures and the Okanogan landscape around the farm', 1600, 1200),
    'resources': ('Horsemanship detail — grooming, tack, or hands-on care', 1600, 1200),
    'contact': ('Farm entrance or landscape near Omak', 1600, 1200),
}

TEMPLATE = '''<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" role="img" aria-label="Photo placeholder: {label}">
<defs><pattern id="p" width="28" height="28" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="28" height="28" fill="#D6C2A6"/><rect width="2" height="28" fill="#CDB696"/></pattern></defs>
<rect width="{w}" height="{h}" fill="url(#p)"/>
<rect x="{bx}" y="{by}" width="{bw}" height="{bh}" rx="10" fill="#F5F2EE" stroke="#4A3C2F" stroke-width="3" stroke-dasharray="14 10"/>
<g fill="none" stroke="#4A3C2F" stroke-width="5" transform="translate({cx} {iy})"><rect x="-48" y="-30" width="96" height="66" rx="8"/><circle cx="0" cy="3" r="18"/><path d="M-20 -30 l8 -14 h24 l8 14"/></g>
<text x="{cx}" y="{t1}" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="{f1}" font-weight="700" letter-spacing="6" fill="#4A3C2F">PHOTO NEEDED</text>
{lines}
<text x="{cx}" y="{t3}" text-anchor="middle" font-family="Helvetica, Arial, sans-serif" font-size="{f3}" fill="#4A3C2F">Placeholder — replace with an authentic Corner Stone Farm photograph</text>
</svg>
'''

os.makedirs(OUT, exist_ok=True)
CORNER = '''<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" role="img" aria-label="Photo placeholder: {label}">
<defs><pattern id="p" width="28" height="28" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><rect width="28" height="28" fill="#D6C2A6"/><rect width="2" height="28" fill="#CDB696"/></pattern></defs>
<rect width="{w}" height="{h}" fill="url(#p)"/>
<rect x="{bx}" y="{by}" width="{bw}" height="{bh}" rx="8" fill="#F5F2EE" stroke="#4A3C2F" stroke-width="3" stroke-dasharray="12 8"/>
<text x="{tx}" y="{t1}" font-family="Helvetica, Arial, sans-serif" font-size="24" font-weight="700" letter-spacing="4" fill="#4A3C2F">PHOTO NEEDED (HERO)</text>
<text x="{tx}" y="{t2}" font-family="Georgia, serif" font-size="24" fill="#111111">{desc}</text>
</svg>
'''

# Hero images sit behind text, so their label is a small bottom-centre tag, clear of the headline
# and still visible when the cover crops the sides on phones.
CORNER_SLUGS = {'hero-home'}

for slug, (desc, w, h) in SHOTS.items():
    if slug in CORNER_SLUGS:
        bw, bh = 520, 110
        bx, by = (w - bw) // 2, h - bh - 40
        svg = CORNER.format(w=w, h=h, bx=bx, by=by, bw=bw, bh=bh, tx=bx + 24, t1=by + 46, t2=by + 84,
                            label=html.escape(desc, quote=True), desc='Horse and rider at Corner Stone Farm')
        with open(os.path.join(OUT, slug + '.svg'), 'w') as fh:
            fh.write(svg)
        continue
    bw, bh = int(w * 0.8), int(h * 0.56)
    bx, by = (w - bw) // 2, (h - bh) // 2
    cx = w // 2
    f1, f2, f3 = int(w / 30), int(w / 34), int(w / 62)
    wrapped = textwrap.wrap(desc, width=40)
    iy = by + int(bh * 0.2)
    t1 = iy + 80 + f1
    lines = ''.join(
        '<text x="{0}" y="{1}" text-anchor="middle" font-family="Georgia, serif" font-size="{2}" fill="#111111">{3}</text>'.format(
            cx, t1 + int(f2 * 1.5) * (i + 1), f2, html.escape(line))
        for i, line in enumerate(wrapped))
    t3 = by + bh - int(f3 * 1.6)
    svg = TEMPLATE.format(w=w, h=h, bx=bx, by=by, bw=bw, bh=bh, cx=cx, iy=iy, t1=t1, t3=t3, f1=f1, f3=f3,
                          label=html.escape(desc, quote=True), lines=lines)
    with open(os.path.join(OUT, slug + '.svg'), 'w') as fh:
        fh.write(svg)
with open(os.path.join(OUT, 'manifest.json'), 'w') as fh:
    json.dump({slug: {'description': d, 'width': w, 'height': h} for slug, (d, w, h) in SHOTS.items()}, fh, indent=1)
print(len(SHOTS), 'placeholders written to', os.path.normpath(OUT))
