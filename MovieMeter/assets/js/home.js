const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const nextSlideBtn = document.querySelector(".next-slide");
const prevSlideBtn = document.querySelector(".prev-slide");
const navbar = document.querySelector(".navbar");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

let currentSlide = 0;
let autoSlide = null;

function showSlide(index) {
    if (!slides.length || !dots.length) return;

    slides.forEach((slide) => slide.classList.remove("active"));
    dots.forEach((dot) => dot.classList.remove("active"));

    slides[index].classList.add("active");
    dots[index].classList.add("active");
}

function nextSlide() {
    if (!slides.length) return;
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    if (!slides.length) return;
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(currentSlide);
}

function startAutoSlide() {
    if (!slides.length) return;
    stopAutoSlide();
    autoSlide = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    if (autoSlide) {
        clearInterval(autoSlide);
    }
}

if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
        navLinks.classList.toggle("open");
    });

    document.querySelectorAll(".nav-links a").forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("open");
        });
    });
}

if (nextSlideBtn) {
    nextSlideBtn.addEventListener("click", () => {
        nextSlide();
        startAutoSlide();
    });
}

if (prevSlideBtn) {
    prevSlideBtn.addEventListener("click", () => {
        prevSlide();
        startAutoSlide();
    });
}

dots.forEach((dot, index) => {
    dot.addEventListener("click", () => {
        currentSlide = index;
        showSlide(currentSlide);
        startAutoSlide();
    });
});

window.addEventListener("scroll", () => {
    if (!navbar) return;

    if (window.scrollY > 60) {
        navbar.classList.add("scrolled");
    } else {
        navbar.classList.remove("scrolled");
    }
});

showSlide(currentSlide);
startAutoSlide();