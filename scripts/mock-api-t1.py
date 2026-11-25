#!/usr/bin/env python3
"""Mock enam endpoint Tim 1. Semua contoh respons dibaca dari docs/api/openapi-t1.yaml,
jadi mock ini tidak bisa menyimpang dari spesifikasi yang di-commit.

Jalankan: python3 scripts/mock-api-t1.py [--port 4010] [--host 127.0.0.1]

Contoh:
  curl -X POST localhost:4010/api/v1/auth/login -H 'Content-Type: application/json' \\
       -d '{"username":"1153000001","password":"SecretPassword123!"}'
  curl localhost:4010/api/v1/academic-periods/active -H 'Authorization: Bearer token-apa-saja'

Untuk mencoba respons error, tambahkan ?__status=401 (atau 403/404/422) sesuai status
yang didokumentasikan endpoint itu, misal /api/v1/students/std_x?__status=404
"""

import argparse
import json
import re
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

try:
    import yaml
except ModuleNotFoundError:
    sys.exit("Butuh PyYAML: python3 -m pip install --user pyyaml")

SPEC_PATH = Path(__file__).resolve().parent.parent / "docs" / "api" / "openapi-t1.yaml"
SPEC = yaml.safe_load(SPEC_PATH.read_text(encoding="utf-8"))
BASE_PATH = urlsplit(SPEC["servers"][0]["url"]).path.rstrip("/")


def resolve(node):
    while isinstance(node, dict) and "$ref" in node:
        cur = SPEC
        for part in node["$ref"].lstrip("#/").split("/"):
            cur = cur[part]
        node = cur
    return node


def compile_routes():
    routes = []
    for path, item in SPEC["paths"].items():
        pattern = "^" + re.sub(r"\{[^}]+\}", "[^/]+", path) + "$"
        for method, op in item.items():
            routes.append((method.upper(), re.compile(pattern), path, op))
    return routes


ROUTES = compile_routes()


def example_for(op, status):
    response = op["responses"].get(str(status))
    if response is None:
        return None
    body = resolve(response).get("content", {}).get("application/json", {})
    return body.get("example")


def documented_statuses(op):
    return sorted(int(s) for s in op["responses"])


class Handler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"

    def _cors(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Headers", "Authorization, Content-Type, Accept")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")

    def _send(self, status, payload):
        raw = json.dumps(payload, ensure_ascii=False).encode()
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=UTF-8")
        self.send_header("Content-Length", str(len(raw)))
        self._cors()
        self.end_headers()
        self.wfile.write(raw)

    def _error(self, status, etype, message, details=None):
        self._send(status, {
            "success": False,
            "code": status,
            "message": message,
            "error": {"type": etype, "details": details or []},
        })

    def do_OPTIONS(self):
        self.send_response(204)
        self._cors()
        self.send_header("Content-Length", "0")
        self.end_headers()

    def do_GET(self):
        self._handle("GET")

    def do_POST(self):
        self._handle("POST")

    def _handle(self, method):
        parts = urlsplit(self.path)
        path = parts.path
        if BASE_PATH and path.startswith(BASE_PATH):
            path = path[len(BASE_PATH):] or "/"
        query = parse_qs(parts.query)
        match = None
        for route_method, pattern, spec_path, op in ROUTES:
            if route_method == method and pattern.match(path):
                match = (spec_path, op)
                break
        if match is None:
            return self._error(404, "RESOURCE_NOT_FOUND", f"Tidak ada endpoint {method} {parts.path} di kontrak")

        spec_path, op = match
        forced = query.get("__status", [None])[0]
        if forced:
            try:
                payload = example_for(op, int(forced))
            except ValueError:
                payload = None
            if payload is None:
                return self._error(400, "VALIDATION_ERROR", f"{spec_path} hanya mendokumentasikan status {documented_statuses(op)}")
            return self._send(int(forced), payload)

        if op.get("security"):
            token = self.headers.get("Authorization", "")
            if not token.startswith("Bearer ") or len(token) <= len("Bearer "):
                return self._send(401, example_for(op, 401))

        length = int(self.headers.get("Content-Length") or 0)
        body = {}
        if length:
            try:
                body = json.loads(self.rfile.read(length) or b"{}")
            except json.JSONDecodeError:
                return self._send(422, example_for(op, 422) or {
                    "success": False, "code": 422, "message": "Validation failed for incoming request payload",
                    "error": {"type": "VALIDATION_ERROR", "details": [{"field": "body", "message": "Body bukan JSON yang sah"}]},
                })

        if spec_path == "/auth/login":
            if not body.get("username") or not body.get("password"):
                return self._send(422, example_for(op, 422))
            if body["password"] != SPEC["paths"]["/auth/login"]["post"]["requestBody"]["content"]["application/json"]["example"]["password"]:
                return self._send(401, example_for(op, 401))

        if spec_path == "/auth/change-password":
            expected = SPEC["paths"]["/auth/change-password"]["post"]["requestBody"]["content"]["application/json"]["example"]["currentPassword"]
            if body.get("currentPassword") != expected:
                return self._send(422, example_for(op, 422))

        return self._send(200, example_for(op, 200))

    def log_message(self, fmt, *args):
        sys.stderr.write("[mock] %s %s\n" % (self.command, self.path))


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Mock server SIA-API-INT v1.1 untuk API Tim 1")
    parser.add_argument("--port", type=int, default=4010)
    parser.add_argument("--host", default="0.0.0.0")
    args = parser.parse_args()
    print(f"Mock Tim 1 — {len(ROUTES)} operasi dari {SPEC_PATH.name}")
    print(f"Base URL: http://127.0.0.1:{args.port}/api/v1  (mis. POST /api/v1/auth/login)")
    print("Simulasi error: tambahkan ?__status=401|403|404|422")
    ThreadingHTTPServer((args.host, args.port), Handler).serve_forever()
