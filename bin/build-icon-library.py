#!/usr/bin/env python3
"""Build the SVG icon library: includes/icon-library/<kit>.php.

Each file returns array( 'icon-name' => '<svg …>' ) for one icon kit: the
icons as the free plugin shows them. The free plugin draws its icons from
these files instead of icon fonts; PRO gets the same files through
bin/sync-from-free.sh and uses them for any icon its own artwork lacks, so
an icon added here shows up in both plugins.

Where each SVG comes from, in order:

  1. The library file as it already is. Once an icon is released, its
     markup never changes.
  2. The free plugin's icon font, through the codepoint its CSS assigns to
     the class (e.g. `.socicon-x:before { content: "\\e94b" }`), so the SVG
     looks exactly like the glyph free sites show today.
  3. PRO's own artwork, for icons the free fonts don't have.
  4. bin/icon-additions/<kit>/<name>.svg: icons neither plugin had, taken
     from newer releases of the sets (Font Awesome 7) or from Simple Icons
     (CC0 only). bin/icon-additions/sources.json says where each came from.

PRO keeps its own artwork for the icons it already had (includes/icons-svg-pro.php
in PRO): it saves that markup into posts, and the block editor flags a block
as invalid if the markup it rebuilds differs from what was saved.

The icon names come from both plugins' icon data, so the library covers
every icon either one offers.

Requires fontTools and brotli (WOFF2):
  python3 -m venv /tmp/fontenv && /tmp/fontenv/bin/pip install fonttools brotli
  /tmp/fontenv/bin/python bin/build-icon-library.py [--pro ../social-icons-widget-pro] [--check]

--check reports what would change and exits non-zero if anything would.
"""
import argparse
import json
import os
import re
import subprocess
import sys

from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont

FREE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(FREE, 'includes', 'icon-library')
ADDITIONS = os.path.join(FREE, 'bin', 'icon-additions')
WP_INCLUDES = os.path.join(FREE, '..', '..', '..', 'wp-includes')

# Where each kit comes from, for the file header (details in LICENSES.md).
SOURCES = {
    'socicon': 'WPZOOM Icons, based on the Socicon font. SIL Open Font License 1.1.',
    'genericon': 'Genericons by Joen Asmussen. GNU GPL v2 or later.',
    'academicons': 'Academicons by James Walsh and Katja Bercic. SIL Open Font License 1.1.',
    'dashicons': 'Dashicons, the WordPress icon font. GNU GPL v2 or later.',
    'fab': 'Font Awesome Free 5.11.2 (Brands) by Fonticons, Inc. Icons: CC BY 4.0.',
    'far': 'Font Awesome Free 5.11.2 (Regular) by Fonticons, Inc. Icons: CC BY 4.0.',
    'fas': 'Font Awesome Free 5.11.2 (Solid) by Fonticons, Inc. Icons: CC BY 4.0.',
    'fa': 'Font Awesome 4.7.0 by Dave Gandy. SIL Open Font License 1.1.',
}

# kit => (font file, stylesheet, class prefix)
KITS = {
    'socicon': ('assets/font/socicon.woff2', 'assets/css/wpzoom-socicon.css', 'socicon-'),
    'genericon': ('assets/font/Genericons.woff', 'assets/css/genericons.css', 'genericon-'),
    'academicons': ('assets/font/academicons.woff2', 'assets/css/academicons.min.css', 'academicons-'),
    'dashicons': (os.path.join(WP_INCLUDES, 'fonts/dashicons.woff2'), os.path.join(WP_INCLUDES, 'css/dashicons.css'), 'dashicons-'),
    'fab': ('assets/font/fa-brands-400.woff2', 'assets/css/font-awesome-5.min.css', 'fa-'),
    'far': ('assets/font/fa-regular-400.woff2', 'assets/css/font-awesome-5.min.css', 'fa-'),
    'fas': ('assets/font/fa-solid-900.woff2', 'assets/css/font-awesome-5.min.css', 'fa-'),
    'fa': ('assets/font/fontawesome-webfont.woff2', 'assets/css/font-awesome-3.min.css', 'fa-'),
}


def php_json(expr):
    """Evaluate a PHP expression that returns an array and decode it as JSON."""
    out = subprocess.run(['php', '-r', 'echo json_encode(' + expr + ');'], capture_output=True, text=True, check=True)
    return json.loads(out.stdout)


def icon_names(data, kit):
    return [i['icon'].strip() for i in data.get(kit, []) if isinstance(i, dict) and i.get('icon')]


def css_codepoints(path, prefix):
    """Class name => codepoint, from rules like `.a:before,.b:before{content:"\\f00d"}`."""
    css = open(path if os.path.isabs(path) else os.path.join(FREE, path)).read()
    out = {}
    for selectors, cp in re.findall(r'([^{}]+)\{\s*content:\s*["\']\\([0-9a-fA-F]+)["\']', css):
        for name in re.findall(re.escape(prefix) + r'([a-z0-9-]+):{1,2}before', selectors):
            out.setdefault(name, int(cp, 16))
    return out


