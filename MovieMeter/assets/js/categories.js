import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

const FALLBACK_IMAGE = "images/no-image.png";

const $genresList = $("#genresList");
const $categoryMovies = $("#categoryMovies");
const $selectedCategoryTitle = $("#selectedCategoryTitle");
const $selectedCategorySubtitle = $("#selectedCategorySubtitle");
const $pagination = $("#pagination");

const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");
const $navbar = $(".navbar");

let genres = [];
let selectedGenreId = null;
let selectedGenreName = "";
let currentPage = 1;
let totalPages = 1;

init();

function getReturnTo() {
    const fileName = window.location.pathname.split("/").pop() || "categories.php";
    return `${fileName}${window.location.search}${window.location.hash}`;
}

function getMovieDetailsUrl(movieId) {
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

function readStateFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const genre = Number(params.get("genre"));
    const page = Number(params.get("page"));

    return {
        genre: Number.isFinite(genre) && genre > 0 ? genre : null,
        page: Number.isFinite(page) && page > 0 ? page : 1
    };
}

function updateUrlState() {
    const fileName = window.location.pathname.split("/").pop() || "categories.php";
    const params = new URLSearchParams(window.location.search);

    if (selectedGenreId) {
        params.set("genre", String(selectedGenreId));
    }

    params.set("page", String(currentPage));

    const newUrl = `${fileName}?${params.toString()}`;
    window.history.replaceState({}, "", newUrl);
}

async function init() {
    attachEvents();
    await loadGenres();
}

function attachEvents() {
    if ($menuToggle.length) {
        $menuToggle.on("click", function () {
            $navLinks.toggleClass("open");
        });
    }

    $(".nav-links a").on("click", function () {
        $navLinks.removeClass("open");
    });

    $(window).on("scroll", function () {
        if ($(window).scrollTop() > 60) {
            $navbar.addClass("scrolled");
        } else {
            $navbar.removeClass("scrolled");
        }
    });

    $("[data-genre-link]").on("click", async function (e) {
        e.preventDefault();

        const genreId = Number($(this).attr("data-genre-link"));
        const genre = genres.find(item => item.id === genreId);

        if (!genre) return;

        selectedGenreId = genre.id;
        selectedGenreName = genre.name;

        updateActiveGenreButton();
        await loadMoviesByGenre(1);
        scrollToMoviesSection();
    });
}

async function loadGenres() {
    try {
        const data = await $.ajax({
            url: `${BASE_URL}/genre/movie/list?api_key=${API_KEY}`,
            method: "GET",
            dataType: "json"
        });

        genres = data.genres || [];

        if (!genres.length) {
            $genresList.html(`<div class="empty-state">No categories found.</div>`);
            return;
        }

        renderGenres();

        const state = readStateFromUrl();
        const urlGenre = genres.find(item => item.id === state.genre);

        if (urlGenre) {
            selectedGenreId = urlGenre.id;
            selectedGenreName = urlGenre.name;
        } else {
            selectedGenreId = genres[0].id;
            selectedGenreName = genres[0].name;
        }

        updateActiveGenreButton();
        await loadMoviesByGenre(state.page || 1);
    } catch (error) {
        console.error(error);
        $genresList.html(`<div class="empty-state">Failed to load categories.</div>`);
        $categoryMovies.html(`<div class="empty-state">Failed to load category movies.</div>`);
    }
}

function renderGenres() {
    $genresList.html("");

    genres.forEach(genre => {
        const $button = $("<button></button>");
        $button.addClass("genre-chip");
        $button.text(genre.name);
        $button.attr("data-genre-id", genre.id);

        $button.on("click", async function () {
            selectedGenreId = genre.id;
            selectedGenreName = genre.name;

            updateActiveGenreButton();
            await loadMoviesByGenre(1);
            scrollToMoviesSection();
        });

        $genresList.append($button);
    });
}

function updateActiveGenreButton() {
    $(".genre-chip").each(function () {
        const genreId = Number($(this).attr("data-genre-id"));
        $(this).toggleClass("active", genreId === selectedGenreId);
    });
}

async function loadMoviesByGenre(page = 1) {
    if (!selectedGenreId) return;

    try {
        currentPage = page;

        $selectedCategoryTitle.text(`${selectedGenreName} Movies`);
        $selectedCategorySubtitle.text("Loading movies...");
        $categoryMovies.html("");
        $pagination.html("");

        const url = `${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${selectedGenreId}&sort_by=popularity.desc&page=${page}&include_adult=false`;

        const data = await $.ajax({
            url: url,
            method: "GET",
            dataType: "json"
        });

        totalPages = Math.min(Math.max(1, data.total_pages || 1), 500);

        $selectedCategoryTitle.text(`${selectedGenreName} Movies`);
        $selectedCategorySubtitle.text(`Showing popular ${selectedGenreName.toLowerCase()} movies`);

        updateUrlState();
        renderMovies(data.results || []);
        renderPagination();
    } catch (error) {
        console.error(error);
        $selectedCategorySubtitle.text("Could not load movies.");
        $categoryMovies.html("");
        $pagination.html("");
    }
}

function renderMovies(movies) {
    $categoryMovies.html("");

    if (!movies.length) {
        $categoryMovies.html(`
            <div class="empty-state">
                No movies found in this category right now.
            </div>
        `);
        return;
    }

    movies.forEach(movie => {
        $categoryMovies.append(createMovieCard(movie));
    });
}

function createMovieCard(movie) {
    const $movieEl = $("<div></div>").addClass("movie-card");

    const $image = $("<img>");
    $image.attr("src", movie.poster_path ? IMG_PATH + movie.poster_path : FALLBACK_IMAGE);
    $image.attr("alt", movie.title || "Movie Poster");
    $image.on("error", function () {
        $(this).attr("src", FALLBACK_IMAGE);
    });

    const $title = $("<h3></h3>").text(movie.title || "Untitled Movie");

    $movieEl.append($image);
    $movieEl.append($title);

    $movieEl.on("click", function () {
        window.location.href = getMovieDetailsUrl(movie.id);
    });

    return $movieEl;
}

function renderPagination() {
    $pagination.html("");

    $pagination.append(createArrowButton("←", currentPage > 1, function () {
        changePage(currentPage - 1);
    }));

    const pages = getPaginationPages(currentPage, totalPages);

    pages.forEach(item => {
        if (item === "...") {
            const $dots = $("<span></span>").addClass("dots").text("...");
            $pagination.append($dots);
        } else {
            const $pageBtn = $("<button></button>").text(item);

            if (item === currentPage) {
                $pageBtn.addClass("active");
            }

            $pageBtn.on("click", function () {
                changePage(item);
            });

            $pagination.append($pageBtn);
        }
    });

    $pagination.append(createArrowButton("→", currentPage < totalPages, function () {
        changePage(currentPage + 1);
    }));
}

function createArrowButton(label, enabled, onClick) {
    const $button = $("<button></button>").text(label);
    $button.prop("disabled", !enabled);
    $button.on("click", onClick);
    return $button;
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
    const section = $("#category-section")[0];

    if (section) {
        section.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}