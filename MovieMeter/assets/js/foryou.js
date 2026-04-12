import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

const FALLBACK_IMAGE = "assets/images/no-image.png";

const navbar = document.querySelector(".navbar");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

const trendingMovies = document.getElementById("trendingMovies");
const forYouMovies = document.getElementById("forYouMovies");

const trendingStatus = document.getElementById("trendingStatus");
const forYouStatus = document.getElementById("forYouStatus");

const refreshTrending = document.getElementById("refreshTrending");
const refreshForYou = document.getElementById("refreshForYou");

const genrePool = [28, 12, 16, 35, 80, 18, 14, 27, 9648, 10749, 878, 53];

init();

function getReturnTo() {
    const fileName = window.location.pathname.split("/").pop() || "foryou.php";
    return `${fileName}${window.location.search}${window.location.hash}`;
}

function getMovieDetailsUrl(movieId) {
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

async function init() {
    attachEvents();
    await Promise.all([
        loadTrendingMovies(),
        loadForYouMovies()
    ]);
}

function attachEvents() {
    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", () => {
            navLinks.classList.toggle("open");
        });
    }

    document.querySelectorAll(".nav-links a").forEach((link) => {
        link.addEventListener("click", () => {
            if (navLinks) {
                navLinks.classList.remove("open");
            }
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

    if (refreshTrending) {
        refreshTrending.addEventListener("click", loadTrendingMovies);
    }

    if (refreshForYou) {
        refreshForYou.addEventListener("click", loadForYouMovies);
    }
}

async function loadTrendingMovies() {
    if (!trendingMovies) return;

    try {
        if (trendingStatus) trendingStatus.textContent = "Loading movies...";
        trendingMovies.innerHTML = "";

        const randomPage = getRandomNumber(1, 8);
        const data = await fetchJSON(
            `${BASE_URL}/trending/movie/week?api_key=${API_KEY}&page=${randomPage}`
        );

        const picked = pickFiveUniqueMovies(data.results || []);
        renderMovieRow(trendingMovies, picked);

        if (trendingStatus) trendingStatus.textContent = "";
    } catch (error) {
        console.error(error);
        if (trendingStatus) trendingStatus.textContent = "";
        trendingMovies.innerHTML = `<div class="empty-state">Failed to load trending movies.</div>`;
    }
}

async function loadForYouMovies() {
    if (!forYouMovies) return;

    try {
        if (forYouStatus) forYouStatus.textContent = "Loading movies...";
        forYouMovies.innerHTML = "";

        const randomGenre = genrePool[getRandomNumber(0, genrePool.length - 1)];
        const randomPage = getRandomNumber(1, 12);

        const data = await fetchJSON(
            `${BASE_URL}/discover/movie?api_key=${API_KEY}&with_genres=${randomGenre}&sort_by=popularity.desc&page=${randomPage}&include_adult=false&vote_count.gte=50`
        );

        const picked = pickFiveUniqueMovies(data.results || []);
        renderMovieRow(forYouMovies, picked);

        if (forYouStatus) forYouStatus.textContent = "";
    } catch (error) {
        console.error(error);
        if (forYouStatus) forYouStatus.textContent = "";
        forYouMovies.innerHTML = `<div class="empty-state">Failed to load recommendations.</div>`;
    }
}

function renderMovieRow(container, movies) {
    container.innerHTML = "";

    if (!movies.length) {
        container.innerHTML = `<div class="empty-state">No movies found right now.</div>`;
        return;
    }

    movies.forEach((movie) => {
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
    title.textContent = movie.title || "Untitled Movie";

    movieEl.appendChild(image);
    movieEl.appendChild(title);

    movieEl.addEventListener("click", () => {
        window.location.href = getMovieDetailsUrl(movie.id);
    });

    return movieEl;
}

function pickFiveUniqueMovies(movies) {
    const usable = movies.filter(
        (movie) => movie && movie.id && (movie.poster_path || movie.backdrop_path)
    );

    const shuffled = [...usable].sort(() => Math.random() - 0.5);
    return shuffled.slice(0, 5);
}

function getRandomNumber(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

async function fetchJSON(url) {
    const response = await fetch(url);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.status_message || data.message || "Failed to fetch data");
    }

    return data;
}