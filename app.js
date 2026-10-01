// app.js - small helpers every page uses.

// Talk to the PHP API. Always returns parsed JSON (even for error responses).
async function api(url, options = {}) {
    const res = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options });
    return res.json();
}
const post = (url, body) => api(url, { method: 'POST', body: JSON.stringify(body) });

// Escape user text before putting it in innerHTML (stops <script> injection).
function esc(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Article bodies are plain text; blank lines become paragraphs.
function paragraphs(text) {
    return esc(text).split(/\n+/).map(p => `<p>${p}</p>`).join('');
}

function fmtDate(d) {
    return new Date(d.replace(' ', 'T')).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

// Draws the top bar and returns the logged-in user (or null).
async function loadNav() {
    const { user } = await api('api/auth.php?action=me');
    let links = '<a href="index.html">Home</a>';
    if (user && user.role === 'admin') links += '<a href="dashboard.html">Dashboard</a>';
    if (user && user.role === 'user')  links += '<a href="feed.html">My feed</a>';
    if (user) links += '<a href="profile.html">Profile</a>';
    links += user
        ? `<button id="logoutBtn">Log out (${esc(user.name)})</button>`
        : '<a href="login.html">Log in</a>';
    document.getElementById('nav').innerHTML = `<a class="logo" href="index.html">Inkwell</a><nav>${links}</nav>`;
    if (user) {
        document.getElementById('logoutBtn').onclick = async () => {
            await api('api/auth.php?action=logout');
            location.href = 'index.html';
        };
    }
    return user;
}

// One article in a list.
function articleCard(a) {
    const preview = a.body.length > 180 ? a.body.slice(0, 180) + '...' : a.body;
    return `<div class="card">
        <div class="by">${esc(a.author)} <span class="muted">on ${fmtDate(a.created_at)}</span></div>
        <a href="article.html?id=${a.id}"><h3>${esc(a.title)}</h3><p>${esc(preview)}</p></a>
    </div>`;
}

// The "Writers" sidebar with Subscribe / Subscribed buttons.
async function loadWriters(box, user) {
    const { authors } = await api('api/authors.php');
    box.innerHTML = '<h2>Writers</h2>' + (authors.length ? authors.map(a => `
        <div class="writer">
            <strong>${esc(a.name)}</strong>
            <span class="muted">${esc(a.bio || '')}</span>
            <span class="muted">${a.subscribers} subscriber(s)</span>
            ${user && user.id == a.id ? '' :
              `<button class="btn small ${a.subscribed ? 'outline' : ''}" data-id="${a.id}">${a.subscribed ? 'Subscribed' : 'Subscribe'}</button>`}
        </div>`).join('') : '<p class="muted">No writers yet.</p>');

    box.querySelectorAll('button[data-id]').forEach(btn => {
        btn.onclick = async () => {
            if (!user) { location.href = 'login.html'; return; }
            await post('api/subscriptions.php', { admin_id: btn.dataset.id });
            loadWriters(box, user); // redraw with new state
            if (window.onSubscriptionChange) window.onSubscriptionChange();
        };
    });
}
