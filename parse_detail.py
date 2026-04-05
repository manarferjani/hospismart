import re

with open('profiler_output3.html', 'r', encoding='utf-8') as f:
    data = f.read()

# Find all issue descriptions - look for table rows with issue details
# Each issue has a severity, entity, property, and description
issues = re.findall(r'<tr[^>]*class="[^"]*(?:status-(?:error|warning|info))[^"]*"[^>]*>(.*?)</tr>', data, re.DOTALL)
print(f'Found {len(issues)} issue rows')

# Alternative: find all issue descriptions
issue_blocks = re.findall(r'class="(?:sf-)?(?:status-)?(critical|warning|info)"[^>]*>.*?</(?:td|span|div)>', data, re.DOTALL)
print(f'Issue blocks found: {len(issue_blocks)}')

# Find entity names mentioned in issues
entity_issues = re.findall(r'App\\\\Entity\\\\(\w+)', data)
print(f'\nEntities with issues: {sorted(set(entity_issues))}')

# Count per entity
from collections import Counter
entity_counts = Counter(entity_issues)
for ent, count in entity_counts.most_common():
    print(f'  {ent}: {count}')

# Find property names in issues
props = re.findall(r'property\s+(?:&quot;|")(\w+)(?:&quot;|")', data)
print(f'\nProperties with issues: {sorted(set(props))}')

# Find specific issue types
issue_types = re.findall(r'<strong>([^<]+)</strong>', data)
type_counts = Counter(issue_types)
print(f'\nIssue types:')
for t, c in type_counts.most_common(20):
    if any(kw in t.lower() for kw in ['mismatch', 'missing', 'security', 'mutable', 'cascade', 'orphan', 'timezone', 'type', 'config', 'blob', 'collat', 'innodb', 'strict', 'acid']):
        print(f'  {t}: {c}')