def number(v):
    return ('%.1f' % v).rstrip('0').rstrip('.') if v != int(v) else str(int(v))


class FontSource:
    def __init__(self, font_path, css_path, prefix):
        path = font_path if os.path.isabs(font_path) else os.path.join(FREE, font_path)
        self.font = TTFont(path)
        self.glyphs = self.font.getGlyphSet()
        self.cmap = self.font.getBestCmap()
        self.ascent = self.font['hhea'].ascent
        self.height = self.ascent - self.font['hhea'].descent
        self.codepoints = css_codepoints(css_path, prefix)

    def svg(self, name):
        cp = self.codepoints.get(name)
        if cp is None or cp not in self.cmap:
            return None
        glyph = self.glyphs[self.cmap[cp]]
        pen = SVGPathPen(self.glyphs, ntos=number)
        # Font units are y-up from the baseline; flip into a y-down viewBox.
        glyph.draw(TransformPen(pen, (1, 0, 0, -1, 0, self.ascent)))
        d = pen.getCommands()
        if not d:
            return None
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d"><path d="%s"/></svg>' % (glyph.width, self.height, d)


def read_library(kit):
    path = os.path.join(OUT, kit + '.php')
    if not os.path.exists(path):
        return {}
    return php_json("include '%s'" % path.replace("'", "\\'"))


def php_string(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


def library_text(kit, icons):
    lines = [
        '<?php',
        '/**',
        ' * %s icons as SVG: %d icons.' % (kit, len(icons)),
        ' *',
        ' * ' + SOURCES[kit],
        ' * Sources, copyright notices and licenses: LICENSES.md in this folder.',
        ' *',
        ' * Generated by bin/build-icon-library.py; do not edit by hand.',
        ' * Shared with the PRO plugin through bin/sync-from-free.sh.',
        ' *',
        ' * @package WPZOOM_Social_Icons',
        ' */',
        '',
        'return array(',
    ]
    for name in icons:
        lines.append('\t%s => %s,' % (php_string(name), php_string(icons[name])))
    lines += [');', '']
    return '\n'.join(lines)


def write_library(kit, text):
    os.makedirs(OUT, exist_ok=True)
    with open(os.path.join(OUT, kit + '.php'), 'w') as fh:
        fh.write(text)


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--pro', default=os.path.join(FREE, '..', 'social-icons-widget-pro'))
    ap.add_argument('--check', action='store_true')
    args = ap.parse_args()

    free = php_json("include '%s/includes/icons-data.php'" % FREE)
    # PRO's icon names (icons-meta.php) and its own artwork (icons-svg-pro.php).
    meta_file = os.path.join(args.pro, 'includes', 'icons-meta.php')
    art_file = os.path.join(args.pro, 'includes', 'icons-svg-pro.php')
    pro = php_json("include '%s'" % meta_file) if os.path.exists(meta_file) else {}
    pro_art = php_json("include '%s'" % art_file) if os.path.exists(art_file) else {}

    changed = False
    for kit, (font_path, css_path, prefix) in KITS.items():
        names = list(dict.fromkeys(icon_names(free, kit) + icon_names(pro, kit)))
        existing = read_library(kit)
        pro_svg = pro_art.get(kit) or {}
        source = None
        icons, origin, missing = {}, {'library': 0, 'pro': 0, 'font': 0, 'added': 0}, []
        # Keep the order of the existing file, then add new names.
        for name in list(existing) + [n for n in names if n not in existing]:
            if name in existing:
                icons[name] = existing[name]; origin['library'] += 1
                continue
            source = source or FontSource(font_path, css_path, prefix)
            svg = source.svg(name)
            if svg:
                icons[name] = svg; origin['font'] += 1
                continue
            added = os.path.join(ADDITIONS, kit, name + '.svg')
            if name in pro_svg:
                icons[name] = pro_svg[name]; origin['pro'] += 1
            elif os.path.exists(added):
                icons[name] = open(added).read().strip(); origin['added'] += 1
            else:
                missing.append(name)
        print('%-11s %4d icons  (kept %d, from PRO %d, from font %d, added %d)%s' % (
            kit, len(icons), origin['library'], origin['pro'], origin['font'], origin['added'],
            ('  no source: ' + ', '.join(missing)) if missing else ''))
        text = library_text(kit, icons)
        path = os.path.join(OUT, kit + '.php')
        if not os.path.exists(path) or open(path).read() != text:
            changed = True
            if not args.check:
                write_library(kit, text)
    if args.check and changed:
        print('The library is out of date. Run bin/build-icon-library.py.')
        sys.exit(1)


if __name__ == '__main__':
    main()
