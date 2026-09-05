import sys, json
sys.path.insert(0, 'tools')
import mcp

for pid in (124, 371):
    m = mcp.call('wp_get_post_meta', {'post_id': pid})
    print('=== producto', pid)
    for k in sorted(m):
        if 'book' in k or 'price' in k or 'person' in k or 'cost' in k:
            print('  ', k, '=', m[k])
