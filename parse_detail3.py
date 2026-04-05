import re

with open('profiler_output3.html', 'r', encoding='utf-8') as f:
    data = f.read()

# Look for specific Doctrine Doctor issue patterns
# The issues are typically in card/panel format

# Search for property_type_mismatch, sensitive_property_exposed, etc.
patterns = [
    'property_type_mismatch',
    'sensitive_property_exposed', 
    'missing_cascade',
    'missing_orphan_removal',
    'mutable_datetime',
    'missing_blameable',
    'timezone',
    'innodb',
    'collation',
    'strict_mode',
    'acid',
    'buffer_pool',
]

for p in patterns:
    count = len(re.findall(p, data, re.IGNORECASE))
    if count > 0:
        print(f'{p}: {count} occurrences')

# Try to find issue cards/sections
print('\n--- Looking for card-like sections ---')
# Find h4 or h5 headers which often are issue titles
headers = re.findall(r'<h[45][^>]*>(.*?)</h[45]>', data)
for h in headers:
    h_clean = re.sub(r'<[^>]+>', '', h).strip()
    if h_clean:
        print(f'  Header: {h_clean}')

# Look for specific text patterns that indicate issues
print('\n--- Issue text patterns ---')
issue_texts = re.findall(r'(?:Warning|Critical|Info):\s*([^\n<]+)', data)
for t in issue_texts[:30]:
    print(f'  {t.strip()[:150]}')

# Look for "App\Entity" followed by property info
entity_props = re.findall(r'App(?:\\\\|\\)Entity(?:\\\\|\\)(\w+)(?:\\\\|\\|\.)(\w+)', data)
print(f'\nEntity.Property references:')
for ent, prop in sorted(set(entity_props)):
    print(f'  {ent}.{prop}')

# Search for descriptive text near Entity references
for m in re.finditer(r'App\\\\Entity\\\\(\w+)', data):
    start = max(0, m.start() - 200)
    end = min(len(data), m.end() + 200)
    context = data[start:end]
    context_clean = re.sub(r'<[^>]+>', ' ', context)
    context_clean = re.sub(r'\s+', ' ', context_clean).strip()
    if len(context_clean) > 20:
        print(f'\n  Context: ...{context_clean[:300]}...')
