import os
import zipfile
import re

def create_zip(source_dir, out_zip_path, slug):
    if os.path.exists(out_zip_path):
        os.remove(out_zip_path)
    
    skip_items = {'.git', '.github', 'node_modules', '.DS_Store', 'Thumbs.db', 'ehthumbs.db', 'Desktop.ini'}
    
    files_added = 0
    with zipfile.ZipFile(out_zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
        for root, dirs, files in os.walk(source_dir):
            dirs[:] = [d for d in dirs if d not in skip_items and not d.startswith('.')]
            
            for file in sorted(files):
                if file in skip_items or file.endswith('.swp') or file.endswith('~'):
                    continue
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, source_dir)
                arc_name = f"{slug}/" + rel_path.replace("\\", "/")
                
                zinfo = zipfile.ZipInfo(arc_name)
                zinfo.external_attr = 0o644 << 16
                zinfo.compress_type = zipfile.ZIP_DEFLATED
                with open(full_path, 'rb') as f:
                    zf.writestr(zinfo, f.read())
                files_added += 1
                
    size_kb = round(os.path.getsize(out_zip_path) / 1024, 1)
    print(f"{os.path.basename(out_zip_path)}  {files_added} files  {size_kb} KB")

root_dir = os.path.abspath(".")
dist_dir = os.path.join(root_dir, "dist")
os.makedirs(dist_dir, exist_ok=True)

style_css = os.path.join(root_dir, "simple-bangla", "style.css")
with open(style_css, "r", encoding="utf-8") as f:
    m = re.search(r"Version:\s*(.+)", f.read())
    theme_ver = m.group(1).strip() if m else "1.0.0"

plugin_php = os.path.join(root_dir, "simple-bangla-cms", "simple-bangla-cms.php")
with open(plugin_php, "r", encoding="utf-8") as f:
    m = re.search(r"Version:\s*(.+)", f.read())
    plugin_ver = m.group(1).strip() if m else "1.0.0"

create_zip(os.path.join(root_dir, "simple-bangla"), os.path.join(dist_dir, f"simple-bangla-{theme_ver}.zip"), "simple-bangla")
create_zip(os.path.join(root_dir, "simple-bangla-cms"), os.path.join(dist_dir, f"simple-bangla-cms-{plugin_ver}.zip"), "simple-bangla-cms")
