import json, os, urllib.request

URL = "https://tienda.vempra.tur.ar/wp-json/royal-mcp/v1/mcp"
# La clave vive en tools/clave.txt, que no entra al repositorio.
KEY = open(os.path.join(os.path.dirname(os.path.abspath(__file__)), "clave.txt")).read().strip()
_S = [None]

def _post(payload, sid):
    h = {"Content-Type": "application/json", "Accept": "application/json, text/event-stream",
         "Authorization": "Bearer " + KEY}
    if sid: h["Mcp-Session-Id"] = sid
    req = urllib.request.Request(URL, json.dumps(payload).encode(), h)
    with urllib.request.urlopen(req, timeout=90) as r:
        body = r.read().decode()
        sid_out = r.headers.get("Mcp-Session-Id")
    for line in body.splitlines():
        if line.startswith("data:"): body = line[5:].strip(); break
    try: return sid_out, json.loads(body)
    except Exception: return sid_out, body

def _init():
    if _S[0] is None:
        sid, _ = _post({"jsonrpc":"2.0","id":1,"method":"initialize","params":{
            "protocolVersion":"2024-11-05","capabilities":{},
            "clientInfo":{"name":"vempra","version":"1"}}}, None)
        _S[0] = sid
        _post({"jsonrpc":"2.0","method":"notifications/initialized"}, sid)
    return _S[0]

def call(tool, args):
    sid = _init()
    _, r = _post({"jsonrpc":"2.0","id":2,"method":"tools/call",
                  "params":{"name":tool,"arguments":args}}, sid)
    if isinstance(r, dict):
        if "error" in r: return "ERROR: " + json.dumps(r["error"], ensure_ascii=False)
        c = r.get("result", {}).get("content", [])
        if c and isinstance(c, list):
            t = c[0].get("text", "")
            try: return json.loads(t)
            except Exception: return t
    return r

def tools():
    sid = _init()
    _, r = _post({"jsonrpc":"2.0","id":3,"method":"tools/list","params":{}}, sid)
    return [t["name"] for t in r.get("result", {}).get("tools", [])]
