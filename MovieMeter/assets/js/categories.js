import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

// Default image used when a movie poster is missing
const FALLBACK_IMAGE = "images/no-image.png";

// Page elements
const $genresList = $("#genresList");
const $categoryMovies = $("#categoryMovies");
const $selectedCategoryTitle = $("#selectedCategoryTitle");
const $selectedCategorySubtitle = $("#selectedCategorySubtitle");
const $pagination = $("#pagination");

// Navbar elements
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");
const $navbar = $(".navbar");

// Page state
let genres = [];
let selectedGenreId = null;
let selectedGenreName = "";
let currentPage = 1;
let totalPages = 1;

// Start the page
init();

function getReturnTo() {
    // Save the current page URL so the user can return back later
    const fileName = window.location.pathname.split("/").pop() || "categories.php";
    return `${fileName}${window.location.search}${window.location.hash}`;
}

function getMovieDetailsUrl(movieId) {
    // Build the movie details URL with the selected movie id
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

function readStateFromUrl() {
    // Read selected genre and page number from the URL
    const params = new URLSearchParams(window.location.search);
    const genre = Number(params.get("genre"));
    const page = Number(params.get("page"));

    return {
        genre: Number.isFinite(genre) && genre > 0 ? genre : null,
        page: Number.isFinite(page) && page > 0 ? page : 1
    };
}

function updateUrlState() {
    // Update the URL without refreshing the page
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
    // Attach events and load categories
    attachEvents();
    await loadGenres();
}

function attachEvents() {
    // Toggle mobile menu
    if ($menuToggle.length) {
        $menuToggle.on("click", function () {
            $navLinks.toggleClass("open");
        });
    }

    // Close mobile menu after clicking a nav link
    $(".nav-links a").on("click", function () {
        $navLinks.removeClass("open");
    });

    // Add navbar style when scrolling
    $(window).on("scroll", function () {
        if ($(window).scrollTop() > 60) {
            $navbar.addClass("scrolled");
        } else {
            $navbar.removeClass("scrolled");
        }
    });

    // Handle genre links from navigation or other page sections
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
    // Load movie genres from the API
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
        // Show error messages if categories fail to load
        console.error(error);
        $genresList.html(`<div class="empty-state">Failed to load categories.</div>`);
        $categoryMovies.html(`<div class="empty-state">Failed to load category movies.</div>`);
    }
}

function renderGenres() {
    // Display genre buttons on the page
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
    // Highlight the currently selected genre button
    $(".genre-chip").each(function () {
        const genreId = Number($(this).attr("data-genre-id"));
        $(this).toggleClass("active", genreId === selectedGenreId);
    });
}

async function loadMoviesByGenre(page = 1) {
    // Load movies for the selected genre
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
        // Clear movie area if movies fail to load
        console.error(error);
        $selectedCategorySubtitle.text("Could not load movies.");
        $categoryMovies.html("");
        $pagination.html("");
    }
}

function renderMovies(movies) {
    // Display movie cards
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
    // Create one movie card element
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
    // Display pagination buttons
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
    // Create previous or next button
    const $button = $("<button></button>").text(label);
    $button.prop("disabled", !enabled);
    $button.on("click", onClick);
    return $button;
}

function changePage(page) {
    // Change the current page if it is valid
    if (page < 1 || page > totalPages) return;
    loadMoviesByGenre(page);
    scrollToMoviesSection();
}

function getPaginationPages(current, total) {
    // Decide which page numbers should appear
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
    // Scroll to the movies section
    const section = $("#category-section")[0];

    if (section) {
        section.scrollIntoView({ behavior: "smooth", block: "start" });
    }
}