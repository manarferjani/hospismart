import re
from collections import Counter

with open('profiler_final2.html', 'r', encoding='utf-8') as f:
    data = f.read()

print(f'File size: {len(data)} bytes')

# Parse metrics
idx = data.find('Total Issues')
if idx >= 0:
    context = data[idx:idx+1000]
    metrics = re.findall(r'<span class="value[^"]*">(\d+)</span>\s*<span class="label">([^<]+)</span>', context)
    print('\n=== METRICS ===')
    for val, label in metrics:
        print(f'  {label.strip()}: {val}')
else:
    print('Total Issues NOT FOUND')

# Count issue types
print('\n=== Issue Types ===')
for p in ['property_type_mismatch', 'sensitive_property_exposed', 'mutable_datetime', 'missing_cascade', 'missing_orphan', 'public_setter', 'blameable', 'embeddable', 'table_naming', 'timezone', 'innodb', 'collation', 'strict_mode', 'acid']:
    count = len(re.findall(p, data, re.IGNORECASE))
    if count > 0:
        print(f'  {p}: {count}')

# Entity references
entities = re.findall(r'App\\\\Entity\\\\(\w+)', data)
print(f'\n=== Entities with issues ===')
print(f'  {dict(Counter(entities))}')

# Headers
headers = re.findall(r'<h[45][^>]*>(.*?)</h[45]>', data)
print(f'\n=== Section Headers ===')
for h in headers:
    h_clean = re.sub(r'<[^>]+>', '', h).strip()
    if h_clean and 'Theme' not in h_clean and 'Page' not in h_clean:
        print(f'  {h_clean}')
