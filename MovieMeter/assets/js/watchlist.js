// Default image if a movie poster is missing
const FALLBACK_IMAGE = "assets/images/notfound.png";

// Navbar and watchlist elements
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");
const $watchlistContainer = $("#watchlistMovies");
const $watchlistCount = $("#watchlistCount");

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

function updateWatchlistCount() {
    // Update saved movies count text
    if (!$watchlistCount.length || !$watchlistContainer.length) return;

    const count = $watchlistContainer.find(".watchlist-card").length;
    $watchlistCount.text(`${count} ${count === 1 ? "movie" : "movies"} saved`);
}

function showEmptyState() {
    // Show empty watchlist message
    if (!$watchlistContainer.length) return;

    $watchlistContainer.html(`
        <div class="empty-state">
            Your watchlist is empty.
        </div>
    `);

    updateWatchlistCount();
}

async function sendRemoveRequest(movieId) {
    // Send remove request to server
    const formData = new FormData();
    formData.append("movie_id", String(movieId));

    try {
        const data = await $.ajax({
            url: "remove-from-watchlist.php",
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json"
        });

        if (!data.success) {
            throw new Error(data.message || "Failed to remove movie.");
        }

        return data;
    } catch (xhr) {
        let data = null;

        if (xhr && xhr.responseJSON) {
            data = xhr.responseJSON;
        } else if (xhr && xhr.responseText) {
            try {
                data = JSON.parse(xhr.responseText);
            } catch (error) {
                throw new Error(xhr.responseText || "Invalid server response.");
            }
        }

        throw new Error((data && data.message) || "Failed to remove movie.");
    }
}

async function removeFromWatchlist(movieId, button) {
    // Remove movie from watchlist UI and server
    if (!movieId || !button) {
        alert("Missing movie id.");
        return;
    }

    const $card = $(button).closest(".watchlist-card");
    if (!$card.length || !$watchlistContainer.length) {
        return;
    }

    // Remove card from UI first
    const placeholder = document.createComment("watchlist-card-placeholder");
    $card[0].parentNode.insertBefore(placeholder, $card[0]);
    $card.remove();

    const remainingCards = $watchlistContainer.find(".watchlist-card");

    if (!remainingCards.length) {
        showEmptyState();
    } else {
        updateWatchlistCount();
    }

    try {
        await sendRemoveRequest(movieId);
    } catch (error) {
        // Restore card if server request fails
        const $emptyState = $watchlistContainer.find(".empty-state");
        if ($emptyState.length) {
            $emptyState.remove();
        }

        if (placeholder.parentNode) {
            placeholder.parentNode.insertBefore($card[0], placeholder);
            placeholder.remove();
        }

        updateWatchlistCount();
        console.error("Remove watchlist error:", error);
        alert(error.message || "Error removing movie.");
    }
}

function setupWatchlistCards() {
    // Set up watchlist cards and remove buttons
    if (!$watchlistContainer.length) return;

    const $cards = $watchlistContainer.find(".watchlist-card");

    if (!$cards.length) {
        showEmptyState();
        return;
    }

    $cards.each(function () {
        const $card = $(this);
        const $image = $card.find(".watchlist-poster, img").first();

        // Replace broken poster image
        if ($image.length) {
            $image.on("error", function () {
                $(this).attr("src", FALLBACK_IMAGE);
            });
        }

        const $removeBtn = $card.find(".watchlist-remove-btn, .remove-watchlist-btn").first();

        // Handle remove button click
        if ($removeBtn.length) {
            $removeBtn.on("click", function () {
                const movieId = parseInt(
                    $removeBtn.data("movieId") ||
                    $removeBtn.data("id") ||
                    $card.data("movieId") ||
                    $card.data("id"),
                    10
                );

                removeFromWatchlist(movieId, this);
            });
        }
    });

    updateWatchlistCount();
}

// Initialize watchlist after page loads
$(document).ready(function () {
    setupWatchlistCards();
});