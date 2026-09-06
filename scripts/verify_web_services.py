#!/usr/bin/env python3
"""Exercise real HTTP exchanges using an isolated SQLite database and PHP server.

Uses the existing application routes, normal authentication, and disposable data.
No Http::fake() or diagnostic routes. Requires Python 3 and PHP with the project's
normal dependencies.
"""

import argparse
import datetime
import html
from html.parser import HTMLParser
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import signal
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request


ROOT = Path(__file__).resolve().parents[1]


def render_evidence(evidence, output):
    """Render captured records for report figures; these are not application pages."""
    def page(title, body):
        return '''<!doctype html><html lang="en"><meta charset="utf-8">
<title>SkillBridge verification evidence</title><style>
*{box-sizing:border-box}body{margin:0;padding:32px;font:16px/1.45 Arial,sans-serif;color:#172033;background:#fff}
h1{font-size:28px;margin:0 0 8px}h2{font-size:19px;margin:20px 0 8px}p{margin:8px 0;color:#43536b}
table{border-collapse:collapse;width:100%;margin:18px 0;font-size:14px}th,td{text-align:left;padding:12px;border:1px solid #dce2eb}
th{background:#eef1f8}code,pre{font:13px/1.5 monospace}pre{background:#f3f5f9;padding:18px;white-space:pre-wrap}
.pass{color:#147047;font-weight:bold}.note{font-size:13px}a{color:#423ac6}
</style><h1>''' + html.escape(title) + '</h1><p>Captured local HTTP verification · ' + html.escape(evidence['verified_at']) + '</p>' + body + '<p class="note">Verification record rendered from <a href="verified-exchanges.json">verified-exchanges.json</a>. Disposable test data; no HTTP mocks. This page is report evidence, not a SkillBridge application screen.</p></html>'

    names = {
        'api.enrollments.user.stats': ('User Management → Enrollment Management', 'GET /api/enrollments/user/3/stats'),
        'api.users.profile': ('Notification Management → User Management', 'GET /api/users/3/profile'),
        'api.notifications.store': ('Enrollment Management → Notification Management', 'POST /api/notifications'),
    }
    rows = []
    for exchange in evidence['exchanges']:
        relationship, route = names[exchange['endpoint']]
        rows.append('<tr><td>' + html.escape(relationship) + '<br><code>' + route + '</code></td><td><code>' +
                    html.escape(exchange['requestId']) + '</code><br>Response ID: ' +
                    ('matches request' if exchange['requestId'] == exchange['response_requestId'] else 'MISMATCH') +
                    '</td><td>' + str(exchange['http_status']) + ' / ' + html.escape(exchange['response_status']) +
                    '</td><td>' + html.escape(exchange['data_source']) + '</td></tr>')
    body = '<p class="pass">All three recorded module exchanges succeeded through HTTP.</p><table><tr><th>Consumer → Provider / Request</th><th>Request and response correlation</th><th>HTTP / IFA</th><th>Data source</th></tr>' + ''.join(rows) + '</table>'
    body += '<h2>Observed results</h2><p>The Edit User page first displayed Total 3, Active 1, Completed 1 and Dropped 1.</p><p>Completing the active enrollment triggered notification creation and a recipient-profile lookup. The student inbox contained “Enrollment completed”.</p><p>The statistics API then returned Total 3, Active 0, Completed 2 and Dropped 1.</p>'
    (output / 'http-exchanges.html').write_text(page('Real module-to-module HTTP exchanges', body))

    rows = []
    for case in evidence['error_checks']:
        response = case['response']
        rows.append('<tr><td>' + html.escape(case['case']) + '</td><td>' + str(case['http_status']) +
                    '</td><td>' + html.escape(response['status']) + '</td><td>' +
                    ('Present' if all(key in response for key in ['requestId', 'timestamp', 'message']) else 'MISSING') + '</td></tr>')
    body = '<table><tr><th>Actual HTTP request case</th><th>HTTP status</th><th>IFA status</th><th>requestId, timestamp, message</th></tr>' + ''.join(rows) + '</table>'
    missing = next(case['response'] for case in evidence['error_checks'] if case['http_status'] == 404)
    body += '<h2>Captured missing-user response</h2><pre>' + html.escape(json.dumps(missing, indent=2)) + '</pre>'
    (output / 'api-errors.html').write_text(page('Verified API error responses', body))


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--php', default='php')
    parser.add_argument('--output', type=Path, default=ROOT / 'output/report/section-6')
    parser.add_argument('--keep-server', action='store_true', help='Keep disposable server available for screenshots.')
    args = parser.parse_args()
    scratch = Path(tempfile.mkdtemp(prefix='skillbridge-http-'))
    database = scratch / 'demo.sqlite'
    database.touch()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}'
    password = secrets.token_urlsafe(24)
    import base64
    env = dict(os.environ, APP_ENV='local', APP_DEBUG='false', APP_URL=base,
               APP_KEY='base64:' + base64.b64encode(secrets.token_bytes(32)).decode(),
               DB_CONNECTION='sqlite', DB_DATABASE=str(database), DB_URL='',
               SESSION_DRIVER='database', SESSION_SECURE_COOKIE='false', SESSION_DOMAIN='null',
               SESSION_COOKIE='skillbridge_report_session', CACHE_STORE='array',
               MAIL_MAILER='array', QUEUE_CONNECTION='sync', LOG_CHANNEL='stderr',
               BCRYPT_ROUNDS='4', PHP_CLI_SERVER_WORKERS='4',
               WEB_SERVICE_LOG_PATH=str(scratch / 'exchanges.log'), REPORT_PASSWORD=password,
               REPORT_ROOT=str(ROOT), APP_MAINTENANCE_DRIVER='file')
    php = [args.php, '-n']
    server = None
    success = False
    try:
        subprocess.run(php + ['artisan', 'migrate', '--force', '--no-interaction'], cwd=ROOT,
                       env=env, check=True, capture_output=True, text=True)
        seed = r'''
require getenv('REPORT_ROOT').'/vendor/autoload.php';
$app = require getenv('REPORT_ROOT').'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$users = [];
foreach (['Admin', 'Mentor', 'Student'] as $name) {
    $role = App\Models\Role::create(['role_name' => $name]);
    $users[$name] = App\Models\User::create([
        'role_id' => $role->id, 'first_name' => 'Report', 'last_name' => $name,
        'email' => strtolower($name).'@report.example', 'password' => getenv('REPORT_PASSWORD'),
        'status' => 'Active',
    ]);
}
$category = App\Models\Category::create(['category_name' => 'Report Demonstration']);
foreach (['Active', 'Completed', 'Dropped'] as $status) {
    $course = App\Models\Course::create([
        'mentor_id' => $users['Mentor']->id, 'category_id' => $category->id,
        'title' => 'Demonstration '.$status, 'slug' => 'demonstration-'.strtolower($status),
        'description' => 'Disposable report verification data.', 'status' => 'Published',
    ]);
    $enrollment = App\Models\Enrollment::create([
        'student_id' => $users['Student']->id, 'course_id' => $course->id,
        'status' => $status, 'enrolled_at' => now(),
    ]);
    if ($status === 'Active') { $activeId = $enrollment->id; }
}
echo json_encode(['student_id' => $users['Student']->id, 'active_enrollment_id' => $activeId]);
'''
        fixture = json.loads(subprocess.run(php + ['-r', seed], cwd=ROOT, env=env,
                            check=True, capture_output=True, text=True).stdout)
        with (scratch / 'server.log').open('w') as log:
            server = subprocess.Popen(php + ['-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'public'),
                str(ROOT / 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
                cwd=ROOT / 'public', env=env, stdout=log, stderr=log, start_new_session=True)
        for attempt in range(100):
            try:
                urllib.request.urlopen(base + '/up', timeout=1).close()
                break
            except (OSError, urllib.error.URLError):
                if server.poll() is not None:
                    raise RuntimeError((scratch / 'server.log').read_text())
                time.sleep(0.1)
        else:
            raise RuntimeError('Disposable server did not become ready.')

        def client():
            return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

        def request(opener, path, fields=None, accept=None):
            data = urllib.parse.urlencode(fields).encode() if fields is not None else None
            headers = {'Accept': accept} if accept else {}
            req = urllib.request.Request(base + path, data=data, headers=headers)
            try:
                response = opener.open(req, timeout=20)
            except urllib.error.HTTPError as error:
                response = error
            return response.status, response.read().decode()

        def csrf(page):
            class TokenParser(HTMLParser):
                token = None

                def handle_starttag(self, tag, attrs):
                    attrs = dict(attrs)
                    if tag == 'input' and attrs.get('name') == '_token':
                        self.token = attrs.get('value')

            parser = TokenParser()
            parser.feed(page)
            if not parser.token:
                raise RuntimeError('The real form did not contain a CSRF token.')
            return parser.token

        def login(opener, email):
            status, page = request(opener, '/login')
            assert status == 200, page
            status, page = request(opener, '/login', {'_token': csrf(page), 'email': email, 'password': password})
            assert status == 200 and 'Logout' in page, 'Real login failed'

        student_id = fixture['student_id']
        admin = client()
        login(admin, 'admin@report.example')
        status, edit_page = request(admin, f'/admin/users/{student_id}/edit')
        assert status == 200 and 'Enrollment Statistics' in edit_page
        counts = {}
        for label in ['Total', 'Active', 'Completed', 'Dropped']:
            match = re.search(r'>' + label + r'</div>\s*<div[^>]*>(\d+)</div>', edit_page)
            assert match, f'Missing {label} statistic'
            counts[label.lower()] = int(match.group(1))
        assert counts == {'total': 3, 'active': 1, 'completed': 1, 'dropped': 1}, counts

        status, page = request(admin, '/enrollments')
        assert status == 200
        status, _ = request(admin, f"/enrollments/{fixture['active_enrollment_id']}/complete", {'_token': csrf(page)})
        assert status == 200, 'Enrollment completion failed'

        status, raw_stats = request(admin, f'/api/enrollments/user/{student_id}/stats?requestId=report-stats-after', accept='application/json')
        stats = json.loads(raw_stats)
        assert status == 200 and stats['data']['completed'] == 2 and stats['data']['active'] == 0, stats
        status, raw_profile = request(admin, f'/api/users/{student_id}/profile?requestId=report-profile', accept='application/json')
        profile = json.loads(raw_profile)
        assert status == 200 and profile['data']['first_name'] == 'Report', profile

        student = client()
        login(student, 'student@report.example')
        status, notification_page = request(student, '/notifications')
        assert status == 200 and 'Enrollment completed' in notification_page

        failures = []
        for title, opener, path, fields, expected in [
            ('Unauthenticated', client(), f'/api/users/{student_id}/profile?requestId=report-401', None, 401),
            ('Missing user', admin, '/api/users/999999/profile?requestId=report-404', None, 404),
            ('Forbidden statistics', student, '/api/enrollments/user/1/stats?requestId=report-403', None, 403),
            ('Missing trace', admin, f'/api/users/{student_id}/profile', None, 422),
            ('Invalid notification', admin, '/api/notifications', {'_token': csrf(page), 'requestId': 'report-422'}, 422),
        ]:
            status, raw = request(opener, path, fields, 'application/json')
            payload = json.loads(raw)
            assert status == expected and payload['status'] == 'F', (title, status, payload)
            assert all(key in payload for key in ['requestId', 'timestamp', 'message'])
            failures.append({'case': title, 'http_status': status, 'response': payload})

        exchanges = []
        for path in sorted(scratch.glob('exchanges-*.log')):
            for line in path.read_text().splitlines():
                exchanges.append(json.loads(line)['context'])
        for endpoint in ['api.enrollments.user.stats', 'api.users.profile', 'api.notifications.store']:
            matches = [x for x in exchanges if x['endpoint'] == endpoint]
            assert matches and all(x['data_source'] == 'http' and x['http_status'] == 200 and x['response_status'] == 'S' for x in matches), (endpoint, matches)
            assert all(x['requestId'] == x['response_requestId'] and x['response_timestamp'] for x in matches), matches

        evidence = {
            'verified_at': datetime.datetime.now(datetime.timezone.utc).isoformat(),
            'environment': 'Disposable local PHP server with four workers and isolated SQLite database',
            'http_fakes_used': False, 'base_url': base,
            'edit_page_counts_before_completion': counts,
            'statistics_after_completion': stats, 'user_profile': profile,
            'student_notification_visible': True, 'exchanges': exchanges, 'error_checks': failures,
        }
        args.output.mkdir(parents=True, exist_ok=True)
        (args.output / 'verified-exchanges.json').write_text(json.dumps(evidence, indent=2) + '\n')
        render_evidence(evidence, args.output)
        state = {'url': base, 'pid': server.pid, 'scratch': str(scratch),
                 'admin_email': 'admin@report.example', 'password': password, **fixture}
        (scratch / 'browser-state.json').write_text(json.dumps(state))
        print(json.dumps({'result': 'PASS', 'real_http_exchanges': len(exchanges),
                          'error_checks': len(failures), 'evidence': str(args.output / 'verified-exchanges.json'),
                          'browser_state_file': str(scratch / 'browser-state.json') if args.keep_server else None}))
        success = True
    finally:
        if not (success and args.keep_server):
            if server is not None:
                try:
                    os.killpg(server.pid, signal.SIGTERM)
                except ProcessLookupError:
                    pass
            if success:
                shutil.rmtree(scratch)
            else:
                print('Verification diagnostics retained at:', scratch)


if __name__ == '__main__':
    main()
