// Slider elements
const $slides = $(".slide");
const $dots = $(".dot");
const $nextSlideBtn = $(".next-slide");
const $prevSlideBtn = $(".prev-slide");

// Navbar elements
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

// Slider state
let currentSlide = 0;
let autoSlide = null;

function showSlide(index) {
    // Show the selected slide and dot
    if (!$slides.length || !$dots.length) return;

    $slides.removeClass("active");
    $dots.removeClass("active");

    $slides.eq(index).addClass("active");
    $dots.eq(index).addClass("active");
}

function nextSlide() {
    // Move to the next slide
    if (!$slides.length) return;
    currentSlide = (currentSlide + 1) % $slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    // Move to the previous slide
    if (!$slides.length) return;
    currentSlide = (currentSlide - 1 + $slides.length) % $slides.length;
    showSlide(currentSlide);
}

function startAutoSlide() {
    // Start automatic slide changing
    if (!$slides.length) return;
    stopAutoSlide();
    autoSlide = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    // Stop automatic slide changing
    if (autoSlide) {
        clearInterval(autoSlide);
    }
}

// Toggle mobile navigation menu
if ($menuToggle.length && $navLinks.length) {
    $menuToggle.on("click", function () {
        $navLinks.toggleClass("open");
    });

    // Close mobile menu when a nav link is clicked
    $(".nav-links a").each(function () {
        $(this).on("click", function () {
            $navLinks.removeClass("open");
        });
    });
}

// Next slide button
if ($nextSlideBtn.length) {
    $nextSlideBtn.on("click", function () {
        nextSlide();
        startAutoSlide();
    });
}

// Previous slide button
if ($prevSlideBtn.length) {
    $prevSlideBtn.on("click", function () {
        prevSlide();
        startAutoSlide();
    });
}

// Dot navigation buttons
$dots.each(function (index) {
    $(this).on("click", function () {
        currentSlide = index;
        showSlide(currentSlide);
        startAutoSlide();
    });
});

// Add navbar style when scrolling
$(window).on("scroll", function () {
    if (!$navbar.length) return;

    if ($(window).scrollTop() > 60) {
        $navbar.addClass("scrolled");
    } else {
        $navbar.removeClass("scrolled");
    }
});

// Initialize slider
showSlide(currentSlide);
startAutoSlide();