"""Exercise real Laravel deployments against a disposable SQLite account layout."""
import io
import json
import os
from pathlib import Path
import shutil
import sqlite3
import subprocess
import tarfile
import tempfile

REPO = Path(__file__).resolve().parents[2]
PHP = shutil.which("php")
ENV = {key: value for key, value in os.environ.items()
       if not key.startswith(("DB_", "APP_", "MAIL_", "CACHE_", "SESSION_", "QUEUE_"))}


def run(args, cwd=None, check=True):
    return subprocess.run(list(map(str, args)), cwd=cwd, env=ENV,
                          capture_output=True, text=True, check=check)


def commit(source, message):
    run(["git", "add", "-A"], source)
    run(["git", "-c", "user.name=Deployment fixture", "-c",
         "user.email=fixture@example.invalid", "commit", "-m", message], source)


with tempfile.TemporaryDirectory(prefix="backlog-deploy-test-") as temporary:
    home = Path(temporary)
    app = home / "apps/backlog-scheduler"
    source = home / "repositories/backlog-exam-scheduler"
    web = home / "public_html"
    app.mkdir(parents=True)
    web.mkdir()
    (home / "maintenance").mkdir()
    archive = subprocess.check_output(["git", "archive", "dde8b82"], cwd=REPO)
    with tarfile.open(fileobj=io.BytesIO(archive)) as tar:
        for member in tar.getmembers():
            assert not Path(member.name).is_absolute() and ".." not in Path(member.name).parts
            assert member.isfile() or member.isdir()
        tar.extractall(app)
    shutil.copytree(REPO / "vendor", app / "vendor")
    for directory in ["bootstrap/cache", "storage/framework/cache/data",
                      "storage/framework/sessions", "storage/framework/views", "storage/logs"]:
        (app / directory).mkdir(parents=True, exist_ok=True)
    db = home / "maintenance/testing.sqlite"
    db.touch()
    environment = ("APP_NAME=Backlog\nAPP_ENV=testing\nAPP_DEBUG=false\n"
                   "APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=\n"
                   "APP_URL=https://services.cse.ruet.ac.bd\nDB_CONNECTION=sqlite\n"
                   f"DB_DATABASE={db}\nCACHE_DRIVER=file\nSESSION_DRIVER=file\n"
                   "QUEUE_CONNECTION=sync\nMAIL_MAILER=log\n")
    (app / ".env").write_text(environment)
    (web / "index.php").write_text((app / "public/index.php").read_text().replace(
        "__DIR__.'/../", "__DIR__.'/../apps/backlog-scheduler/"))
    (web / ".htaccess").write_text("# Hosting handler sentinel\n")
    for site in ["alumni", "remuneration.services.cse.ruet.ac.bd"]:
        (web / site).mkdir()
        (web / site / "preserve.txt").write_text(site)
    (home / "mail").mkdir()
    (home / "mail/preserve.txt").write_text("hosting mail")
    (app / "public/uploads/notices").mkdir(parents=True)
    (app / "public/uploads/notices/keep.txt").write_text("private notice")
    (app / "storage/framework/sessions/preserve").write_text("session bytes")
    run([PHP, "artisan", "migrate", "--force", "--no-interaction"], app)
    with sqlite3.connect(db) as connection:
        connection.execute("INSERT INTO available_exams (exam_name,department,series,deadline) VALUES ('Fixture exam','CSE','2020','2099-01-01')")
    run(["git", "clone", "--local", "--no-hardlinks", REPO, source])
    for file in ["scripts/cpanel-deploy.php", ".cpanel.yml"]:
        (source / file).parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(REPO / file, source / file)
    commit(source, "Test deployment implementation")

    def deploy():
        # Local PHP is newer than the lockfile supports. Exercise the verified
        # unchanged-lockfile path; production uses PHP 8.3 and normal Composer.
        return run([PHP, source / "scripts/cpanel-deploy.php", home, "--reuse-vendor"], check=False)

    def preserved():
        assert (app / ".env").read_text() == environment
        assert (app / "public/uploads/notices/keep.txt").read_text() == "private notice"
        assert (app / "storage/framework/sessions/preserve").read_text() == "session bytes"
        assert not (web / "uploads").exists()
        assert (web / ".htaccess").read_text() == "# Hosting handler sentinel\n"
        assert "../apps/backlog-scheduler/vendor/autoload.php" in (web / "index.php").read_text()
        for site in ["alumni", "remuneration.services.cse.ruet.ac.bd"]:
            assert (web / site / "preserve.txt").read_text() == site
        assert (home / "mail/preserve.txt").read_text() == "hosting mail"
        assert not (app / ".git").exists()
        assert not (app / "tests").exists()

    result = deploy()
    if result.returncode != 0:
        state = json.loads((home / "maintenance/deployment.json").read_text())
        raise AssertionError(result.stdout + result.stderr + Path(state["log"]).read_text()[-7000:])
    state = json.loads((home / "maintenance/deployment.json").read_text())
    assert state["status"] == "complete"
    assert state["pending_migrations"] == 1
    assert (web / "css/app.css").read_bytes() == (source / "public/css/app.css").read_bytes()
    assert (web / "js/app.js").read_bytes() == (source / "public/js/app.js").read_bytes()
    assert (web / "css").stat().st_mode & 0o777 == 0o755
    assert (web / "css/app.css").stat().st_mode & 0o777 == 0o644
    assert not (app / "storage/framework/down").exists()
    with sqlite3.connect(db) as connection:
        assert connection.execute("SELECT count(*) FROM mail_logs").fetchone()[0] == 0
        assert connection.execute("SELECT count(*) FROM available_exams").fetchone()[0] == 1
    with sqlite3.connect(Path(state["backup"]) / "database.sqlite") as backup:
        assert backup.execute("SELECT count(*) FROM available_exams").fetchone()[0] == 1
        assert not backup.execute("SELECT name FROM sqlite_master WHERE name='mail_logs'").fetchone()
    preserved()
    print("PASS: full release, new UI assets, pending migration, DB backup, production data and other sites preserved")

    # A repeated deployment must not re-run the applied migration or lose mutable data.
    result = deploy()
    assert result.returncode == 0, result.stdout + result.stderr
    assert json.loads((home / "maintenance/deployment.json").read_text())["pending_migrations"] == 0
    preserved()
    print("PASS: repeated release keeps data and skips applied migration")

    # A failed real HTTP render after the switch must restore code and assets.
    good_view = (app / "resources/views/home.blade.php").read_bytes()
    good_css = (web / "css/app.css").read_bytes()
    (source / "resources/views/home.blade.php").write_text("@php throw new RuntimeException('Fixture rendering failure'); @endphp")
    with (source / "public/css/app.css").open("a") as css:
        css.write("\n/* failed release sentinel */\n")
    commit(source, "Introduce disposable smoke failure")
    result = deploy()
    assert result.returncode != 0
    assert json.loads((home / "maintenance/deployment.json").read_text())["status"] == "rolled_back"
    assert (app / "resources/views/home.blade.php").read_bytes() == good_view
    assert (web / "css/app.css").read_bytes() == good_css
    assert not (app / "storage/framework/down").exists()
    preserved()
    print("PASS: failed HTTP smoke check restores previous code and assets and reopens site")

    # Schema failure requires explicit recovery; never pretend a partially applied migration is safe.
    (source / "resources/views/home.blade.php").write_bytes(good_view)
    (source / "database/migrations/2099_01_01_000000_fixture_failure.php").write_text(
        "<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration {"
        " public function up() { throw new \\RuntimeException('Disposable migration failure'); }"
        " public function down() {} };\n")
    commit(source, "Introduce disposable migration failure")
    result = deploy()
    assert result.returncode != 0
    assert json.loads((home / "maintenance/deployment.json").read_text())["status"] == "needs_recovery"
    assert (app / "storage/framework/down").exists()
    assert deploy().returncode != 0
    preserved()
    print("PASS: migration failure keeps maintenance mode, retains backup and blocks overlapping recovery")
