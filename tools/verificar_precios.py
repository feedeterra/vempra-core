"""Lee las fichas en vivo y compara lo que se muestra contra lo que se cobra.

Pide cada pagina con un parametro al azar para saltear la cache de LiteSpeed:
con un parametro repetido el servidor devuelve la version vieja y parece que
el cambio no se aplico.
"""
import html
import random
import re
import sys
import time
import urllib.request

sys.path.insert(0, 'tools')
import mcpprod as mcp

PRODUCTOS = [110, 122, 123, 124, 125, 126, 127, 128, 129, 130,
             131, 132, 133, 134, 237, 369, 370, 371]


def numero(txt):
    if not txt:
        return None
    limpio = re.sub(r'[^\d.]', '', txt.replace(',', ''))
    return float(limpio) if limpio else None


def ficha(pid):
    url = 'https://tienda.vempra.tur.ar/?p=%d&nc=%d' % (pid, random.randrange(10 ** 8))
    req = urllib.request.Request(url, headers={
        'User-Agent': 'Mozilla/5.0', 'Cache-Control': 'no-cache'})
    return urllib.request.urlopen(req, timeout=30).read().decode('utf-8', 'replace')


print('%4s %11s %11s %11s  %s' % ('ID', 'muestra', 'cobra', 'GTM', 'producto'))
problemas = []
for pid in PRODUCTOS:
    try:
        h = ficha(pid)
    except Exception as exc:
        print(pid, 'FALLO', exc)
        continue

    visible = (re.findall(r'tour_price">\s*([^<\s]+)', h) or [None])[0]

    datos = re.search(r'name="gtm4wp_product_data"\s+value="([^"]*)"', h)
    datos = html.unescape(datos.group(1)) if datos else ''
    gtm = re.search(r'"price"\s*:\s*([^,}]+)', datos)
    nombre = re.search(r'"item_name"\s*:\s*"([^"]*)"', datos)

    adulto = None
    for persona in sorted(set(int(x) for x in
                              re.findall(r'wc_bookings_field_persons_(\d+)', h))):
        etiqueta = re.search(
            r'for="wc_bookings_field_persons_%d">([^<]*)<' % persona, h)
        if etiqueta and 'dulto' in etiqueta.group(1):
            adulto = persona
            break

    cobra = None
    if adulto:
        meta = mcp.call('wp_get_post_meta', {'post_id': adulto})
        time.sleep(2)
        if isinstance(meta, dict):
            cobra = (meta.get('cost') or [None])[0]

    marca = ''
    if numero(visible) is not None and numero(cobra) is not None:
        if abs(numero(visible) - numero(cobra)) > 0.5:
            marca = '  <<< MUESTRA != COBRA'
            problemas.append(pid)
    if gtm and numero(visible) is not None and abs(numero(gtm.group(1)) - numero(visible)) > 0.5:
        marca += '  <<< GTM != MUESTRA'
        if pid not in problemas:
            problemas.append(pid)

    print('%4d %11s %11s %11s  %s%s' % (
        pid, visible or '-', cobra or '-',
        gtm.group(1) if gtm else '?',
        (nombre.group(1) if nombre else '')[:38], marca))

print()
print('problemas:', problemas if problemas else 'ninguno')
