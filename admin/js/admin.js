async function fetchJSON(url, opts){ const r = await fetch(url, opts); return await r.json(); }

document.addEventListener('DOMContentLoaded', async ()=>{
  const ordersWrap = document.getElementById('ordersWrap');
  const itemsWrap = document.getElementById('itemsWrap');

  try {
    const ordersRes = await fetchJSON('/php/admin/get-orders.php');
    if (ordersRes.success) {
      ordersWrap.innerHTML = ordersRes.data.map(o => `
        <div class="card">
          <div><strong>Order #${o.id}</strong> — ${o.created_at} — <em>${o.status}</em></div>
          <div>Student: ${o.student_id || '—'} | Pickup: ${o.pickup_time || '—'}</div>
          <div>Items: <pre style="white-space:pre-wrap">${JSON.stringify(o.items, null, 2)}</pre></div>
          <div>Total: ৳${parseFloat(o.total).toFixed(2)}</div>
          <div style="margin-top:.6rem">
            <button onclick="changeStatus(${o.id}, 'accepted')">Accept</button>
            <button onclick="changeStatus(${o.id}, 'processing')">Processing</button>
            <button onclick="changeStatus(${o.id}, 'ready')">Ready</button>
            <button onclick="changeStatus(${o.id}, 'picked_up')">Picked up</button>
            <button onclick="changeStatus(${o.id}, 'cancelled')">Cancel</button>
          </div>
        </div>
      `).join('') || '<p>No orders</p>';
    } else ordersWrap.textContent = 'Failed to load orders';

    const itemsRes = await fetchJSON('/php/admin/get-menu-admin.php');
    if (itemsRes.success) {
      itemsWrap.innerHTML = itemsRes.data.map(i => `
        <div class="card" style="display:flex;justify-content:space-between;align-items:center">
          <div><strong>${i.name}</strong><div class="muted">৳${parseFloat(i.price).toFixed(2)}</div></div>
          <div><button onclick="deleteItem(${i.id})">Delete</button></div>
        </div>
      `).join('');
    } else itemsWrap.textContent = 'Failed to load menu';
  } catch (err) {
    ordersWrap.textContent = 'Network error';
    itemsWrap.textContent = 'Network error';
  }

  document.getElementById('logoutBtn')?.addEventListener('click', async (e)=>{
    e.preventDefault();
    await fetch('/php/admin/login.php?logout=1');
    window.location.href = '/admin/login.html';
  });
});

async function changeStatus(id, status) {
  if (!confirm('Change order #' + id + ' to ' + status + '?')) return;
  const res = await fetch('/php/admin/update-order-status.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({id, status})
  });
  const data = await res.json();
  if (data.success) location.reload();
  else alert('Failed: ' + (data.message || 'unknown'));
}

async function deleteItem(id) {
  if (!confirm('Delete item?')) return;
  const res = await fetch('/php/admin/delete-item.php', {
    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})
  });
  const data = await res.json();
  if (data.success) location.reload();
  else alert('Delete failed');
}
