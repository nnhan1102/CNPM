/**
 * Coffee House - Cart Manager
 * Quản lý giỏ hàng, xử lý thanh toán
 */

class CartManager {
  constructor() {
    // Lấy giỏ hàng từ localStorage hoặc tạo mới
    this.cart = JSON.parse(localStorage.getItem("cart")) || [];
    this.isProcessing = false;

    // Khởi tạo
    this.init();
  }

  /**
   * Khởi tạo giỏ hàng
   */
  init() {
    console.log("🛒 CartManager đang khởi tạo...");

    // Hiển thị giỏ hàng
    this.renderCart();

    // Cập nhật thông tin đơn hàng
    this.updateCartSummary();

    // Cập nhật số lượng trong header
    this.updateHeaderCartCount();

    // Gắn sự kiện
    this.attachEventListeners();

    // Kiểm tra trạng thái nút checkout
    this.updateCheckoutButtonState();
  }

  /**
   * Hiển thị giỏ hàng
   */
  renderCart() {
    const cartItemsContainer = document.getElementById("cart-items");
    const emptyCart = document.getElementById("empty-cart");
    const cartItemCount = document.getElementById("cart-item-count");

    // Nếu giỏ hàng trống
    if (this.cart.length === 0) {
      this.showEmptyCart(cartItemsContainer, emptyCart, cartItemCount);
      return;
    }

    // Hiển thị sản phẩm trong giỏ hàng
    this.showCartItems(cartItemsContainer, emptyCart, cartItemCount);
  }

  /**
   * Hiển thị trạng thái giỏ hàng trống
   */
  showEmptyCart(container, emptyCartElement, countElement) {
    container.innerHTML = "";
    container.appendChild(emptyCartElement);
    emptyCartElement.style.display = "block";
    countElement.textContent = "0 sản phẩm";

    // Vô hiệu hóa nút checkout
    this.disableCheckoutButton();
  }

  /**
   * Hiển thị sản phẩm trong giỏ hàng
   */
  showCartItems(container, emptyCartElement, countElement) {
    emptyCartElement.style.display = "none";

    // Tính tổng số sản phẩm
    const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
    countElement.textContent = `${totalItems} ${
      totalItems > 1 ? "sản phẩm" : "sản phẩm"
    }`;

    // Tạo HTML cho từng sản phẩm
    const cartHTML = this.cart
      .map((item, index) => this.createCartItemHTML(item, index))
      .join("");
    container.innerHTML = cartHTML;

    // Kích hoạt nút checkout
    this.enableCheckoutButton();
  }

