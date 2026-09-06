# Section 6 evidence package

- `section-6-report-update.md`: exact replacement instructions, report text and captions.
- `enrollment-stats-code.txt`: current consumer method for the Section 6.3 code example.
- `figure-6-3-enrollment-statistics.png`: actual application screenshot after enrollment completion.
- `figure-6-4-http-exchanges.png`: screenshot of captured service-exchange records.
- `figure-6-5-api-errors.png`: screenshot of actual error-check results and a captured 404 response.
- `verified-exchanges.json`: original sanitized local verification results.
- `http-exchanges.html` and `api-errors.html`: readable presentations of those results, not application pages.
- `automated-tests.txt`: output from the selected automated regression tests.

The original verification used a disposable database and accounts, not the application's normal data. Timestamps are UTC. The HTTP server used four workers so nested module calls could run concurrently. No API authentication or CSRF checks were bypassed.

## Repeat the local check

From the SkillBridge project directory on this Mac:

```sh
/Users/apple/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/bin/python3 scripts/verify_web_services.py --php /Applications/XAMPP/xamppfiles/bin/php
```

The script creates a temporary database, runs migrations, seeds disposable accounts, starts a loopback-only server, logs in normally, checks module exchanges and API failures, saves sanitized results, and stops its server. It requires permission to listen on a local port. It does not deploy the application.

The default output directory is this folder. To preserve these submitted records, use `--output /private/tmp/skillbridge-new-verification`. HTML and JSON are generated automatically; PNG screenshots must be captured again if the results change.

`--keep-server` leaves the disposable server available for screenshots and prints the location of a private temporary browser-state file. That file contains the temporary login credentials and the owned server process ID. Do not add it to the report or source control. After capturing screenshots, stop that server process group and remove its temporary directory.
