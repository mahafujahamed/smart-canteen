let cart = JSON.parse(localStorage.getItem('cart')) || [];

document.addEventListener('DOMContentLoaded', function() {
    loadMenu();
    updateCartCount();
});

function loadMenu() {
    fetch('../php/user/get-menu.php')
    .then(res => res.json())
    .then(data => {
        let menuDiv = document.getElementById('menu');
        menuDiv.innerHTML = '';
        data.forEach(item => {
            let card = document.createElement('div');
            card.classList.add('card');
            card.innerHTML = `
                <img src="../uploads/${item.image}" alt="${item.name}">
                <h3>${item.name}</h3>
                <p>${item.description}</p>
                <p>৳${item.price}</p>
                <button onclick="addToCart(${item.id}, '${item.name}', ${item.price})">Add to Cart</button>
            `;
            menuDiv.appendChild(card);
        });
    });
}

function addToCart(id, name, price) {
    let existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1 });
    }
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartCount();
}

function updateCartCount() {
    document.getElementById('cartCount').innerText = cart.reduce((sum, i) => sum + i.qty, 0);
}
