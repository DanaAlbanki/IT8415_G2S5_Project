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

function updateWatchlistCount() {
    if (!watchlistCount || !watchlistContainer) return;

    const cards = watchlistContainer.querySelectorAll(".watchlist-card");
    const count = cards.length;

    watchlistCount.textContent = `${count} ${count === 1 ? "movie" : "movies"} saved`;
}

function showEmptyState() {
    if (!watchlistContainer) return;

    watchlistContainer.innerHTML = `
        <div class="empty-state">
            Your watchlist is empty.
        </div>
    `;

    updateWatchlistCount();
}

async function removeFromWatchlist(movieId, button) {
    if (!movieId) {
        alert("Missing movie id.");
        return;
    }

    const oldText = button.textContent;
    button.disabled = true;
    button.textContent = "Removing...";

    try {
        const formData = new FormData();
        formData.append("movie_id", String(movieId));

        const response = await fetch("remove-from-watchlist.php", {
            method: "POST",
            body: formData
        });

        const text = await response.text();

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error(text || "Invalid server response.");
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Failed to remove movie.");
        }

        const card = button.closest(".watchlist-card");
        if (card) {
            card.remove();
        }

        if (watchlistContainer) {
            const remainingCards = watchlistContainer.querySelectorAll(".watchlist-card");
            if (remainingCards.length === 0) {
                showEmptyState();
            } else {
                updateWatchlistCount();
            }
        }
    } catch (error) {
        console.error("Remove watchlist error:", error);
        alert(error.message || "Error removing movie.");
        button.disabled = false;
        button.textContent = oldText;
    }
}

function setupWatchlistCards() {
    if (!watchlistContainer) return;

    const cards = watchlistContainer.querySelectorAll(".watchlist-card");

    if (!cards.length) {
        showEmptyState();
        return;
    }

    cards.forEach((card) => {
        const image = card.querySelector(".watchlist-poster, img");
        if (image) {
            image.addEventListener("error", () => {
                image.src = FALLBACK_IMAGE;
            });
        }

        const removeBtn = card.querySelector(".watchlist-remove-btn, .remove-watchlist-btn");
        if (removeBtn) {
            removeBtn.addEventListener("click", () => {
                const movieId =
                    removeBtn.dataset.movieId ||
                    removeBtn.dataset.id ||
                    card.dataset.movieId ||
                    card.dataset.id;

                removeFromWatchlist(movieId, removeBtn);
            });
        }
    });

    updateWatchlistCount();
}

document.addEventListener("DOMContentLoaded", () => {
    setupWatchlistCards();
});