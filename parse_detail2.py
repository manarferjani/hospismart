import re

with open('profiler_output3.html', 'r', encoding='utf-8') as f:
    data = f.read()

# Find the category sections
for category in ['Security', 'Integrity', 'Configuration', 'Performance']:
    idx = data.find(f'<h3>{category}')
    if idx < 0:
        idx = data.find(f'>{category}<')
    if idx >= 0:
        # Get a larger section after this header
        section = data[idx:idx+15000]
        # Find all issue text
        texts = re.findall(r'<(?:p|li|td|div)[^>]*class="[^"]*"[^>]*>(.*?)</(?:p|li|td|div)>', section, re.DOTALL)
        strong_texts = re.findall(r'<strong>(.*?)</strong>', section)
        descs = re.findall(r'<p[^>]*>(.*?)</p>', section, re.DOTALL)
        
        print(f'\n=== {category} (found at pos {idx}) ===')
        for s in strong_texts[:15]:
            s_clean = re.sub(r'<[^>]+>', '', s).strip()
            if s_clean:
                print(f'  STRONG: {s_clean}')
        for d in descs[:20]:
            d_clean = re.sub(r'<[^>]+>', '', d).strip()
            if d_clean and len(d_clean) > 10:
                print(f'  DESC: {d_clean[:200]}')

# Also search for all strong tags to find issue types
print('\n=== All issue-related strong tags ===')
all_strong = re.findall(r'<strong>(.*?)</strong>', data)
for s in all_strong:
    s_clean = re.sub(r'<[^>]+>', '', s).strip()
    if s_clean and any(kw in s_clean.lower() for kw in ['security', 'type', 'mismatch', 'missing', 'mutable', 'cascade', 'sensitive', 'timezone', 'collat', 'buffer', 'strict', 'acid', 'orphan', 'config', 'exposed', 'blameable', 'trait']):
        print(f'  {s_clean}')
