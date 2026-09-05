"""Compara el contenido de las fichas y de la pagina de tours entre
produccion y staging, y deja los textos en /tmp para poder revisarlos.

wp_get_post espera la clave 'id' (no 'post_id') y devuelve 'title' y
'content'; con 'post_id' contesta "Post not found" y parece que la ficha no
existiera.
"""
import difflib
import json
import sys
import time

sys.path.insert(0, 'tools')
import mcp as staging
import mcpprod as produccion

FICHAS = [528, 523, 525, 529, 538]
PAGINA = 331


def traer(cliente, post_id):
    r = cliente.call('wp_get_post', {'id': post_id})
    time.sleep(2)
    if not isinstance(r, dict):
        return None
    return {'titulo': r.get('title') or '', 'texto': r.get('content') or ''}


guardado = {}
for post_id in FICHAS + [PAGINA]:
    p = traer(produccion, post_id)
    s = traer(staging, post_id)
    guardado[post_id] = {'prod': p, 'stg': s}

    print('=' * 70)
    print('post', post_id)
    if not p or not s:
        print('  no se pudo leer  prod=%s  stg=%s' % (bool(p), bool(s)))
        continue

    print('  titulo prod:', p['titulo'][:72])
    print('  titulo stg :', s['titulo'][:72])
    if p['titulo'] != s['titulo']:
        print('  >>> LOS TITULOS DIFIEREN')
    print('  largo prod %d  stg %d  (%+d)' %
          (len(p['texto']), len(s['texto']), len(s['texto']) - len(p['texto'])))

    dif = list(difflib.unified_diff(
        p['texto'].splitlines(), s['texto'].splitlines(),
        'produccion', 'staging', lineterm='', n=1))
    if not dif:
        print('  contenido identico')
        continue
    for linea in dif[:60]:
        print('  ' + linea[:200])
    if len(dif) > 60:
        print('  ... %d lineas mas de diferencia' % (len(dif) - 60))

with open('/tmp/contenido.json', 'w') as f:
    json.dump(guardado, f, ensure_ascii=False, indent=1)
print()
print('textos guardados en /tmp/contenido.json')
