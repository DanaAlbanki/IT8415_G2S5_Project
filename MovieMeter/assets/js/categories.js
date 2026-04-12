import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

const FALLBACK_IMAGE = "images/no-image.png";

const genresList = document.getElementById("genresList");
const categoryMovies = document.getElementById("categoryMovies");
const selectedCategoryTitle = document.getElementById("selectedCategoryTitle");
const selectedCategorySubtitle = document.getElementById("selectedCategorySubtitle");
const pagination = document.getElementById("pagination");

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const navbar = document.querySelector(".navbar");

let genres = [];
let selectedGenreId = null;
let selectedGenreName = "";
let currentPage = 1;
let totalPages = 1;

init();

async function init() {
    attachEvents();
    await loadGenres();
}

function attachEvents() {
    if (menuToggle) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("open");
        });
    }

    document.querySelectorAll(".nav-links a").forEach(link => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("open");
        });
    });

    window.addEventListener("scroll", () => {
        if (window.scrollY > 60) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }
    });

    document.querySelectorAll("[data-genre-link]").forEach(link => {
        link.addEventListener("click", async (e) => {
            e.preventDefault();

            const genreId = Number(link.getAttribute("data-genre-link"));
            const genre = genres.find(item => item.id === genreId);

            if (!genre) return;

            selectedGenreId = genre.id;
            selectedGenreName = genre.name;

            updateActiveGenreButton();
            await loadMoviesByGenre(1);
            scrollToMoviesSection();
        });
    });
}

async function loadGenres() {
    try {
        const response = await fetch(`${BASE_URL}/genre/movie/list?api_key=${API_KEY}`);

        if (!response.ok) {
            throw new Error("Failed to load genres");
        }

        const data = await response.json();
        genres = data.genres || [];

        if (!genres.length) {
            genresList.innerHTML = `<div class="empty-state">No categories found.</div>`;
            return;
        }

        renderGenres();

        selectedGenreId = genres[0].id;
        selectedGenreName = genres[0].name;

        updateActiveGenreButton();
        await loadMoviesByGenre(1);
    } catch (error) {
        console.error(error);
        genresList.innerHTML = `<div class="empty-state">Failed to load categories.</div>`;
        categoryMovies.innerHTML = `<div class="empty-state">Failed to load category movies.</div>`;
    }
}

function renderGenres() {
    genresList.innerHTML = "";

    genres.forEach(genre => {
        const button = document.createElement("button");
        button.className = "genre-chip";
        button.textContent = genre.name;
        button.dataset.genreId = genre.id;

        button.addEventListener("click", async () => {
            selectedGenreId = genre.id;
            selectedGenreName = genre.name;

            updateActiveGenreButton();
            await loadMoviesByGenre(1);
            scrollToMoviesSection();
        });

        genresList.appendChild(button);
    });
}

function updateActiveGenreButton() {
    document.querySelectorAll(".genre-chip").forEach(button => {
        const genreId = Number(button.dataset.genreId);
        button.classList.toggle("active", genreId === selectedGenreId);
    });
}

async function loadMoviesByGenre(page = 1) {
    if (!selectedGenreId) return;

    try {
        currentPage = page;

        selectedCategoryTitle.textContent = `${selectedGenreName} Movies`;
        selectedCategorySubtitle.textContent = "Loading movies...";
        categoryMovies.innerHTML = "";
        pagination.innerHTML = "";

        const url = `${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${selectedGenreId}&sort_by=popularity.desc&page=${page}&include_adult=false`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error("Failed to load movies");
        }

        const data = await response.json();

        totalPages = Math.min(Math.max(1, data.total_pages || 1), 500);

        selectedCategoryTitle.textContent = `${selectedGenreName} Movies`;
        selectedCategorySubtitle.textContent = `Showing popular ${selectedGenreName.toLowerCase()} movies`;

        renderMovies(data.results || []);
        renderPagination();
    } catch (error) {
        console.error(error);
        selectedCategorySubtitle.textContent = "Could not load movies.";
        categoryMovies.innerHTML = "";
        pagination.innerHTML = "";
    }
}

function renderMovies(movies) {
    categoryMovies.innerHTML = "";

    if (!movies.length) {
        categoryMovies.innerHTML = `
            <div class="empty-state">
                No movies found in this category right now.
            </div>
        `;
        return;
    }

    movies.forEach(movie => {
        categoryMovies.appendChild(createMovieCard(movie));
    });
}

function createMovieCard(movie) {
    const movieEl = document.createElement("div");
    movieEl.classList.add("movie-card");

    const image = document.createElement("img");
    image.src = movie.poster_path ? IMG_PATH + movie.poster_path : FALLBACK_IMAGE;
    image.alt = movie.title || "Movie Poster";

    const title = document.createElement("h3");
    title.textContent = movie.title || "Untitled Movie";

    movieEl.appendChild(image);
    movieEl.appendChild(title);

    movieEl.addEventListener("click", () => {
        window.location.href = `movie.php?id=${movie.id}`;
    });

    return movieEl;
}

function renderPagination() {
    pagination.innerHTML = "";

    pagination.appendChild(createArrowButton("←", currentPage > 1, () => {
        changePage(currentPage - 1);
    }));

    const pages = getPaginationPages(currentPage, totalPages);

    pages.forEach(item => {
        if (item === "...") {
            const dots = document.createElement("span");
            dots.className = "dots";
            dots.textContent = "...";
            pagination.appendChild(dots);
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

function createArrowButton(label, enabled, onClick) {
    const button = document.createElement("button");
    button.textContent = label;
    button.disabled = !enabled;
    button.addEventListener("click", onClick);
    return button;
}

function changePage(page) {
    if (page < 1 || page > totalPages) return;
    loadMoviesByGenre(page);
    scrollToMoviesSection();
}

function getPaginationPages(current, total) {
    if (total <= 5) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    if (current <= 3) {
        return [1, 2, 3, "...", total];
    }

    if (current >= total - 2) {
        return [1, "...", total - 2, total - 1, total];
    }

    return [1, "...", current, current + 1, "...", total];
}

function scrollToMoviesSection() {
    const section = document.getElementById("category-section");

    if (section) {
        section.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}