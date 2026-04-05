import urllib.request, re, sys
sys.stdout.reconfigure(encoding='utf-8')

r = urllib.request.urlopen('http://127.0.0.1:8000/')
html = r.read().decode('utf-8')

tokens = re.findall(r'/_profiler/([a-f0-9]+)', html)
if tokens:
    token = tokens[0]
    print(f'New profiler token: {token}')
    
    r2 = urllib.request.urlopen(f'http://127.0.0.1:8000/_profiler/{token}?panel=doctrine_doctor')
    profiler_html = r2.read().decode('utf-8')
    with open('profiler_output2.html', 'w', encoding='utf-8') as f:
        f.write(profiler_html)
    
    total = re.search(r'(\d+)Total Issues', profiler_html)
    critical = re.search(r'(\d+)Critical', profiler_html)
    warnings = re.search(r'(\d+)Warnings', profiler_html)
    info = re.search(r'(\d+)Info', profiler_html)
    perf = re.search(r'Performance \((\d+)\)', profiler_html)
    sec = re.search(r'Security \((\d+)\)', profiler_html)
    integ = re.search(r'Integrity \((\d+)\)', profiler_html)
    config = re.search(r'Configuration \((\d+)\)', profiler_html)
    
    print(f'Total Issues: {total.group(1) if total else "?"}')
    print(f'Critical: {critical.group(1) if critical else "?"}')
    print(f'Warnings: {warnings.group(1) if warnings else "?"}')
    print(f'Info: {info.group(1) if info else "?"}')
    print(f'Performance: {perf.group(1) if perf else "?"}')
    print(f'Security: {sec.group(1) if sec else "?"}')
    print(f'Integrity: {integ.group(1) if integ else "?"}')
    print(f'Configuration: {config.group(1) if config else "?"}')
else:
    print('No profiler token found')
