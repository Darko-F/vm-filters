#!/usr/bin/env python3
"""Build and verify the installable archive, without including development files."""
from pathlib import Path
import hashlib
import xml.etree.ElementTree as ET
import zipfile

root = Path(__file__).resolve().parents[1]
source = root / 'mod_vm_smartfilters'
manifest = ET.parse(source / 'mod_vm_smartfilters.xml').getroot()
assert manifest.attrib == {'type': 'module', 'client': 'site', 'method': 'upgrade'}
for node in manifest.findall('./files/*'):
    assert (source / node.text).exists(), node.text
for node in manifest.findall('./languages/language'):
    assert (source / node.text).is_file(), node.text
for node in manifest.findall('./media/folder'):
    assert (source / 'media' / node.text).is_dir(), node.text
target = root / 'dist' / f'mod_vm_smartfilters-{manifest.findtext("version")}.zip'
target.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(target, 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in sorted(source.rglob('*')):
        if path.is_file():
            info = zipfile.ZipInfo(str(path.relative_to(source)), (2026, 9, 22, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, path.read_bytes())
with zipfile.ZipFile(target) as archive:
    assert archive.testzip() is None
    assert 'mod_vm_smartfilters.xml' in archive.namelist()
checksum = hashlib.sha256(target.read_bytes()).hexdigest()
target.with_suffix('.zip.sha256').write_text(f'{checksum}  {target.name}\n')
print(f'Built and verified {target} ({target.stat().st_size:,} bytes)')

# The same static-feed and protected-download service used by BillHelper.
updates = ET.Element('updates')
entry = ET.SubElement(updates, 'update')
for key, value in {
    'name': 'VM Smart Filters',
    'description': 'Bootstrap 5 styled searchable String custom-field filters for VirtueMart 4.8.4 and later compatible releases.',
    'element': 'mod_vm_smartfilters', 'type': 'module', 'client': 'site',
    'version': manifest.findtext('version'),
}.items():
    ET.SubElement(entry, key).text = value
ET.SubElement(entry, 'infourl', {'title': 'VM Smart Filters'}).text = 'https://github.com/Darko-F/vm-filters'
downloads = ET.SubElement(entry, 'downloads')
ET.SubElement(downloads, 'downloadurl', {'type': 'full', 'format': 'zip'}).text = (
    'https://shop.topoweryou.com/index.php?option=com_vmupdatekeymanager'
    f'&task=download.get&file={target.name}&channel=update'
)
ET.SubElement(ET.SubElement(entry, 'tags'), 'tag').text = 'stable'
ET.SubElement(entry, 'sha256').text = checksum
ET.SubElement(entry, 'maintainer').text = 'topoweryou.com'
ET.SubElement(entry, 'maintainerurl').text = 'https://topoweryou.com'
ET.SubElement(entry, 'targetplatform', {'name': 'joomla', 'version': r'(4\.4\..*|5\..*|6\..*)'})
ET.SubElement(entry, 'php_minimum').text = '8.1.0'
ET.indent(updates, space='  ')
feed = root / 'updates' / 'vm-smartfilters.xml'
feed.parent.mkdir(exist_ok=True)
ET.ElementTree(updates).write(feed, encoding='utf-8', xml_declaration=True)
print(f'Generated {feed} with package SHA-256.')
