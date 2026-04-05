import re

with open('profiler_output3.html', 'r', encoding='utf-8') as f:
    data = f.read()

# Find total issues
totals = re.findall(r'Total Issues.*?(\d+)', data[:5000])
print('Total Issues matches:', totals)

# Find category counts  
cats = re.findall(r'(Security|Integrity|Configuration|Performance)\s*\((\d+)\)', data[:10000])
print('Category matches:', cats)

# Also search for 'Total Issues' context
idx = data.find('Total Issues')
if idx >= 0:
    print('Context around Total Issues:', repr(data[idx:idx+200]))

# Find badge counts in the sidebar
badges = re.findall(r'class="badge[^"]*">(\d+)<', data[:10000])
print('Badge counts:', badges)

# Search for severity indicators
critical = len(re.findall(r'class="[^"]*critical[^"]*"', data))
warning = len(re.findall(r'class="[^"]*warning[^"]*"', data))
info = len(re.findall(r'class="[^"]*\binfo\b[^"]*"', data))
print(f'Critical occurrences: {critical}')
print(f'Warning occurrences: {warning}')
print(f'Info occurrences: {info}')

# Look for the tab headers with counts
tabs = re.findall(r'<span[^>]*>(\w+)\s*<span[^>]*class="[^"]*badge[^"]*"[^>]*>(\d+)<', data)
print('Tabs with badges:', tabs)
