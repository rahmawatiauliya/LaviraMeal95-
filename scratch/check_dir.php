import re
import os
import subprocess

def find_bare_strings(content):
    # This is a simple regex-based heuristic to find raw strings inside JSX but outside <Text> tags.
    # We look for text nodes between JSX tags that are not <Text> tags.
    # Note: JSX tags look like <Tag ...> or </Tag>
    # We can strip out standard comments and check.
    pass

# Let's get the list of modified files using git
files = subprocess.check_output(['git', 'diff', '--name-only'], cwd='c:\\xampp\\htdocs\\project_lavirameal').decode('utf-8').splitlines()

js_files = [f for f in files if f.endswith('.js')]
print("Modified JS files:", js_files)

for f_path in js_files:
    full_path = os.path.join('c:\\xampp\\htdocs\\project_lavirameal', f_path)
    if not os.path.exists(full_path):
        continue
    with open(full_path, 'r', encoding='utf-8') as file:
        content = file.read()
        
    # Check for raw string templates or comments like // inside JSX
    # Let's search for typical React Native JSX errors:
    # 1. {variable && <Tag>} where variable is a string or number
    # 2. {/* comment */} vs // comment inside JSX
    # 3. Trailing spaces or symbols in JSX tags
    
    # Let's look for common patterns like:
    # {item.something && (
    # without !! or Boolean or ? :
    matches = re.findall(r'\{\s*([\w\.\?\!]+)\s*&&\s*\(', content)
    for m in matches:
        # If the variable name is not a boolean-like name, print it
        if not any(x in m.lower() for x in ['show', 'visible', 'loading', 'refreshing', 'scanned', 'active', 'has', 'is', 'error']):
            print(f"Potential && issue in {f_path}: {{{m} && ( ...")

    # Let's also check for literal text between tags that is not wrapped in <Text
    # e.g., <View>text</View>
    # Let's print all lines containing text directly after > or before < in a View/TouchableOpacity
    lines = content.splitlines()
    for i, line in enumerate(lines):
        # A simple check: if a line contains alphanumeric characters outside tags
        # and is within a JSX return block, let's print it for manual review
        # We can look for common tags: View, TouchableOpacity, ScrollView, Modal
        # if the line contains something like `<View> ... Alphanumeric` or `Alphanumeric ... </View>`
        if re.search(r'<(View|TouchableOpacity|ScrollView|Modal)[^>]*>\s*[a-zA-Z0-9_]', line) or re.search(r'[a-zA-Z0-9_]\s*</(View|TouchableOpacity|ScrollView|Modal)>', line):
            print(f"Bare text? {f_path}:{i+1}: {line.strip()}")
