import { API_KEY, BASE_URL, IMG_PATH } from './api.js';

const FALLBACK_IMAGE = 'assets/images/notfound.png';

let currentPage = 1;
let totalPages = 1;

const container = document.getElementById("movies");
const pagination = document.getElementById("pagination");

const searchForm = document.getElementById("searchForm");
const searchTitle = document.getElementById("searchTitle");
const searchCreator = document.getElementById("searchCreator");
const fromDate = document.getElementById("fromDate");
const toDate = document.getElementById("toDate");
const sortBy = document.getElementById("sortBy");
const resetFiltersBtn = document.getElementById("resetFilters");

const allMoviesSection = document.getElementById("all-movies-section");

const latestTrack = document.getElementById("latestTrack");
const latestPrev = document.getElementById("latestPrev");
const latestNext = document.getElementById("latestNext");

const topRatedTrack = document.getElementById("topRatedTrack");
const topRatedPrev = document.getElementById("topRatedPrev");
const topRatedNext = document.getElementById("topRatedNext");

const resultsCount = document.getElementById("resultsCount");
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

const filters = {
    title: "",
    creator: "",
    fromDate: "",
    toDate: "",
    sortBy: "primary_release_date.desc"
};

const carousels = {
    latest: {
        track: latestTrack,
        prev: latestPrev,
        next: latestNext,
        index: 0,
        total: 0
    },
    topRated: {
        track: topRatedTrack,
        prev: topRatedPrev,
        next: topRatedNext,
        index: 0,
        total: 0
    }
};

init();

async function init() {
    attachEvents();
    await Promise.all([loadLatestMovies(), loadTopRatedMovies()]);
    await getMovies(1);
}

