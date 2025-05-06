document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleSidebar) {
        toggleSidebar.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            mainContent.style.marginLeft = sidebar.classList.contains('active') ? '0' : 'var(--sidebar-width)';
        });        
    }
    
    // Responsive sidebar
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.add('active');
            mainContent.style.marginLeft = '0';
        } else {
            sidebar.classList.remove('active');
            mainContent.style.marginLeft = 'var(--sidebar-width)';
        }
    }
    
    // Check on load
    if (sidebar && mainContent) {
        checkScreenSize();
        
        // Check on resize
        window.addEventListener('resize', checkScreenSize);
    }
    
    // Dropdown menu for admin profile
    const adminProfile = document.querySelector('.admin-profile');
    const dropdown = document.querySelector('.profile-dropdown');
    
    if (adminProfile && dropdown) {
        adminProfile.addEventListener('click', function(event) {
            // Toggle dropdown menu
            dropdown.classList.toggle('show');
            event.stopPropagation();
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideProfile = adminProfile.contains(event.target);
            
            if (!isClickInsideProfile && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });
    }
    
    // Product card animations
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Notifications toggle
    const notificationIcon = document.querySelector('.notification');
    const notificationsPanel = document.querySelector('.notifications-panel');
    
    if (notificationIcon && notificationsPanel) {
        notificationIcon.addEventListener('click', function(event) {
            // Toggle notifications panel
            notificationsPanel.classList.toggle('show');
            event.stopPropagation();
        });
        
        // Close notification panel when clicking outside
        document.addEventListener('click', function(event) {
            const isClickInsideNotification = notificationIcon.contains(event.target);
            
            if (!isClickInsideNotification && notificationsPanel.classList.contains('show')) {
                notificationsPanel.classList.remove('show');
            }
        });
    }
    
    // Table row actions
    const actionButtons = document.querySelectorAll('.action-buttons .btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const action = this.classList.contains('view-btn') ? 'view' : 
                          this.classList.contains('edit-btn') ? 'edit' : 'delete';
            
            const row = this.closest('tr');
            if (!row) return;
            
            const orderIdCell = row.querySelector('td:first-child');
            if (!orderIdCell) return;
            
            const orderId = orderIdCell.textContent;
            
            if (action === 'view') {
                // View order details
                console.log('Viewing order:', orderId);
                // Redirect to view page or show modal
                window.location.href = `view-order.php?id=${orderId.replace('#', '')}`;
            } else if (action === 'edit') {
                // Edit order
                console.log('Editing order:', orderId);
                // Redirect to edit page or show modal
                window.location.href = `edit-order.php?id=${orderId.replace('#', '')}`;
            } else {
                // Delete confirmation
                if (confirm(`Apakah Anda yakin ingin menghapus pesanan ${orderId}?`)) {
                    console.log('Deleting order:', orderId);
                    // Send AJAX request to delete
                }
            }
        });
    });
    
    // Product card actions
    const productActionButtons = document.querySelectorAll('.product-actions .btn');
    productActionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const action = this.classList.contains('edit-btn') ? 'edit' : 'delete';
            
            const card = this.closest('.product-card');
            if (!card) return;
            
            const productNameEl = card.querySelector('h4');
            if (!productNameEl) return;
            
            const productName = productNameEl.textContent;
            const productId = card.dataset.productId; // Assuming you add data-product-id attribute
            
            if (action === 'edit') {
                // Edit product
                console.log('Editing product:', productName);
                // Redirect to edit page
                if (productId) {
                    window.location.href = `edit-product.php?id=${productId}`;
                }
            } else {
                // Delete confirmation
                if (confirm(`Apakah Anda yakin ingin menghapus produk "${productName}"?`)) {
                    console.log('Deleting product:', productName);
                    
                    // Send AJAX request to delete
                    // Example AJAX request (commented out)
          
                    fetch('delete-product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // After success, remove the card with animation
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.remove();
                            }, 300);
                        } else {
                            alert('Gagal menghapus produk: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus produk');
                    });
        
                    
                    // For now, just animate and remove
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                    }, 300);
                }
            }
        });
    });
    
    // Add click event for Add Product button
    const addProductBtn = document.querySelector('.add-btn');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            window.location.href = 'tambah-produk.php';
        });
    }
    
    // Implement quick stats counter animation
    const statValues = document.querySelectorAll('.stat-value');
    function animateValue(element, start, end, duration) {
        if (!element) return;
        
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const value = Math.floor(progress * (end - start) + start);
            
            // Format value if it's a currency
            if (element.textContent.includes('Rp')) {
                element.textContent = 'Rp' + value.toLocaleString('id-ID');
            } else {
                element.textContent = value;
            }
            
            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };
        window.requestAnimationFrame(step);
    }
    
    // Only set up observer if we have stat values
    if (statValues.length > 0 && 'IntersectionObserver' in window) {
        // Observe when stats are in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    let endValue;
                    
                    // Parse the end value
                    if (element.textContent.includes('Rp')) {
                        endValue = parseInt(element.textContent.replace(/[^0-9]/g, ''));
                    } else {
                        endValue = parseInt(element.textContent);
                    }
                    
                    // Make sure endValue is a valid number
                    if (!isNaN(endValue)) {
                        animateValue(element, 0, endValue, 1000);
                    }
                    observer.unobserve(element);
                }
            });
        }, { threshold: 0.5 });
        
        statValues.forEach(value => {
            observer.observe(value);
        });
    }
});

