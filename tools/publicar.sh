#!/bin/sh
# Publica una version del plugin en GitHub para que la tienda la tome sola.
#
#   sh tools/publicar.sh 1.9.0 "Que cambio en esta version"
#
# Pasos: chequea el PHP, verifica que la version del archivo coincida, arma el
# ZIP con la carpeta vempra-core/ adentro, hace commit + tag y crea el release
# con el ZIP adjunto. En 12 horas (o al tocar "Buscar actualizacion" en
# Plugins) WordPress ofrece la actualizacion.
set -e
cd "$(dirname "$0")/.."

VERSION="$1"
NOTAS="$2"
[ -n "$VERSION" ] || { echo "uso: sh tools/publicar.sh <version> \"<notas>\""; exit 1; }

grep -q "VEMPRA_CORE_VERSION', '$VERSION'" build/vempra-core/vempra-core.php \
  || { echo "vempra-core.php no dice $VERSION: actualizá la version primero"; exit 1; }

python3 tools/chequear_php.py

ZIP="build/vempra-core-$VERSION.zip"
rm -f "$ZIP"
(cd build && zip -rq "vempra-core-$VERSION.zip" vempra-core -x '*.DS_Store')

git add -A
git commit -qm "v$VERSION: $NOTAS" || true
git tag -a "v$VERSION" -m "$NOTAS"
git push -q origin main --tags

gh release create "v$VERSION" "$ZIP#vempra-core.zip" --title "v$VERSION" --notes "$NOTAS"
echo "publicada v$VERSION"
