// script.js
// Vanilla JS replacement for the old AngularJS app.js.
//
// HOW THIS TALKS TO PHP:
// fetch() is the native browser equivalent of Angular's $http. A GET is
// just fetch(url), a POST needs an explicit method, a Content-Type header,
// and a JSON.stringify'd body — fetch does none of that serialization for
// you automatically (Angular's $http did). On the PHP side, nothing
// changes: every endpoint still reads the raw JSON body with
// file_get_contents("php://input") and json_decode(), because PHP's
// $_POST superglobal never gets populated by a JSON request body.

const TOPICS = ['Technology', 'Sports', 'Business', 'Health', 'Entertainment'];

let subscribers = [];       // cached list from the last successful fetch
let selectedTopics = [];    // topics checked in the subscribe form

// ---- Elements ----
const topicGroup = document.getElementById('topicGroup');
const subForm = document.getElementById('subForm');
const nameInput = document.getElementById('name');
const emailInput = document.getElementById('email');
const nameError = document.getElementById('nameError');
const emailError = document.getElementById('emailError');
const subscribeBtn = document.getElementById('subscribeBtn');
const subSuccess = document.getElementById('subSuccess');
const subError = document.getElementById('subError');

const searchInput = document.getElementById('searchInput');
const resultCount = document.getElementById('resultCount');
const subscribersBody = document.getElementById('subscribersBody');

const subjectInput = document.getElementById('subject');
const bodyInput = document.getElementById('body');
const sendBtn = document.getElementById('sendBtn');
const sendCount = document.getElementById('sendCount');
const sendSuccess = document.getElementById('sendSuccess');

// ---- Build the topic checkboxes once, from the TOPICS list ----
TOPICS.forEach(topic => {
    const label = document.createElement('label');
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.value = topic;
    checkbox.addEventListener('change', () => {
        if (checkbox.checked) {
            selectedTopics.push(topic);
        } else {
            selectedTopics = selectedTopics.filter(t => t !== topic);
        }
    });
    label.appendChild(checkbox);
    label.appendChild(document.createTextNode(' ' + topic));
    topicGroup.appendChild(label);
});

// ---- Client-side validation (mirrors what AngularJS's ng-model did) ----
// This is UX only. subscribe.php re-validates everything server-side
// regardless, since this check can be skipped entirely (curl, disabled JS).
function validateForm() {
    const nameValid = nameInput.value.trim() !== '';
    // Simple RFC-5322-ish check; good enough for a classroom form.
    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value.trim());

    nameInput.classList.toggle('invalid', !nameValid && nameInput.dataset.touched);
    nameError.classList.toggle('show', !nameValid && nameInput.dataset.touched === 'true');
    emailInput.classList.toggle('invalid', !emailValid && emailInput.dataset.touched === 'true');
    emailError.classList.toggle('show', !emailValid && emailInput.dataset.touched === 'true');

    const formValid = nameValid && emailValid;
    subscribeBtn.disabled = !formValid;
    return formValid;
}

[nameInput, emailInput].forEach(input => {
    input.addEventListener('blur', () => { input.dataset.touched = 'true'; validateForm(); });
    input.addEventListener('input', validateForm);
});
validateForm(); // set initial disabled state

// ---- Load subscribers from the backend ----
function loadSubscribers() {
    fetch('get_subscribers.php')
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                subscribers = json.data;
                renderTable();
            }
        })
        .catch(err => console.error('Failed to load subscribers:', err));
}
loadSubscribers();

