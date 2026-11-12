#!/usr/bin/env python3
"""Create smaller WebP siblings for existing theme images and WordPress uploads.

Requires Pillow with WebP support. Original files and WordPress metadata are never
changed. Existing siblings are left alone; rerunning handles newly added images.
"""
from pathlib import Path
from PIL import Image, ImageOps
import io

ROOT = Path(__file__).resolve().parents[1]
DIRECTORIES = (ROOT / 'wp-content/themes/rockpack/assets/images', ROOT / 'wp-content/uploads')
converted = skipped = saved = 0
for directory in DIRECTORIES:
    for source in sorted(directory.rglob('*')):
        if not source.is_file() or source.suffix.lower() not in ('.jpg', '.jpeg', '.png') or 'section-previews' in source.parts:
            continue
        target = source.with_name(source.name + '.webp')
        if target.exists():
            skipped += 1
            continue
        try:
            with Image.open(source) as im:
                if getattr(im, 'n_frames', 1) != 1 or im.getexif().get(274, 1) != 1:
                    skipped += 1
                    continue
                im.load()
                buffer = io.BytesIO()
                if source.suffix.lower() == '.png':
                    im.convert('RGBA' if 'A' in im.getbands() or 'transparency' in im.info else 'RGB').save(buffer, format='WEBP', lossless=True, method=6)
                else:
                    im.convert('RGB').save(buffer, format='WEBP', quality=88, method=6)
                data = buffer.getvalue()
                if len(data) >= source.stat().st_size * 0.9:
                    skipped += 1
                    continue
                with Image.open(io.BytesIO(data)) as result:
                    if result.size != im.size:
                        raise ValueError('dimensions changed')
                    if source.suffix.lower() == '.png' and im.convert('RGBA').tobytes() != result.convert('RGBA').tobytes():
                        raise ValueError('lossless PNG pixels changed')
                target.write_bytes(data)
                saved += source.stat().st_size - len(data)
                converted += 1
        except (OSError, ValueError) as exc:
            skipped += 1
            print(f'SKIP {source.relative_to(ROOT)}: {exc}')
print(f'Created {converted} WebP files; skipped {skipped}; saved {saved} bytes in converted files.')
