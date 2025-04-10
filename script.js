document.addEventListener("DOMContentLoaded", function() {
    // Select slider elements
    const slider = document.querySelector('.slider');
    const slides = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dotsContainer = document.querySelector('.slider-dots');
    
    // Set up initial state
    let currentIndex = 0;
    let slideInterval;
    const slideWidth = 100; // 100% of container width
    const autoSlideDelay = 5000; // 5 seconds between slides
    
    // Create dots for navigation
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });
    
    const dots = document.querySelectorAll('.dot');
    
    // Function to go to a specific slide
    function goToSlide(index) {
        // Handle edge cases
        if (index < 0) index = slides.length - 1;
        if (index >= slides.length) index = 0;
        
        currentIndex = index;
        slider.style.transform = `translateX(-${slideWidth * currentIndex}%)`;
        
        // Update active dot
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === currentIndex);
        });
    }
    
    // Event listeners for buttons
    prevBtn.addEventListener('click', () => {
        resetInterval();
        goToSlide(currentIndex - 1);
    });
    
    nextBtn.addEventListener('click', () => {
        resetInterval();
        goToSlide(currentIndex + 1);
    });
    
    // Touch events for swipe functionality
    let touchStartX = 0;
    let touchEndX = 0;
    
    slider.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    slider.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        if (touchStartX - touchEndX > swipeThreshold) {
            // Swipe left, go to next slide
            resetInterval();
            goToSlide(currentIndex + 1);
        } else if (touchEndX - touchStartX > swipeThreshold) {
            // Swipe right, go to previous slide
            resetInterval();
            goToSlide(currentIndex - 1);
        }
    }
    
    // Auto slide functionality
    function startAutoSlide() {
        slideInterval = setInterval(() => {
            goToSlide(currentIndex + 1);
        }, autoSlideDelay);
    }
    
    function resetInterval() {
        clearInterval(slideInterval);
        startAutoSlide();
    }
    
    // Mouse enter/leave events to pause/resume auto slide
    const sliderContainer = document.querySelector('.slider-container');
    sliderContainer.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    sliderContainer.addEventListener('mouseleave', () => {
        startAutoSlide();
    });
    
    // Start the auto slider
    startAutoSlide();

    
    const produk = document.querySelectorAll('.produk');
    produk.forEach((produk) => {
        produk.addEventListener('click', () => {
            window.location.href = 'produk.html';
        });
    });
});
