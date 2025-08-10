async function fetchJSON(url, opts){ const r = await fetch(url, opts); return await r.json(); }

document.addEventListener('DOMContentLoaded', async () => {
  // fetch orders
  const ordersWrap = document.getElementById('ordersWrap');
  const itemsWrap = document.getElementById('itemsWrap');
  try {
    const orders = await fetchJSON('../php/admin/get-orders.php');
    if (orders.success) {
      ordersWrap.innerHTML = orders.data.map(o => `
        <div class="card">
          <div><strong>Order #${o.id}</strong> — ${o.order_time}</div>
          <div>Items: <pre style="white-space:pre-wrap">${o.items}</pre></div>
          <div>Total: ৳${parseFloat(o.total).toFixed(2)}</div>
        </div>
      `).join('') || '<p>No orders yet</p>';
    } else ordersWrap.textContent = 'Failed to load orders';

    const items = await fetchJSON('../php/admin/get-menu-admin.php');
    if (items.success) {
      itemsWrap.innerHTML = items.data.map(i => `
        <div class="card" style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <strong>${i.name}</strong><div class="muted">৳${i.price}</div>
          </div>
          <div>
            <button onclick="deleteItem(${i.id})">Delete</button>
          </div>
        </div>
      `).join('') || '<p>No menu items</p>';
    } else itemsWrap.textContent = 'Failed to load menu';
  } catch (err) {
    ordersWrap.textContent = 'Network error';
    itemsWrap.textContent = 'Network error';
  }

  document.getElementById('logoutBtn').addEventListener('click', async (e) => {
    e.preventDefault();
    await fetch('../php/admin/login.php?logout=1');
    window.location.href = 'login.html';
  });
});

async function deleteItem(id){
  if (!confirm('Delete item?')) return;
  const res = await fetch('../php/admin/delete-item.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ id })
  });
  const data = await res.json();
  if (data.success) window.location.reload();
  else alert('Delete failed: ' + (data.message || 'unknown'));
}
