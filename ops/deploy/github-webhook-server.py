#!/usr/bin/env python
import hashlib
import hmac
import json
import os
import subprocess
try:
    from BaseHTTPServer import BaseHTTPRequestHandler, HTTPServer
    from SocketServer import ThreadingMixIn
except ImportError:
    from http.server import BaseHTTPRequestHandler, HTTPServer
    from socketserver import ThreadingMixIn

SECRET = os.environ.get('GITHUB_WEBHOOK_SECRET', '')
HOST = '127.0.0.1'
PORT = 19095
SCRIPT = '/www/xmqibu-ai/ops/deploy/deploy-from-git.sh'
LOG = '/var/log/xmqibu-ai-deploy.log'
MAX_BODY = 1024 * 1024

def constant_time_equal(left, right):
    if len(left) != len(right):
        return False
    result = 0
    for a, b in zip(bytearray(left.encode('utf-8')), bytearray(right.encode('utf-8'))):
        result |= a ^ b
    return result == 0

class ThreadingHTTPServer(ThreadingMixIn, HTTPServer):
    daemon_threads = True

class Handler(BaseHTTPRequestHandler):
    def respond(self, status, data):
        body = json.dumps(data).encode('utf-8')
        self.send_response(status)
        self.send_header('Content-Type', 'application/json')
        self.send_header('Content-Length', str(len(body)))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if self.path == '/healthz':
            self.respond(200, {'ok': True})
        else:
            self.respond(404, {'error': 'not_found'})

    def do_POST(self):
        if self.path != '/-/github-webhook':
            return self.respond(404, {'error': 'not_found'})
        try:
            length = int(self.headers.get('Content-Length', '0'))
        except ValueError:
            length = 0
        if length < 1 or length > MAX_BODY:
            return self.respond(413, {'error': 'invalid_body_size'})
        body = self.rfile.read(length)
        supplied = self.headers.get('X-Hub-Signature-256', '')
        expected = 'sha256=' + hmac.new(SECRET.encode(), body, hashlib.sha256).hexdigest()
        if not SECRET or not constant_time_equal(supplied, expected):
            return self.respond(401, {'error': 'invalid_signature'})
        event = self.headers.get('X-GitHub-Event', '')
        if event == 'ping':
            return self.respond(200, {'ok': True, 'event': 'ping'})
        if event != 'push':
            return self.respond(202, {'ok': True, 'ignored': 'event'})
        try:
            payload = json.loads(body)
        except (ValueError, UnicodeDecodeError):
            return self.respond(400, {'error': 'invalid_json'})
        if payload.get('repository', {}).get('full_name') != 'yanlc-1/xmqibu-ai' or payload.get('ref') != 'refs/heads/main':
            return self.respond(202, {'ok': True, 'ignored': 'repository_or_ref'})
        with open(LOG, 'a') as output:
            subprocess.Popen([SCRIPT], stdin=open(os.devnull, 'r'), stdout=output, stderr=subprocess.STDOUT, close_fds=True, preexec_fn=os.setsid)
        self.respond(202, {'ok': True, 'accepted': True})

    def log_message(self, fmt, *args):
        pass

if __name__ == '__main__':
    if not SECRET:
        raise SystemExit('GITHUB_WEBHOOK_SECRET is required')
    ThreadingHTTPServer((HOST, PORT), Handler).serve_forever()
