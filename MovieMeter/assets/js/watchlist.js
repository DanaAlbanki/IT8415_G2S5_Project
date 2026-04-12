const WATCHLIST_KEY = "moviemeter_watchlist";
const FALLBACK_IMAGE = "assets/images/notfound.png";

const navbar = document.querySelector(".navbar");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const watchlistContainer = document.getElementById("watchlistMovies");
const watchlistCount = document.getElementById("watchlistCount");

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

function getWatchlist() {
    return JSON.parse(localStorage.getItem(WATCHLIST_KEY)) || [];
}

function saveWatchlist(watchlist) {
    localStorage.setItem(WATCHLIST_KEY, JSON.stringify(watchlist));
}

function updateWatchlistCount(count) {
    if (!watchlistCount) return;
    watchlistCount.textContent = `${count} ${count === 1 ? "movie" : "movies"} saved`;
}

function removeFromWatchlist(movieId) {
    const watchlist = getWatchlist().filter((movie) => String(movie.id) !== String(movieId));
    saveWatchlist(watchlist);
    renderWatchlist();
}

function renderWatchlist() {
    if (!watchlistContainer) return;

    const watchlist = getWatchlist();
    watchlistContainer.innerHTML = "";
    updateWatchlistCount(watchlist.length);

    if (!watchlist.length) {
        watchlistContainer.innerHTML = `
            <div class="empty-state">
                Your watchlist is empty.
            </div>
        `;
        return;
    }

    watchlist.forEach((movie) => {
        const card = document.createElement("div");
        card.className = "movie-card watchlist-card";

        card.innerHTML = `
            <a href="movie-details.php?id=${movie.id}" class="movie-card-link">
                <img src="${movie.poster || FALLBACK_IMAGE}" alt="${movie.title || "Movie Poster"}" class="watchlist-poster">
                <h3>${movie.title || "Untitled Movie"}</h3>
            </a>

            <div class="watchlist-card-actions">
                <button type="button" class="watchlist-remove-btn" data-id="${movie.id}">
                    Remove
                </button>
            </div>
        `;

        const image = card.querySelector(".watchlist-poster");
        if (image) {
            image.addEventListener("error", () => {
                image.src = FALLBACK_IMAGE;
            });
        }

        const removeBtn = card.querySelector(".watchlist-remove-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => {
                removeFromWatchlist(movie.id);
            });
        }

        watchlistContainer.appendChild(card);
    });
}

renderWatchlist();