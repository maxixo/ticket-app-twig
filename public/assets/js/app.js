// LocalStorage helpers
const storage = {
  get: key => JSON.parse(localStorage.getItem(key) || '[]'),
  set: (key, data) => localStorage.setItem(key, JSON.stringify(data))
};

// Initialize default data
if (!localStorage.getItem('users')) storage.set('users', [{username: 'admin', password: 'admin'}]);
if (!localStorage.getItem('tickets')) storage.set('tickets', []);

// --- Login ---
if (document.getElementById('loginForm')) {
  document.getElementById('loginForm').addEventListener('submit', e => {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const users = storage.get('users');
    const user = users.find(u => u.username === username && u.password === password);
    if (user) {
      localStorage.setItem('session', username);
      window.location = '/?page=dashboard';
    } else {
      alert('Invalid credentials');
    }
  });
}

// --- Logout ---
if (document.getElementById('logoutBtn')) {
  document.getElementById('logoutBtn').addEventListener('click', e => {
    localStorage.removeItem('session');
    window.location = '/?page=login';
  });
}

// --- Ticket Form ---
if (document.getElementById('ticketForm')) {
  document.getElementById('ticketForm').addEventListener('submit', e => {
    e.preventDefault();
    const tickets = storage.get('tickets');
    const ticket = {
      id: Date.now(),
      title: document.getElementById('title').value,
      description: document.getElementById('description').value,
      status: document.getElementById('status').value,
      created_at: new Date().toLocaleString()
    };
    tickets.push(ticket);
    storage.set('tickets', tickets);
    alert('Ticket created!');
    window.location = '/?page=dashboard';
  });
}

// --- Ticket List ---
if (document.getElementById('ticketList')) {
  const tickets = storage.get('tickets');
  const container = document.getElementById('ticketList');
  if (tickets.length === 0) {
    container.innerHTML = '<p>No tickets found.</p>';
  } else {
    container.innerHTML = tickets.map(t => `
      <div class="ticket">
        <h3>${t.title}</h3>
        <p>Status: ${t.status}</p>
        <button onclick="window.location='/?page=ticket&id=${t.id}'">View</button>
      </div>
    `).join('');
  }
}

// --- Ticket Detail ---
if (document.getElementById('ticketDetail')) {
  const id = document.getElementById('ticketDetail').dataset.ticketId;
  const tickets = storage.get('tickets');
  const t = tickets.find(x => x.id == id);
  const el = document.getElementById('ticketDetail');
  if (!t) {
    el.innerHTML = '<p>Ticket not found.</p>';
  } else {
    el.innerHTML = `
      <h3>${t.title}</h3>
      <p>${t.description}</p>
      <p>Status: ${t.status}</p>
      <p>Created: ${t.created_at}</p>
    `;
  }
}