// ---- Render the admin table, applying the current search filter ----
function renderTable() {
    const query = searchInput.value.trim().toLowerCase();
    const filtered = subscribers.filter(s =>
        !query || s.name.toLowerCase().includes(query) || s.email.toLowerCase().includes(query)
    );

    subscribersBody.innerHTML = '';

    if (filtered.length === 0) {
        subscribersBody.innerHTML = '<tr><td colspan="5">No subscribers found.</td></tr>';
    } else {
        filtered.forEach(sub => {
            const tr = document.createElement('tr');

            const topicsHtml = sub.topics
                .map(t => `<span class="topic-tag">${escapeHtml(t)}</span>`)
                .join('');

            const joined = sub.created_at
                ? new Date(sub.created_at.replace(' ', 'T')).toLocaleDateString()
                : '';

            tr.innerHTML = `
                <td>${escapeHtml(sub.name)}</td>
                <td>${escapeHtml(sub.email)}</td>
                <td>${topicsHtml}</td>
                <td>${joined}</td>
                <td><button class="unsub-btn" data-id="${sub.id}">Remove</button></td>
            `;
            subscribersBody.appendChild(tr);
        });
    }

    resultCount.textContent = `${filtered.length} of ${subscribers.length} shown`;
    sendCount.textContent = subscribers.length;
    sendBtn.disabled = !subjectInput.value.trim() || !bodyInput.value.trim();
}

// Escapes user-supplied text before it's dropped into innerHTML, so a
// subscriber name containing "<script>" can't run in the admin dashboard.
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

searchInput.addEventListener('input', renderTable);

// Event delegation: one listener on the table body handles every
// "Remove" button, including ones added after later re-renders.
subscribersBody.addEventListener('click', e => {
    if (e.target.matches('.unsub-btn')) {
        const id = e.target.dataset.id;
        unsubscribe(id);
    }
});

// ---- Submit the subscription form ----
subForm.addEventListener('submit', e => {
    e.preventDefault();
    nameInput.dataset.touched = 'true';
    emailInput.dataset.touched = 'true';
    if (!validateForm()) return;

    subSuccess.classList.remove('show');
    subError.classList.remove('show');
    subscribeBtn.disabled = true;
    subscribeBtn.textContent = 'Subscribing...';

    fetch('subscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            name: nameInput.value.trim(),
            email: emailInput.value.trim(),
            topics: selectedTopics
        })
    })
        .then(res => res.json().then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            subscribeBtn.disabled = false;
            subscribeBtn.textContent = 'Subscribe';

            if (ok && data.success) {
                subSuccess.textContent = 'Thanks! You are subscribed.';
                subSuccess.classList.add('show');
                subForm.reset();
                selectedTopics = [];
                topicGroup.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = false);
                nameInput.dataset.touched = '';
                emailInput.dataset.touched = '';
                validateForm();
                loadSubscribers(); // refresh the admin table with the new row
            } else {
                subError.textContent = data.message || 'Something went wrong.';
                subError.classList.add('show');
            }
        })
        .catch(() => {
            subscribeBtn.disabled = false;
            subscribeBtn.textContent = 'Subscribe';
            subError.textContent = 'Server error, please try again.';
            subError.classList.add('show');
        });
});

// ---- Remove / unsubscribe a subscriber from the admin table ----
function unsubscribe(id) {
    fetch('unsubscribe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                subscribers = subscribers.filter(s => String(s.id) !== String(id));
                renderTable();
            }
        })
        .catch(err => console.error('Failed to unsubscribe:', err));
}

// ---- Simulate sending the newsletter (no real SMTP call) ----
[subjectInput, bodyInput].forEach(el => {
    el.addEventListener('input', () => {
        sendBtn.disabled = !subjectInput.value.trim() || !bodyInput.value.trim();
    });
});

sendBtn.addEventListener('click', () => {
    const subject = subjectInput.value.trim();
    const recipientCount = subscribers.length;
    sendBtn.disabled = true;

    // Intentionally fake — per the assignment spec, no backend email
    // endpoint is required. The delay just makes it feel like a real send.
    setTimeout(() => {
        sendSuccess.textContent = `Newsletter "${subject}" sent to ${recipientCount} subscriber(s)! (simulated)`;
        sendSuccess.classList.add('show');
        subjectInput.value = '';
        bodyInput.value = '';
        sendBtn.disabled = true;
    }, 600);
});
