"""Control estructural del PHP del plugin, a falta de `php -l` en esta Mac.

Cuenta llaves, parentesis y corchetes solo dentro de las regiones <?php ... ?>
(los bloques de HTML/JS intercalados tienen sus propias llaves), despues de
sacar comentarios y cadenas. No reemplaza a un interprete: detecta un bloque
sin cerrar, no un punto y coma faltante.
"""
import glob
import os
import re
import sys

RAIZ = sys.argv[1] if len(sys.argv) > 1 else 'build/vempra-core'


def regiones_php(src):
    out, i = [], 0
    while True:
        a = src.find('<?php', i)
        if a == -1:
            break
        b = src.find('?>', a)
        if b == -1:
            out.append(src[a + 5:])
            break
        out.append(src[a + 5:b])
        i = b + 2
    return out


def limpiar(s):
    # Primero las cadenas y despues los comentarios: una URL entre comillas
    # ('https://...') tiene un "//" adentro y, si se sacaran antes los
    # comentarios, se comeria el resto del renglon con sus parentesis.
    s = re.sub(r'/\*.*?\*/', '', s, flags=re.S)
    s = re.sub(r"'(?:\\.|[^'\\])*'", "''", s)
    s = re.sub(r'"(?:\\.|[^"\\])*"', '""', s)
    return re.sub(r'(?m)//.*$', '', s)


archivos = sorted(glob.glob(os.path.join(RAIZ, '*.php')) +
                  glob.glob(os.path.join(RAIZ, 'inc', '*.php')))
mal = 0
for f in archivos:
    s = limpiar(''.join(regiones_php(open(f).read())))
    cuenta = {'{': 0, '(': 0, '[': 0}
    cierra = {'}': '{', ')': '(', ']': '['}
    for ch in s:
        if ch in cuenta:
            cuenta[ch] += 1
        elif ch in cierra:
            cuenta[cierra[ch]] -= 1
    ok = all(v == 0 for v in cuenta.values())
    mal += not ok
    print('%-40s %s' % (os.path.relpath(f, RAIZ), 'ok' if ok else 'DESBALANCEADO %s' % cuenta))

print()
print('archivos:', len(archivos), '| con problemas:', mal)
sys.exit(1 if mal else 0)
