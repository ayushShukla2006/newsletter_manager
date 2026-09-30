# Inkwell - newsletter manager

Writers (admins) publish articles. Readers (users) subscribe to writers and get a feed.

## Run locally (XAMPP)
1. Copy this folder to `htdocs/newsletter_manager`.
2. Start Apache + MySQL, open phpMyAdmin, run `schema.sql`.
3. Open http://localhost/newsletter_manager/

## Deploy on Vercel
1. Push to GitHub, import the repo in Vercel.
2. Run `schema.sql` on your hosted MySQL.
3. Project Settings > Environment Variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_SECRET` (any long random string), and `DB_SSL_CA` if your host needs TLS.

## Files
- `index.html` public home | `article.html` reading page | `login.html` login + signup
- `feed.html` reader portal | `dashboard.html` + `write.html` writer portal
- `app.js` shared helpers | `style.css` all styling
- `api/` auth, articles, authors, subscriptions | `lib/` db connection + login helpers
