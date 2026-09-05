# Deploying to Vercel with a hosted MySQL database

Vercel doesn't run PHP or host MySQL natively — this setup gets a real PHP
app live anyway, using a Docker container Vercel runs as a Function, plus
a free managed MySQL instance.

## 1. Create a free MySQL database on Aiven

Aiven has a genuinely free-forever MySQL tier (no card, no trial clock) —
1 CPU, 1GB RAM, 1GB disk, which is plenty for a course project.

1. Sign up at https://aiven.io and create a new **MySQL** service on the
   **Free** plan.
2. Once it's running, open the service's **Overview** tab and note:
   `Host`, `Port`, `User`, `Password`, `Database name`.
3. Download the **CA Certificate** from the same page (you'll need this —
   Aiven requires TLS, unlike your local XAMPP setup).
4. Under the **Databases** or a SQL console tab, run the contents of
   `schema.sql` from this project against the new database (skip the
   `CREATE DATABASE` line — Aiven already gives you one; just run the
   `CREATE TABLE` and `INSERT` statements against it).

## 2. Add the CA certificate to the project

```bash
mkdir certs
mv ~/Downloads/ca.pem certs/aiven-ca.pem
```

Then in `Dockerfile.vercel`, uncomment this line:
```dockerfile
COPY certs ./certs
```

## 3. Set environment variables in Vercel

In the Vercel dashboard: **Project Settings > Environment Variables**, add:

| Name | Value |
|---|---|
| `DB_HOST` | (from Aiven) |
| `DB_PORT` | (from Aiven, usually not 3306 — Aiven uses a custom port) |
| `DB_NAME` | (from Aiven) |
| `DB_USER` | (from Aiven) |
| `DB_PASSWORD` | (from Aiven) |
| `DB_SSL_CA` | `/app/certs/aiven-ca.pem` |

`db.php` already reads all of these via `getenv()` — nothing else to edit.

## 4. Deploy

```bash
npm install -g vercel     # if you don't have the CLI yet
vercel login
vercel deploy --prod
```

Vercel builds `Dockerfile.vercel` into a container image and serves it
from a Function. First deploy takes a bit longer since it's building a
full PHP runtime image; later deploys are faster.

## 5. Test it

Visit the URL Vercel prints. The subscribe form, admin table, and search
should all work exactly as they did locally — they're hitting the same
`subscribe.php` / `get_subscribers.php` / `unsubscribe.php` endpoints,
just now talking to Aiven instead of your local MySQL.

## Local development is unaffected

Running this locally on XAMPP/WAMP still works with zero changes: no env
vars are set, so `db.php` falls back to `localhost` / `root` / no
password / no TLS, exactly like before.
