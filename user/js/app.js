// ---------- CONFIG ----------
// Replace with your PHP host (do NOT include trailing /php)
// EXAMPLE: https://smart-canteen-backend.example.com
const PHP_HOST = 'https://your-php-host.com'; // <-- REPLACE THIS
const API_BASE = PHP_HOST + '/php'; // don't change

// ---------- utilities ----------
async function fetchJSON(url, opts = {}) {
  const r = await fetch(url, opts);
  if (!r.ok) {
    const txt = await r.text();
    throw new Error(txt || r.statusText);
  }
  return r.json();
}

// ---------- Menu & Cart ----------
let cart = JSON.parse(localStorage.getItem('sc_cart') || '[]');

function saveCart() { localStorage.setItem('sc_cart', JSON.stringify(cart)); updateCartCount(); }

function updateCartCount() {
  const el = document.getElementById('cartCount');
  if (el) el.textContent = cart.reduce((s,i)=>s+i.qty,0);
}

// Render menu on index.html
async function loadMenu() {
  const menuEl = document.getElementById('menu');
  if (!menuEl) return;
  try {
    const items = await fetchJSON(API_BASE + '/user/get-menu.php');
    menuEl.innerHTML = items.map(it => `
      <article class="card" data-id="${it.id}" data-price="${it.price}">
        <img src="${PHP_HOST}/uploads/${it.image ?? 'placeholder.png'}" alt="${escapeHtml(it.name)}" class="card-img" />
        <div class="card-body">
          <div>
            <h3>${escapeHtml(it.name)}</h3>
            <p class="desc">${escapeHtml(it.description ?? '')}</p>
          </div>
          <div class="card-footer">
            <span class="price">৳${parseFloat(it.price).toFixed(2)}</span>
            <button class="btn primary addBtn" data-id="${it.id}" data-name="${escapeHtml(it.name)}" data-price="${it.price}">Add</button>
          </div>
        </div>
      </article>
    `).join('');
    document.querySelectorAll('.addBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = parseInt(btn.dataset.id);
        const name = btn.dataset.name;
        const price = parseFloat(btn.dataset.price);
        const existing = cart.find(x=>x.id===id);
        if (existing) existing.qty++;
        else cart.push({id, name, price, qty:1});
        saveCart();
        // small feedback
        btn.textContent = 'Added';
        setTimeout(()=>btn.textContent='Add', 700);
      });
    });
  } catch (err) {
    menuEl.innerHTML = '<p class="muted">Failed to load menu.</p>';
    console.error(err);
  }
}

// simple sanitize for template insertion
function escapeHtml(s='') { return String(s).replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

// Render cart (on cart.html)
function renderCartPage() {
  const wrap = document.getElementById('cartWrap');
  if (!wrap) return;
  if (cart.length === 0) {
    wrap.innerHTML = `<p>Your cart is empty. <a href="index.html">Browse menu</a></p>`;
    document.getElementById('checkoutPanel').style.display = 'none';
    return;
  }
  document.getElementById('checkoutPanel').style.display = 'block';
  wrap.innerHTML = cart.map(i => `
    <div class="cart-item" data-id="${i.id}">
      <div><strong>${escapeHtml(i.name)}</strong><div>৳${i.price.toFixed(2)} × ${i.qty}</div></div>
      <div>
        <button onclick="changeQty(${i.id}, -1)">−</button>
        <button onclick="changeQty(${i.id}, 1)">+</button>
        <button onclick="removeItem(${i.id})">Remove</button>
      </div>
    </div>
  `).join('');
  document.getElementById('grandTotal').textContent = cart.reduce((s,x)=>s + x.price*x.qty,0).toFixed(2);
}

// global helpers used in inline events
window.changeQty = function(id, delta) {
  const it = cart.find(x=>x.id===id);
  if (!it) return;
  it.qty += delta;
  if (it.qty <= 0) cart = cart.filter(x=>x.id !== id);
  saveCart();
  renderCartPage();
};
window.removeItem = function(id) {
  cart = cart.filter(x=>x.id !== id);
  saveCart();
  renderCartPage();
};

// ---------- Place Order & Tracking ----------
async function placeOrderFromCart() {
  const btn = document.getElementById('placeOrderBtn');
  const msg = document.getElementById('orderMsg');
  btn.disabled = true;
  msg.textContent = 'Placing order...';

  const student_id = document.getElementById('studentId').value.trim() || null;
  const pickup_time = document.getElementById('pickupTime').value || null; // datetime-local value

  try {
    const body = { items: cart, student_id, pickup_time };
    const res = await fetchJSON(API_BASE + '/user/place-order.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(body)
    });
    if (res.success) {
      msg.textContent = 'Order placed! ID: ' + res.order_id;
      // Save order id for tracking and notifications
      localStorage.setItem('sc_last_order_id', res.order_id);
      localStorage.removeItem('sc_cart');
      cart = [];
      updateCartCount();
      // redirect to track page
      setTimeout(()=> window.location.href = `track.html?order_id=${res.order_id}`, 800);
    } else {
      msg.textContent = 'Error: ' + (res.message || 'unknown');
    }
  } catch (err) {
    msg.textContent = 'Network or server error';
    console.error(err);
  } finally {
    btn.disabled = false;
  }
}

