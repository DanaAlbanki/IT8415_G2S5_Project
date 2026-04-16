const $slides = $(".slide");
const $dots = $(".dot");
const $nextSlideBtn = $(".next-slide");
const $prevSlideBtn = $(".prev-slide");
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

let currentSlide = 0;
let autoSlide = null;

function showSlide(index) {
    if (!$slides.length || !$dots.length) return;

    $slides.removeClass("active");
    $dots.removeClass("active");

    $slides.eq(index).addClass("active");
    $dots.eq(index).addClass("active");
}

function nextSlide() {
    if (!$slides.length) return;
    currentSlide = (currentSlide + 1) % $slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    if (!$slides.length) return;
    currentSlide = (currentSlide - 1 + $slides.length) % $slides.length;
    showSlide(currentSlide);
}

function startAutoSlide() {
    if (!$slides.length) return;
    stopAutoSlide();
    autoSlide = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    if (autoSlide) {
        clearInterval(autoSlide);
    }
}

if ($menuToggle.length && $navLinks.length) {
    $menuToggle.on("click", function () {
        $navLinks.toggleClass("open");
    });

    $(".nav-links a").each(function () {
        $(this).on("click", function () {
            $navLinks.removeClass("open");
        });
    });
}

if ($nextSlideBtn.length) {
    $nextSlideBtn.on("click", function () {
        nextSlide();
        startAutoSlide();
    });
}

if ($prevSlideBtn.length) {
    $prevSlideBtn.on("click", function () {
        prevSlide();
        startAutoSlide();
    });
}

$dots.each(function (index) {
    $(this).on("click", function () {
        currentSlide = index;
        showSlide(currentSlide);
        startAutoSlide();
    });
});

$(window).on("scroll", function () {
    if (!$navbar.length) return;

    if ($(window).scrollTop() > 60) {
        $navbar.addClass("scrolled");
    } else {
        $navbar.removeClass("scrolled");
    }
});

showSlide(currentSlide);
startAutoSlide();