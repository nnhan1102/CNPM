// Cart functionality
class CartManager {
  constructor() {
    this.cart = JSON.parse(localStorage.getItem("cart")) || [];
    this.init();
  }

  init() {
    this.renderCart();
    this.attachEventListeners();
    this.updateCartSummary();
  }

  renderCart() {
    const cartItemsContainer = document.getElementById("cart-items");
    const emptyCart = document.getElementById("empty-cart");
    const cartItemCount = document.getElementById("cart-item-count");

    if (this.cart.length === 0) {
      cartItemsContainer.innerHTML = "";
      cartItemsContainer.appendChild(emptyCart);
      emptyCart.style.display = "block";
      cartItemCount.textContent = "0 sản phẩm";
      return;
    }

    emptyCart.style.display = "none";
    cartItemCount.textContent = `${this.cart.length} sản phẩm`;

    cartItemsContainer.innerHTML = this.cart
      .map(
        (item) => `
            <div class="cart-item" data-id="${item.id}">
                <img src="${item.image}" alt="${
          item.name
        }" class="cart-item-image">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">${this.formatPrice(
                      item.price
                    )} VND</div>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="quantity-btn decrease" data-id="${
                          item.id
                        }">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="quantity">${item.quantity}</span>
                        <button class="quantity-btn increase" data-id="${
                          item.id
                        }">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <button class="remove-btn" data-id="${item.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `
      )
      .join("");
  }

  updateCartSummary() {
    const subtotal = this.cart.reduce(
      (sum, item) => sum + item.price * item.quantity,
      0
    );
    const shipping = subtotal > 0 ? 15000 : 0;
    const discount = 0; // You can implement promo code logic here
    const total = subtotal + shipping - discount;

    document.getElementById("subtotal").textContent =
      this.formatPrice(subtotal) + " VND";
    document.getElementById("shipping").textContent =
      this.formatPrice(shipping) + " VND";
    document.getElementById("discount").textContent =
      "-" + this.formatPrice(discount) + " VND";
    document.getElementById("total").textContent =
      this.formatPrice(total) + " VND";

    // Enable/disable checkout button
    const checkoutBtn = document.getElementById("checkout-btn");
    checkoutBtn.disabled = this.cart.length === 0;
  }

  updateQuantity(productId, change) {
    const item = this.cart.find((item) => item.id === productId);
    if (item) {
      item.quantity += change;

      if (item.quantity <= 0) {
        this.removeFromCart(productId);
      } else {
        this.saveCart();
        this.renderCart();
        this.updateCartSummary();
        this.updateHeaderCartCount();
      }
    }
  }

  removeFromCart(productId) {
    this.cart = this.cart.filter((item) => item.id !== productId);
    this.saveCart();
    this.renderCart();
    this.updateCartSummary();
    this.updateHeaderCartCount();
  }

  saveCart() {
    localStorage.setItem("cart", JSON.stringify(this.cart));
  }

  updateHeaderCartCount() {
    const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
    document.querySelector(".cart-count").textContent = totalItems;
  }

  formatPrice(price) {
    return new Intl.NumberFormat("vi-VN").format(price);
  }

  attachEventListeners() {
    // Quantity controls
    document.addEventListener("click", (e) => {
      if (e.target.closest(".increase")) {
        const productId = e.target.closest(".increase").dataset.id;
        this.updateQuantity(productId, 1);
      }

      if (e.target.closest(".decrease")) {
        const productId = e.target.closest(".decrease").dataset.id;
        this.updateQuantity(productId, -1);
      }

      if (e.target.closest(".remove-btn")) {
        const productId = e.target.closest(".remove-btn").dataset.id;
        this.removeFromCart(productId);
      }
    });

    // Checkout button
    document.getElementById("checkout-btn").addEventListener("click", () => {
      this.proceedToCheckout();
    });

    // Promo code
    document.getElementById("apply-promo").addEventListener("click", () => {
      this.applyPromoCode();
    });
  }

  proceedToCheckout() {
    if (this.cart.length === 0) return;

    // Prepare order data
    const orderData = {
      items: this.cart,
      subtotal: this.cart.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
      ),
      shipping: 15000,
      total:
        this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0) +
        15000,
      description: this.generateOrderDescription(),
    };

    // Redirect to checkout page with order data
    const queryString = new URLSearchParams({
      items: JSON.stringify(orderData.items),
      total: orderData.total,
      description: orderData.description,
    }).toString();

    window.location.href = `checkout.php?${queryString}`;
  }

  generateOrderDescription() {
    const items = this.cart
      .map((item) => `${item.name} x${item.quantity}`)
      .join(", ");
    return `Đơn hàng Coffee House - ${items}`;
  }

  applyPromoCode() {
    const promoInput = document.getElementById("promo-input");
    const promoCode = promoInput.value.trim();

    if (promoCode === "COFFEE10") {
      // Apply 10% discount
      alert("Áp dụng mã giảm giá 10% thành công!");
      promoInput.value = "";
    } else if (promoCode) {
      alert("Mã giảm giá không hợp lệ!");
    }
  }
}

// Initialize cart manager when page loads
document.addEventListener("DOMContentLoaded", () => {
  new CartManager();
});
