import re

with open('profiler_output3.html', 'r', encoding='utf-8') as f:
    data = f.read()

# Find the metrics section
idx = data.find('Total Issues')
if idx >= 0:
    context = data[idx:idx+1000]
    # Extract all numbers from the metrics section
    metrics = re.findall(r'<span class="value[^"]*">(\d+)</span>\s*<span class="label">([^<]+)</span>', context)
    for val, label in metrics:
        print(f'{label.strip()}: {val}')

# Find the tab section with counts
tab_section = re.findall(r'<li[^>]*>\s*<a[^>]*>([^<]*(?:<[^>]*>[^<]*)*)</a>\s*</li>', data[:20000])

# Better approach - find the category tabs
for cat in ['Security', 'Integrity', 'Configuration', 'Performance']:
    idx = data.find(f'>{cat}')
    if idx >= 0:
        context = data[max(0,idx-100):idx+200]
        count = re.findall(r'(\d+)', context)
        print(f'{cat} context counts: {count}')
        # Get surrounding HTML
        nums = re.findall(r'<span[^>]*>(\d+)</span>', context)
        print(f'  Span numbers: {nums}')

# Find summary numbers
summary = data.find('metrics')
section = data[summary:summary+2000] if summary >= 0 else ''
all_values = re.findall(r'<span class="value[^"]*">(\d+)</span>', section)
all_labels = re.findall(r'<span class="label">([^<]+)</span>', section)
print('\nAll metrics:')
for v, l in zip(all_values, all_labels):
    print(f'  {l}: {v}')
