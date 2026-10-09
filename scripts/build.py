#!/usr/bin/env python3
"""Build the module + system plugin as one deterministic Joomla package."""
from pathlib import Path
import hashlib
import xml.etree.ElementTree as ET
import zipfile
import io

root = Path(__file__).resolve().parents[1]
manifest = ET.parse(root / 'package/pkg_vm_smartfilters.xml').getroot()
version = manifest.findtext('version')

def archive_bytes(files):
    buffer = io.BytesIO()
    with zipfile.ZipFile(buffer, 'w', zipfile.ZIP_DEFLATED) as archive:
        for name, content in sorted(files.items()):
            info = zipfile.ZipInfo(name, (2026, 9, 22, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, content)
    with zipfile.ZipFile(buffer) as archive:
        assert archive.testzip() is None
    return buffer.getvalue()

def source_files(path):
    return {str(p.relative_to(path)): p.read_bytes() for p in path.rglob('*') if p.is_file()}

files = source_files(root / 'package')
for name, xml in [('mod_vm_smartfilters', 'mod_vm_smartfilters.xml'), ('plg_system_vmpropertyfilters', 'vmpropertyfilters.xml'), ('plg_installer_vmsmartfiltersupdatekey', 'vmsmartfiltersupdatekey.xml')]:
    source = root / name
    child = ET.parse(source / xml).getroot()
    assert child.findtext('version') == version
    for node in child.findall('./files/*'):
        assert (source / node.text).exists(), node.text
    languages = child.find('languages')
    for node in languages if languages is not None else []:
        assert (source / languages.get('folder', '') / node.text).is_file(), node.text
    for node in child.findall('./media/folder'):
        assert (source / 'media' / node.text).is_dir(), node.text
    files[f'packages/{name}-{version}.zip'] = archive_bytes(source_files(source))
for node in manifest.findall('./files/file'):
    assert 'packages/' + node.text in files, node.text
target = root / 'dist' / f'pkg_vm_smartfilters_v{version}.zip'
target.parent.mkdir(exist_ok=True)
target.write_bytes(archive_bytes(files))
with zipfile.ZipFile(target) as archive:
    assert archive.testzip() is None
    assert 'pkg_vm_smartfilters.xml' in archive.namelist()
checksum = hashlib.sha256(target.read_bytes()).hexdigest()
target.with_suffix('.zip.sha256').write_text(f'{checksum}  {target.name}\n')
print(f'Built and verified {target} ({target.stat().st_size:,} bytes)')

# The same static-feed and protected-download service used by BillHelper.
updates = ET.Element('updates')
entry = ET.SubElement(updates, 'update')
for key, value in {
    'name': 'VM Smart Filters',
    'description': 'String custom-field and dimension/weight Property filters for VirtueMart 4.8.4 and later compatible releases.',
    'element': 'pkg_vm_smartfilters', 'type': 'package', 'client': 'site',
    'version': version,
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
ET.SubElement(entry, 'dlid', {'prefix': 'key=', 'suffix': ''})
ET.SubElement(entry, 'php_minimum').text = '8.1.0'
ET.indent(updates, space='  ')
feed = root / 'updates' / 'vm-smartfilters-package.xml'
feed.parent.mkdir(exist_ok=True)
ET.ElementTree(updates).write(feed, encoding='utf-8', xml_declaration=True)
print(f'Generated {feed} with package SHA-256.')