  /**
   * Tạo HTML cho một sản phẩm trong giỏ hàng
   */
  createCartItemHTML(item, index) {
    const totalPrice = item.price * item.quantity;

    return `
      <div class="cart-item" data-id="${item.id}" data-index="${index}">
        <img src="${item.image}" alt="${item.name}" class="cart-item-image">
        <div class="cart-item-details">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-price">${this.formatPrice(totalPrice)} VND</div>
          <div class="item-unit-price text-muted small mt-1">
            ${this.formatPrice(item.price)} VND / sản phẩm
          </div>
        </div>
        <div class="cart-item-controls">
          <div class="quantity-controls">
            <button class="quantity-btn decrease" 
                    data-id="${item.id}" 
                    data-index="${index}"
                    aria-label="Giảm số lượng">
              <i class="fas fa-minus"></i>
            </button>
            <span class="quantity">${item.quantity}</span>
            <button class="quantity-btn increase" 
                    data-id="${item.id}" 
                    data-index="${index}"
                    aria-label="Tăng số lượng">
              <i class="fas fa-plus"></i>
            </button>
          </div>
          <button class="remove-btn" 
                  data-id="${item.id}" 
                  data-index="${index}"
                  aria-label="Xóa sản phẩm">
            <i class="fas fa-trash"></i> Xóa
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Cập nhật thông tin đơn hàng
   */
  updateCartSummary() {
    const subtotal = this.calculateSubtotal();
    const shipping = this.calculateShipping(subtotal);
    const total = subtotal + shipping;

    // Cập nhật DOM
    document.getElementById("subtotal").textContent = `${this.formatPrice(
      subtotal
    )} VND`;
    document.getElementById("shipping").textContent = `${this.formatPrice(
      shipping
    )} VND`;
    document.getElementById("total").textContent = `${this.formatPrice(
      total
    )} VND`;

    // Hiển thị thông báo miễn phí vận chuyển
    this.updateShippingMessage(subtotal);
  }

  /**
   * Tính tổng tiền sản phẩm
   */
  calculateSubtotal() {
    return this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  }

  /**
   * Tính phí vận chuyển
   */
  calculateShipping(subtotal) {
    // Miễn phí vận chuyển cho đơn trên 150,000 VND
    if (subtotal >= 150000 || subtotal === 0) {
      return 0;
    }
    return 15000;
  }

  /**
   * Cập nhật thông báo vận chuyển
   */
  updateShippingMessage(subtotal) {
    const shippingInfo = document.querySelector(".info-item:first-child span");
    if (shippingInfo) {
      if (subtotal >= 150000 && subtotal > 0) {
        shippingInfo.innerHTML =
          '<strong class="text-success">✓ Miễn phí vận chuyển</strong>';
      } else if (subtotal > 0) {
        const needed = 150000 - subtotal;
        shippingInfo.textContent = `Thêm ${this.formatPrice(
          needed
        )} VND để được miễn phí vận chuyển`;
      }
    }
  }

  /**
   * Cập nhật số lượng sản phẩm
   */
  updateQuantity(productId, change) {
    if (this.isProcessing) return;

    this.isProcessing = true;

    const itemIndex = this.cart.findIndex((item) => item.id === productId);

    if (itemIndex !== -1) {
      const item = this.cart[itemIndex];
      const newQuantity = item.quantity + change;

      if (newQuantity <= 0) {
        this.removeItem(productId);
      } else {
        // Giới hạn tối đa 99 sản phẩm
        if (newQuantity > 99) {
          this.showToast("Số lượng tối đa là 99 sản phẩm", "warning");
          this.isProcessing = false;
          return;
        }

        item.quantity = newQuantity;
        this.saveCart();
        this.updateCartItem(itemIndex);
        this.showQuantityUpdateToast(item.name, newQuantity);
      }
    }

    this.isProcessing = false;
  }

  /**
   * Cập nhật hiển thị của một sản phẩm
   */
  updateCartItem(index) {
    const cartItemElement = document.querySelector(
      `.cart-item[data-index="${index}"]`
    );

    if (cartItemElement) {
      const item = this.cart[index];
      const totalPrice = item.price * item.quantity;

      // Cập nhật số lượng
      const quantityElement = cartItemElement.querySelector(".quantity");
      if (quantityElement) {
        quantityElement.textContent = item.quantity;
      }

      // Cập nhật tổng giá
      const priceElement = cartItemElement.querySelector(".cart-item-price");
      if (priceElement) {
        priceElement.textContent = `${this.formatPrice(totalPrice)} VND`;
      }
    }

    // Cập nhật toàn bộ giỏ hàng
    this.updateCartSummary();
    this.updateHeaderCartCount();
    this.updateCheckoutButtonState();
  }

  /**
   * Xóa sản phẩm khỏi giỏ hàng
   */
  removeItem(productId) {
    const item = this.cart.find((item) => item.id === productId);

    if (item && confirm(`Bạn có chắc muốn xóa "${item.name}" khỏi giỏ hàng?`)) {
      const itemIndex = this.cart.findIndex((item) => item.id === productId);
      this.cart.splice(itemIndex, 1);
      this.saveCart();
      this.renderCart();
      this.updateCartSummary();
      this.updateHeaderCartCount();
      this.updateCheckoutButtonState();
      this.showToast(`Đã xóa "${item.name}" khỏi giỏ hàng`, "success");
    }
  }

  /**
   * Xóa toàn bộ giỏ hàng
   */
  clearCart() {
    if (
      this.cart.length > 0 &&
      confirm("Bạn có chắc muốn xóa toàn bộ giỏ hàng?")
    ) {
      this.cart = [];
      this.saveCart();
      this.renderCart();
      this.updateCartSummary();
      this.updateHeaderCartCount();
      this.updateCheckoutButtonState();
      this.showToast("Đã xóa toàn bộ giỏ hàng", "success");
    }
  }

  /**
   * Cập nhật số lượng trong header
   */
  updateHeaderCartCount() {
    const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountElement = document.querySelector(".cart-count");

    if (cartCountElement) {
      cartCountElement.textContent = totalItems;

      // Thêm animation khi thay đổi
      if (totalItems > 0) {
        cartCountElement.style.transform = "scale(1.2)";
        setTimeout(() => {
          cartCountElement.style.transform = "scale(1)";
        }, 300);
      }
    }
  }

  /**
   * Kích hoạt nút checkout
   */
  enableCheckoutButton() {
    const checkoutBtn = document.getElementById("checkout-btn");
    if (checkoutBtn) {
      checkoutBtn.disabled = false;
      checkoutBtn.style.opacity = "1";
      checkoutBtn.style.cursor = "pointer";
    }
  }

  /**
   * Vô hiệu hóa nút checkout
   */
  disableCheckoutButton() {
    const checkoutBtn = document.getElementById("checkout-btn");
    if (checkoutBtn) {
      checkoutBtn.disabled = true;
      checkoutBtn.style.opacity = "0.7";
      checkoutBtn.style.cursor = "not-allowed";
    }
  }

  /**
   * Cập nhật trạng thái nút checkout
   */
  updateCheckoutButtonState() {
    if (this.cart.length === 0) {
      this.disableCheckoutButton();
    } else {
      this.enableCheckoutButton();
    }
  }

  /**
   * Lưu giỏ hàng vào localStorage
   */
  saveCart() {
    try {
      localStorage.setItem("cart", JSON.stringify(this.cart));
      console.log("💾 Giỏ hàng đã được lưu:", this.cart);
    } catch (error) {
      console.error("❌ Lỗi khi lưu giỏ hàng:", error);
      this.showToast("Lỗi khi lưu giỏ hàng", "error");
    }
  }

  /**
   * Định dạng giá tiền
   */
  formatPrice(price) {
    return new Intl.NumberFormat("vi-VN").format(price);
  }

  /**
   * Hiển thị thông báo
   */
  showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    const toastMessage = document.getElementById("toast-message");
    const toastIcon = toast.querySelector("i");

    if (!toast || !toastMessage) return;

    // Đặt nội dung
    toastMessage.textContent = message;

    // Đặt màu sắc theo loại thông báo
    switch (type) {
      case "success":
        toast.style.borderLeftColor = "#28a745";
        toastIcon.className = "fas fa-check-circle";
        toastIcon.style.color = "#28a745";
        break;
      case "warning":
        toast.style.borderLeftColor = "#ffc107";
        toastIcon.className = "fas fa-exclamation-triangle";
        toastIcon.style.color = "#ffc107";
        break;
      case "error":
        toast.style.borderLeftColor = "#dc3545";
        toastIcon.className = "fas fa-times-circle";
        toastIcon.style.color = "#dc3545";
        break;
      default:
        toast.style.borderLeftColor = "#28a745";
        toastIcon.className = "fas fa-check-circle";
        toastIcon.style.color = "#28a745";
    }

    // Hiển thị toast
    toast.classList.add("show");

    // Ẩn toast sau 3 giây
    setTimeout(() => {
      toast.classList.remove("show");
    }, 3000);
  }

  /**
   * Hiển thị thông báo cập nhật số lượng
   */
  showQuantityUpdateToast(productName, quantity) {
    this.showToast(`${productName}: ${quantity} sản phẩm`, "success");
  }

  /**
   * Tiến hành thanh toán
   */
  proceedToCheckout() {
    if (this.cart.length === 0) {
      this.showToast(
        "Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi thanh toán.",
        "warning"
      );
      return;
    }

    if (this.isProcessing) return;

    this.isProcessing = true;

    // Hiển thị loading state
    const checkoutBtn = document.getElementById("checkout-btn");
    const originalText = checkoutBtn.innerHTML;
    checkoutBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    checkoutBtn.disabled = true;

    // Chuẩn bị dữ liệu đơn hàng
    const orderData = {
      items: this.cart,
      subtotal: this.calculateSubtotal(),
      shipping: this.calculateShipping(this.calculateSubtotal()),
      total:
        this.calculateSubtotal() +
        this.calculateShipping(this.calculateSubtotal()),
      description: this.generateOrderDescription(),
      timestamp: new Date().toISOString(),
      orderId: `ORDER_${Date.now()}`,
    };

    console.log("📦 Dữ liệu đơn hàng:", orderData);

    // Tạo URL query string
    const queryString = new URLSearchParams({
      items: JSON.stringify(orderData.items),
      total: orderData.total,
      description: encodeURIComponent(orderData.description),
      orderId: orderData.orderId,
    }).toString();

    // Chuyển hướng sau 1 giây để người dùng thấy loading
    setTimeout(() => {
      console.log(`🔗 Chuyển hướng đến: checkout.php?${queryString}`);
      window.location.href = `checkout.php?${queryString}`;
    }, 1000);
  }

  /**
   * Tạo mô tả đơn hàng
   */
  generateOrderDescription() {
    const itemsDescription = this.cart
      .map((item) => `${item.name} (x${item.quantity})`)
      .join(", ");

    return `Đơn hàng Coffee House - ${itemsDescription}`;
  }

  /**
   * Gắn sự kiện
   */
  attachEventListeners() {
    // Sự kiện click trên document (delegation)
    document.addEventListener("click", (e) => {
      // Tăng số lượng
      if (e.target.closest(".increase")) {
        const button = e.target.closest(".increase");
        const productId = button.dataset.id;
        this.updateQuantity(productId, 1);
      }

      // Giảm số lượng
      if (e.target.closest(".decrease")) {
        const button = e.target.closest(".decrease");
        const productId = button.dataset.id;
        this.updateQuantity(productId, -1);
      }

      // Xóa sản phẩm
      if (e.target.closest(".remove-btn")) {
        const button = e.target.closest(".remove-btn");
        const productId = button.dataset.id;
        this.removeItem(productId);
      }
    });

    // Sự kiện cho nút checkout
    const checkoutBtn = document.getElementById("checkout-btn");
    if (checkoutBtn) {
      checkoutBtn.addEventListener("click", (e) => {
        e.preventDefault();
        this.proceedToCheckout();
      });
    }

    // Sự kiện input số lượng (nếu có)
    const quantityInputs = document.querySelectorAll(".quantity-input");
    quantityInputs.forEach((input) => {
      input.addEventListener("change", (e) => {
        const productId = e.target.dataset.id;
        const newQuantity = parseInt(e.target.value) || 1;

        if (newQuantity < 1 || newQuantity > 99) {
          this.showToast("Số lượng phải từ 1 đến 99", "warning");
          e.target.value = 1;
          return;
        }

        const item = this.cart.find((item) => item.id === productId);
        if (item) {
          item.quantity = newQuantity;
          this.saveCart();
          this.updateCartItem(this.cart.findIndex((i) => i.id === productId));
        }
      });
    });

    // Sự kiện keydown cho quantity controls
    document.addEventListener("keydown", (e) => {
      if (e.key === "+" || e.key === "=") {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.classList.contains("quantity")) {
          const productId = focusedElement.closest(".cart-item").dataset.id;
          this.updateQuantity(productId, 1);
          e.preventDefault();
        }
      } else if (e.key === "-" || e.key === "_") {
        const focusedElement = document.activeElement;
        if (focusedElement && focusedElement.classList.contains("quantity")) {
          const productId = focusedElement.closest(".cart-item").dataset.id;
          this.updateQuantity(productId, -1);
          e.preventDefault();
        }
      }
    });
  }
}

// Khởi tạo CartManager khi trang tải xong
document.addEventListener("DOMContentLoaded", () => {
  console.log("🚀 Khởi động Coffee House Cart System...");

  try {
    const cartManager = new CartManager();

    // Thêm vào window để debug (có thể xóa khi deploy production)
    window.cartManager = cartManager;

    console.log("✅ CartManager khởi tạo thành công!");
    console.log("📊 Số sản phẩm trong giỏ:", cartManager.cart.length);
  } catch (error) {
    console.error("❌ Lỗi khi khởi tạo CartManager:", error);

    // Hiển thị thông báo lỗi cho người dùng
    const errorToast = document.createElement("div");
    errorToast.className =
      "alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3";
    errorToast.style.zIndex = "9999";
    errorToast.textContent = "Lỗi khi tải giỏ hàng. Vui lòng tải lại trang.";
    document.body.appendChild(errorToast);

    setTimeout(() => {
      errorToast.remove();
    }, 5000);
  }
});

// Hàm tiện ích để thêm sản phẩm từ bên ngoài (từ trang chủ)
function addToCart(product) {
  try {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    const existingItem = cart.find((item) => item.id === product.id);

    if (existingItem) {
      existingItem.quantity += 1;
    } else {
      cart.push({
        ...product,
        quantity: 1,
      });
    }

    localStorage.setItem("cart", JSON.stringify(cart));

    // Hiển thị thông báo
    const toast = document.getElementById("toast");
    const toastMessage = document.getElementById("toast-message");

    if (toast && toastMessage) {
      toastMessage.textContent = `Đã thêm ${product.name} vào giỏ hàng`;
      toast.classList.add("show");

      setTimeout(() => {
        toast.classList.remove("show");
      }, 3000);
    }

    // Cập nhật số lượng trong header
    const cartCount = document.querySelector(".cart-count");
    if (cartCount) {
      const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
      cartCount.textContent = totalItems;
    }

    return true;
  } catch (error) {
    console.error("❌ Lỗi khi thêm vào giỏ hàng:", error);
    return false;
  }
}

// Xuất hàm để sử dụng từ các file khác
if (typeof module !== "undefined" && module.exports) {
  module.exports = { CartManager, addToCart };
}
