// Giỏ hàng
let cart = JSON.parse(localStorage.getItem("cart")) || [];

// Cập nhật số lượng giỏ hàng
function updateCartCount() {
  const cartCount = document.querySelector(".cart-count");
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
  cartCount.textContent = totalItems;
}

// Thêm vào giỏ hàng
function addToCart(product) {
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
  updateCartCount();
  showToast("Đã thêm vào giỏ hàng");
}

// Hiển thị toast notification
function showToast(message) {
  const toast = document.getElementById("toast");
  const toastMessage = document.getElementById("toast-message");

  toastMessage.textContent = message;
  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

// Lọc sản phẩm theo danh mục
function filterProducts(category) {
  const menuItems = document.querySelectorAll(".menu-item");

  menuItems.forEach((item) => {
    if (category === "all" || item.dataset.category === category) {
      item.style.display = "block";
    } else {
      item.style.display = "none";
    }
  });
}

// Sự kiện khi trang load
document.addEventListener("DOMContentLoaded", function () {
  updateCartCount();

  // Sự kiện thêm vào giỏ hàng
  document.querySelectorAll(".btn-add-cart").forEach((button) => {
    button.addEventListener("click", function () {
      const product = {
        id: this.dataset.id,
        name: this.dataset.name,
        price: parseInt(this.dataset.price),
        image: this.dataset.image,
      };
      addToCart(product);
    });
  });

  // Sự kiện lọc sản phẩm
  document.querySelectorAll(".filter-btn").forEach((button) => {
    button.addEventListener("click", function () {
      // Xóa active class từ tất cả buttons
      document.querySelectorAll(".filter-btn").forEach((btn) => {
        btn.classList.remove("active");
      });

      // Thêm active class cho button được click
      this.classList.add("active");

      // Lọc sản phẩm
      const category = this.dataset.category;
      filterProducts(category);
    });
  });
});
