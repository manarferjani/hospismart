import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('profiler_output2.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Remove HTML tags  
clean = re.sub(r'<[^>]+>', '\n', html)
clean = re.sub(r'\n+', '\n', clean)

# Extract all lines
lines = [l.strip() for l in clean.split('\n') if l.strip()]

# Find Security section
print("=" * 80)
print("SECURITY ISSUES (3)")
print("=" * 80)
in_security = False
in_integrity = False
in_config = False

security_text = []
integrity_text = []
config_text = []

for i, line in enumerate(lines):
    if 'Security (' in line:
        in_security = True
        in_integrity = False
        in_config = False
        continue
    if 'Integrity (' in line:
        in_security = False
        in_integrity = True
        in_config = False
        continue
    if 'Configuration (' in line:
        in_security = False
        in_integrity = False
        in_config = True
        continue
    if 'Slowest Queries' in line:
        in_config = False
        continue
        
    if in_security:
        security_text.append(line)
    elif in_integrity:
        integrity_text.append(line)
    elif in_config:
        config_text.append(line)

# Print Security issues
for line in security_text[:200]:
    print(line)

print("\n" + "=" * 80)
print("INTEGRITY ISSUES (101) - First 500 lines")
print("=" * 80)
for line in integrity_text[:500]:
    print(line)

print("\n" + "=" * 80)
print("CONFIGURATION ISSUES (8)")
print("=" * 80)
for line in config_text[:300]:
    print(line)
