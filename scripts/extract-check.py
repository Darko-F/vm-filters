#!/usr/bin/env python3
"""Expand this project's package and children for the JED static scanner."""
import sys
import zipfile
from pathlib import Path

source, target = Path(sys.argv[1]), Path(sys.argv[2])
assert not target.exists(), 'Use a fresh output directory'
with zipfile.ZipFile(source) as archive:
    archive.extractall(target)
for child in sorted((target / 'packages').glob('*.zip')):
    with zipfile.ZipFile(child) as archive:
        archive.extractall(child.with_suffix(''))
print(target)
