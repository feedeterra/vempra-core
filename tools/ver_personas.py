import sys, json
sys.path.insert(0, 'tools')
import mcp

posts = mcp.call('wp_get_posts', {'post_type': 'bookable_person', 'per_page': 100, 'status': 'any'})
print(type(posts), len(posts) if isinstance(posts, list) else posts)
if isinstance(posts, list):
    for p in posts:
        m = mcp.call('wp_get_post_meta', {'post_id': p['id']})
        print(p['id'], p.get('title'), '| costo=', m.get('cost'), '| block=', m.get('block_cost'))
