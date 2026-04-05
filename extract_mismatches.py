import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('profiler_output.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Find all type mismatch entries
pattern = r'Type Mismatch: (\w+)::\\\$(\w+).*?Expected:\s*([^<\n]+).*?Actual:\s*([^<\n]+)'
mismatches = re.findall(pattern, html, re.DOTALL)

print(f"Found {len(mismatches)} type mismatch issues:")

by_entity = {}
for entity, field, expected, actual in mismatches:
    expected = expected.strip()
    actual = actual.strip()
    if entity not in by_entity:
        by_entity[entity] = []
    by_entity[entity].append({
        'field': field,
        'expected': expected,
        'actual': actual
    })

for entity in sorted(by_entity.keys()):
    fields = by_entity[entity]
    print(f'\n{entity} ({len(fields)} mismatches):')
    for f in fields:
        print(f'  ${f["field"]}: expected="{f["expected"]}", actual="{f["actual"]}"')
