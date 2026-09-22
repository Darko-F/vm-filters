"""Execute the real generated predicates against a portable database fixture."""
import json
import os
import sqlite3
import subprocess
from pathlib import Path

root = Path(__file__).resolve().parents[1]
sql = json.loads(subprocess.check_output([os.environ.get('PHP_BINARY', 'php'), str(root / 'tests/properties.php'), '--sql']))
db = sqlite3.connect(':memory:')
db.executescript('''
CREATE TABLE virtuemart_products (virtuemart_product_id INTEGER, product_width REAL, product_lwh_uom TEXT, product_weight REAL, product_weight_uom TEXT, published INTEGER, category_id INTEGER);
CREATE TABLE virtuemart_customs (virtuemart_custom_id INTEGER, field_type TEXT, published INTEGER, admin_only INTEGER, is_hidden INTEGER, virtuemart_shoppergroup_id TEXT);
CREATE TABLE virtuemart_product_customfields (virtuemart_product_id INTEGER, virtuemart_custom_id INTEGER, customfield_value TEXT);
INSERT INTO virtuemart_customs VALUES (15,'P',1,0,0,NULL),(16,'P',1,0,0,NULL),(17,'P',1,1,0,NULL);
INSERT INTO virtuemart_products VALUES
 (1,70,'CM',8,'KG',1,9), (2,0.7,'M',8000,'G',1,9),
 (3,700,'MM',8000000,'MG',1,9), (4,90,'CM',8,'KG',1,9),
 (5,70,'CM',12,'KG',1,9), (6,70,'CM',8,'KG',0,9),
 (7,70,'CM',8,'KG',1,10), (8,0,'CM',0,'KG',1,9),
 (9,70,'UNKNOWN',8,'KG',1,9), (10,70,'CM',8,'UNKNOWN',1,9),
 (11,70,'CM',8,'KG',1,9), (12,1,'IN',1,'KG',1,9), (13,2.54,'CM',1,'KG',1,9);
''')
for product in range(1, 14):
    if product != 11:  # Positive dimensions without the Property assignment must not match.
        db.execute('INSERT INTO virtuemart_product_customfields VALUES (?,15,?)', (product, 'product_width'))
        db.execute('INSERT INTO virtuemart_product_customfields VALUES (?,16,?)', (product, 'product_weight'))

def matches(predicates):
    query = 'SELECT p.virtuemart_product_id FROM virtuemart_products AS p WHERE p.published = 1 AND p.category_id = 9 AND ' + ' AND '.join(predicates)
    return [row[0] for row in db.execute(query.replace('#__', ''))]

assert matches(sql['combined']) == [1, 2, 3]
assert matches(sql['inch']) == [12, 13]
assert matches(sql['invalid']) == []
db.execute('UPDATE virtuemart_customs SET admin_only = 1 WHERE virtuemart_custom_id = 15')
assert matches(sql['combined']) == []
print('SQL fixture: mixed units, inclusive bounds, AND combination, publication/category, missing assignments, invalid input and private fields passed.')