function attachEvents() {
    if (searchForm) {
        searchForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            filters.title = searchTitle ? searchTitle.value.trim() : "";
            filters.creator = searchCreator ? searchCreator.value.trim() : "";
            filters.fromDate = fromDate ? fromDate.value : "";
            filters.toDate = toDate ? toDate.value : "";
            filters.sortBy = sortBy ? sortBy.value : "primary_release_date.desc";

            await getMovies(1);
            scrollToAllMovies();
        });
    }

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener("click", async () => {
            if (searchForm) {
                searchForm.reset();
            }

            filters.title = "";
            filters.creator = "";
            filters.fromDate = "";
            filters.toDate = "";
            filters.sortBy = "primary_release_date.desc";

            if (sortBy) {
                sortBy.value = "primary_release_date.desc";
            }

            await getMovies(1);
            scrollToAllMovies();
        });
    }

    if (latestPrev && latestNext) {
        latestPrev.addEventListener("click", () => moveCarousel("latest", -1));
        latestNext.addEventListener("click", () => moveCarousel("latest", 1));
    }

    if (topRatedPrev && topRatedNext) {
        topRatedPrev.addEventListener("click", () => moveCarousel("topRated", -1));
        topRatedNext.addEventListener("click", () => moveCarousel("topRated", 1));
    }

    document.querySelectorAll(".hero-detail-btn").forEach(button => {
        button.addEventListener("click", async (e) => {
            e.preventDefault();

            const title = button.dataset.title;

            try {
                const params = new URLSearchParams({
                    api_key: API_KEY,
                    query: title,
                    page: 1,
                    include_adult: "false"
                });

                const url = `${BASE_URL}/search/movie?${params.toString()}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.results && data.results.length > 0) {
                    const movieId = data.results[0].id;
                    window.location.href = `movie.php?id=${movieId}`;
                } else {
                    alert("Movie not found.");
                }
            } catch (error) {
                console.error("Failed to find movie ID:", error);
                alert("Something went wrong.");
            }
        });
    });
}

async function loadLatestMovies() {
    if (!latestTrack) return;

    try {
        const today = new Date().toISOString().split("T")[0];
        const url = `${BASE_URL}/discover/movie?api_key=${API_KEY}&sort_by=primary_release_date.desc&primary_release_date.lte=${today}&include_adult=false&include_video=false&page=1`;
        const data = await fetchJSON(url);

        const releasedOnly = (data.results || [])
            .filter(movie => movie.release_date && movie.release_date <= today)
            .sort((a, b) => new Date(b.release_date || 0) - new Date(a.release_date || 0));

        renderCarousel("latest", releasedOnly.slice(0, 10));
    } catch (error) {
        if (latestTrack) {
            latestTrack.innerHTML = `<div class="empty-state">Failed to load latest movies.</div>`;
        }
    }
}

async function loadTopRatedMovies() {
    if (!topRatedTrack) return;

    try {
        const today = new Date().toISOString().split("T")[0];
        const url = `${BASE_URL}/movie/top_rated?api_key=${API_KEY}&page=1`;
        const data = await fetchJSON(url);

        const releasedOnly = (data.results || [])
            .filter(movie => movie.release_date && movie.release_date <= today);

        renderCarousel("topRated", releasedOnly.slice(0, 10));
    } catch (error) {
        if (topRatedTrack) {
            topRatedTrack.innerHTML = `<div class="empty-state">Failed to load top rated movies.</div>`;
        }
    }
}

async function getMovies(page) {
    if (!container) return;

    try {
        currentPage = page;

        const data = await fetchMovies(page);

        if (resultsCount) {
            resultsCount.innerHTML = `Found <span class="count-number">${(data.total_results || 0).toLocaleString()}</span> results`;
        }

        totalPages = Math.min(Math.max(1, data.total_pages || 1), 500);

        const finalMovies = applyClientFilters(data.results || []);
        showMovies(finalMovies);
        renderPagination();
    } catch (error) {
        console.error("Error fetching movies:", error);
        container.innerHTML = `<div class="empty-state">Failed to load movies.</div>`;

        if (pagination) {
            pagination.innerHTML = "";
        }
    }
}

async function fetchMovies(apiPage) {
    if (filters.creator) {
        const personId = await getPersonId(filters.creator);

        if (!personId) {
            return {
                results: [],
                total_pages: 1,
                total_results: 0
            };
        }

        const url = buildDiscoverUrl(apiPage, { with_people: personId });
        return fetchJSON(url);
    }

    if (filters.title) {
        const params = new URLSearchParams({
            api_key: API_KEY,
            query: filters.title,
            page: apiPage,
            include_adult: "false"
        });

        const url = `${BASE_URL}/search/movie?${params.toString()}`;
        return fetchJSON(url);
    }

    const url = buildDiscoverUrl(apiPage);
    return fetchJSON(url);
}

function buildDiscoverUrl(apiPage, extraParams = {}) {
    const today = new Date().toISOString().split("T")[0];

    const params = new URLSearchParams({
        api_key: API_KEY,
        page: apiPage,
        include_adult: "false",
        include_video: "false",
        sort_by: filters.sortBy || "primary_release_date.desc",
        "primary_release_date.lte": filters.toDate || today
    });

    if (filters.fromDate) {
        params.set("primary_release_date.gte", filters.fromDate);
    }

    Object.entries(extraParams).forEach(([key, value]) => {
        params.set(key, value);
    });

    return `${BASE_URL}/discover/movie?${params.toString()}`;
}

async function getPersonId(name) {
    const params = new URLSearchParams({
        api_key: API_KEY,
        query: name,
        page: 1,
        include_adult: "false"
    });

    const url = `${BASE_URL}/search/person?${params.toString()}`;
    const data = await fetchJSON(url);

    return data.results && data.results.length ? data.results[0].id : null;
}

function applyClientFilters(movies) {
    let filtered = [...movies];
    const today = new Date().toISOString().split("T")[0];

    filtered = filtered.filter(movie =>
        movie.release_date && movie.release_date <= today
    );

    if (filters.creator && filters.title) {
        filtered = filtered.filter(movie =>
            (movie.title || "").toLowerCase().includes(filters.title.toLowerCase())
        );
    }

    if (filters.fromDate) {
        filtered = filtered.filter(movie =>
            movie.release_date && movie.release_date >= filters.fromDate
        );
    }

    if (filters.toDate) {
        filtered = filtered.filter(movie =>
            movie.release_date && movie.release_date <= filters.toDate
        );
    }

    return sortMovies(filtered);
}

function sortMovies(movies) {
    const sorted = [...movies];

    switch (filters.sortBy) {
        case "vote_average.desc":
            sorted.sort((a, b) => (b.vote_average || 0) - (a.vote_average || 0));
            break;

        case "primary_release_date.desc":
            sorted.sort((a, b) => new Date(b.release_date || 0) - new Date(a.release_date || 0));
            break;

        case "primary_release_date.asc":
            sorted.sort((a, b) => new Date(a.release_date || 0) - new Date(b.release_date || 0));
            break;

        default:
            sorted.sort((a, b) => (b.popularity || 0) - (a.popularity || 0));
            break;
    }

    return sorted;
}

function showMovies(movies) {
    if (!container) return;

    container.innerHTML = "";

    if (!movies.length) {
        container.innerHTML = `
            <div class="empty-state">
                No released movies found. Try another title, creator, or date range.
            </div>
        `;
        return;
    }

    movies.forEach(movie => {
        container.appendChild(createMovieCard(movie));
    });
}

function createMovieCard(movie) {
    const movieEl = document.createElement("div");
    movieEl.classList.add("movie-card");

    const image = document.createElement("img");
    image.src = movie.poster_path ? IMG_PATH + movie.poster_path : FALLBACK_IMAGE;
    image.alt = movie.title || "Movie Poster";
    image.onerror = () => {
        image.src = FALLBACK_IMAGE;
    };

    const title = document.createElement("h3");
    title.textContent = (movie.title || "").trim() ? movie.title : "Untitled Movie";

    movieEl.appendChild(image);
    movieEl.appendChild(title);

    movieEl.addEventListener("click", () => {
        window.location.href = `movie.php?id=${movie.id}`;
    });

    return movieEl;
}

function renderCarousel(type, movies) {
    const carousel = carousels[type];

    if (!carousel || !carousel.track) return;

    carousel.track.innerHTML = "";
    carousel.index = 0;

    const groups = chunkArray(movies, 5);
    carousel.total = groups.length;

    if (!groups.length) {
        carousel.track.innerHTML = `<div class="empty-state">No movies found.</div>`;
        updateCarousel(type);
        return;
    }

    groups.forEach(group => {
        const page = document.createElement("div");
        page.classList.add("carousel-page");

        group.forEach(movie => {
            page.appendChild(createMovieCard(movie));
        });

        carousel.track.appendChild(page);
    });

    updateCarousel(type);
}

function moveCarousel(type, direction) {
    const carousel = carousels[type];
    if (!carousel) return;

    carousel.index += direction;

    if (carousel.index < 0) {
        carousel.index = 0;
    }

    if (carousel.index > carousel.total - 1) {
        carousel.index = carousel.total - 1;
    }

    updateCarousel(type);
}

function updateCarousel(type) {
    const carousel = carousels[type];

    if (!carousel || !carousel.track) return;

    carousel.track.style.transform = `translateX(-${carousel.index * 100}%)`;

    if (carousel.prev) {
        carousel.prev.disabled = carousel.index === 0;
    }

    if (carousel.next) {
        carousel.next.disabled = carousel.index >= carousel.total - 1 || carousel.total <= 1;
    }
}

function renderPagination() {
    if (!pagination) return;

    pagination.innerHTML = "";

    pagination.appendChild(createArrowButton("←", currentPage > 1, () => {
        changePage(currentPage - 1);
    }));

    const pages = getPaginationPages(currentPage, totalPages);

    pages.forEach(item => {
        if (item === "...") {
            const dotsElement = document.createElement("span");
            dotsElement.className = "dots";
            dotsElement.textContent = "...";
            pagination.appendChild(dotsElement);
        } else {
            const pageBtn = document.createElement("button");
            pageBtn.textContent = item;

            if (item === currentPage) {
                pageBtn.classList.add("active");
            }

            pageBtn.addEventListener("click", () => changePage(item));
            pagination.appendChild(pageBtn);
        }
    });

    pagination.appendChild(createArrowButton("→", currentPage < totalPages, () => {
        changePage(currentPage + 1);
    }));
}

function createArrowButton(symbol, enabled, onClick) {
    const btn = document.createElement("button");
    btn.textContent = symbol;
    btn.className = "arrow";
    btn.disabled = !enabled;

    if (enabled) {
        btn.addEventListener("click", onClick);
    }

    return btn;
}

function getPaginationPages(current, total) {
    const pages = [];

    if (total <= 3) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
        return pages;
    }

    if (current === 1) {
        pages.push(1, 2, 3, "...");
    } else if (current === total) {
        pages.push("...", total - 2, total - 1, total);
    } else {
        if (current - 1 > 1) {
            pages.push("...");
        }

        pages.push(current - 1, current, current + 1);

        if (current + 1 < total) {
            pages.push("...");
        }
    }

    return pages.filter((item, index, arr) => {
        if (typeof item !== "number") return true;
        return item >= 1 && item <= total && arr.indexOf(item) === index;
    });
}

function changePage(page) {
    getMovies(page);
    scrollToAllMovies();
}

function scrollToAllMovies() {
    if (!allMoviesSection) return;

    window.scrollTo({
        top: allMoviesSection.offsetTop - 80,
        behavior: "smooth"
    });
}

function chunkArray(array, size) {
    const result = [];

    for (let i = 0; i < array.length; i += size) {
        result.push(array.slice(i, i + size));
    }

    return result;
}

async function fetchJSON(url) {
    const response = await fetch(url);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.status_message || data.message || "Failed to fetch data");
    }

    return data;
}