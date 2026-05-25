import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

// Default image used when a movie poster is missing
const FALLBACK_IMAGE = "assets/images/no-image.png";

// Navbar elements
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

// Movie section elements
const $trendingMovies = $("#trendingMovies");
const $forYouMovies = $("#forYouMovies");

// Status text elements
const $trendingStatus = $("#trendingStatus");
const $forYouStatus = $("#forYouStatus");

// Refresh buttons
const $refreshTrending = $("#refreshTrending");
const $refreshForYou = $("#refreshForYou");

// Genre ids used for random recommendations
const genrePool = [28, 12, 16, 35, 80, 18, 14, 27, 9648, 10749, 878, 53];

// Start the page
init();

function getReturnTo() {
    // Save the current page URL so the user can return back later
    const fileName = window.location.pathname.split("/").pop() || "foryou.php";
    return `${fileName}${window.location.search}${window.location.hash}`;
}

function getMovieDetailsUrl(movieId) {
    // Build the movie details URL with the selected movie id
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

async function init() {
    // Attach events and load both movie sections
    attachEvents();
    await Promise.all([
        loadTrendingMovies(),
        loadForYouMovies()
    ]);
}

function attachEvents() {
    // Toggle mobile navigation menu
    if ($menuToggle.length && $navLinks.length) {
        $menuToggle.on("click", function () {
            $navLinks.toggleClass("open");
        });
    }

    // Close mobile menu when a nav link is clicked
    $(".nav-links a").each(function () {
        $(this).on("click", function () {
            if ($navLinks.length) {
                $navLinks.removeClass("open");
            }
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

    // Reload trending movies when refresh is clicked
    if ($refreshTrending.length) {
        $refreshTrending.on("click", loadTrendingMovies);
    }

    // Reload recommendations when refresh is clicked
    if ($refreshForYou.length) {
        $refreshForYou.on("click", loadForYouMovies);
    }
}

async function loadTrendingMovies() {
    // Load random weekly trending movies
    if (!$trendingMovies.length) return;

    try {
        if ($trendingStatus.length) $trendingStatus.text("Loading movies...");
        $trendingMovies.html("");

        const randomPage = getRandomNumber(1, 8);
        const data = await fetchJSON(
            `${BASE_URL}/trending/movie/week?api_key=${API_KEY}&page=${randomPage}`
        );

        const picked = pickFiveUniqueMovies(data.results || []);
        renderMovieRow($trendingMovies, picked);

        if ($trendingStatus.length) $trendingStatus.text("");
    } catch (error) {
        // Show error message if trending movies fail to load
        console.error(error);
        if ($trendingStatus.length) $trendingStatus.text("");
        $trendingMovies.html(`<div class="empty-state">Failed to load trending movies.</div>`);
    }
}

async function loadForYouMovies() {
    // Load random recommended movies by genre
    if (!$forYouMovies.length) return;

    try {
        if ($forYouStatus.length) $forYouStatus.text("Loading movies...");
        $forYouMovies.html("");

        const randomGenre = genrePool[getRandomNumber(0, genrePool.length - 1)];
        const randomPage = getRandomNumber(1, 12);

        const data = await fetchJSON(
            `${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${randomGenre}&sort_by=popularity.desc&page=${randomPage}&include_adult=false&vote_count.gte=50`
        );

        const picked = pickFiveUniqueMovies(data.results || []);
        renderMovieRow($forYouMovies, picked);

        if ($forYouStatus.length) $forYouStatus.text("");
    } catch (error) {
        // Show error message if recommendations fail to load
        console.error(error);
        if ($forYouStatus.length) $forYouStatus.text("");
        $forYouMovies.html(`<div class="empty-state">Failed to load recommendations.</div>`);
    }
}

function renderMovieRow($container, movies) {
    // Display movies inside the selected container
    $container.html("");

    if (!movies.length) {
        $container.html(`<div class="empty-state">No movies found right now.</div>`);
        return;
    }

    movies.forEach((movie) => {
        $container.append(createMovieCard(movie));
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

function pickFiveUniqueMovies(movies) {
    // Pick up to five usable movies with images
    const usable = movies.filter(
        (movie) => movie && movie.id && (movie.poster_path || movie.backdrop_path)
    );

    const shuffled = [...usable].sort(() => Math.random() - 0.5);
    return shuffled.slice(0, 5);
}

function getRandomNumber(min, max) {
    // Return a random number between min and max
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

async function fetchJSON(url) {
    // Request JSON data from the API
    const data = await $.ajax({
        url: url,
        method: "GET",
        dataType: "json"
    });

    return data;
}