// Track order and show desktop notifications when status changes
async function getOrderStatus(orderId) {
  try {
    const res = await fetchJSON(API_BASE + '/user/order-status.php?order_id=' + encodeURIComponent(orderId));
    if (res.success) return res.order;
    return null;
  } catch (err) {
    console.error('Status fetch error', err);
    return null;
  }
}

function requestNotificationPermission() {
  if (!("Notification" in window)) return;
  if (Notification.permission === 'default') Notification.requestPermission();
}

function showDesktopNotification(title, body) {
  if (!("Notification" in window)) return;
  if (Notification.permission === 'granted') {
    try {
      new Notification(title, { body });
    } catch (e) { console.warn('Notification error', e); }
  }
}

// called on track.html to start polling
function startOrderPolling(orderId, onUpdate) {
  let lastStatus = null;
  async function poll() {
    const order = await getOrderStatus(orderId);
    if (!order) return;
    if (lastStatus !== order.status) {
      if (lastStatus !== null) {
        // show notification for status change
        showDesktopNotification('Order ' + order.id + ' ' + order.status.toUpperCase(), `Status changed to: ${order.status}`);
      }
      lastStatus = order.status;
      onUpdate(order);
    }
  }
  poll();
  const interval = setInterval(poll, 8000);
  return () => clearInterval(interval);
}

// ---------- Page init ----------
document.addEventListener('DOMContentLoaded', () => {
  updateCartCount();
  loadMenu();

  // Cart page actions
  if (document.getElementById('cartWrap')) {
    renderCartPage();
    document.getElementById('placeOrderBtn').addEventListener('click', placeOrderFromCart);
  }

  // Track page actions
  const urlParams = new URLSearchParams(location.search);
  const qOrder = urlParams.get('order_id') || localStorage.getItem('sc_last_order_id');
  if (document.getElementById('statusWrap')) {
    const input = document.getElementById('orderIdInput');
    const statusWrap = document.getElementById('statusWrap');
    requestNotificationPermission();
    if (qOrder) input.value = qOrder;

    async function renderOrder(order) {
      if (!order) { statusWrap.innerHTML = '<p class="muted">Order not found</p>'; return; }
      statusWrap.innerHTML = `
        <div class="card">
          <div><strong>Order #${order.id}</strong> — ${order.status.toUpperCase()}</div>
          <div>Student ID: ${escapeHtml(order.student_id ?? '')}</div>
          <div>Pickup time: ${escapeHtml(order.pickup_time ?? '—')}</div>
          <div>Total: ৳${parseFloat(order.total).toFixed(2)}</div>
          <div>Items:<pre style="white-space:pre-wrap">${escapeHtml(JSON.stringify(order.items, null, 2))}</pre></div>
          <div>Placed: ${escapeHtml(order.created_at)}</div>
        </div>
      `;
    }

    let stopPolling = null;
    document.getElementById('trackBtn').addEventListener('click', async () => {
      const id = input.value.trim();
      if (!id) return alert('Enter order id');
      if (stopPolling) stopPolling();
      const order = await getOrderStatus(id);
      await renderOrder(order);
      stopPolling = startOrderPolling(id, renderOrder);
    });

    // if query param present, auto start
    if (qOrder) {
      document.getElementById('trackBtn').click();
    }
  }

  // allow notification permission prompt on menu page too
  const canRequest = 'Notification' in window;
  if (canRequest && Notification.permission === 'default') {
    // request later when user interacts - skipped here for UX
  }
});
