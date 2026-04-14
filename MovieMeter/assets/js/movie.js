import { API_KEY, BASE_URL, IMG_PATH } from "./api.js";

const BACKDROP_PATH = "https://image.tmdb.org/t/p/original";
const PROFILE_PATH = "https://image.tmdb.org/t/p/w300";
const FALLBACK_IMAGE = "assets/images/notfound.png";

const container = document.getElementById("movie-detail");
const params = new URLSearchParams(window.location.search);
const tmdbId = (params.get("id") || "").trim();

const basePath = window.location.pathname.replace(/\/[^/]*$/, "/");
const ADD_TO_WATCHLIST_URL = `${window.location.origin}${basePath}add-to-watchlist.php`;
const REMOVE_FROM_WATCHLIST_URL = `${window.location.origin}${basePath}remove-from-watchlist.php`;
const ADD_RATING_URL = `${window.location.origin}${basePath}add-rating.php`;
const ADD_COMMENT_URL = `${window.location.origin}${basePath}add-comment.php`;

const pageData = window.moviePageData || {};
const isLoggedIn = Boolean(pageData.isLoggedIn || false);

let dbComments = Array.isArray(pageData.comments) ? pageData.comments : [];
let dbUserRating = Number(pageData.userRating || 0);
let dbSummary = pageData.summary || {
    average_rating: 0,
    rating_count: 0,
    comment_count: 0
};

let isInUserWatchlist = Boolean(pageData.isInWatchlist || false);

if (!tmdbId) {
    showMessage("Movie not found", "No movie ID was provided.");
} else {
    loadMovie();
}

function getReturnTo() {
    return `movie.php?id=${encodeURIComponent(tmdbId)}`;
}

function getMovieDetailsUrl(movieId) {
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

function requireLogin() {
    if (!isLoggedIn) {
        window.location.href = "login.php";
        return false;
    }
    return true;
}

async function loadMovie() {
    try {
        const response = await fetch(
            `${BASE_URL}/movie/${encodeURIComponent(tmdbId)}?api_key=${API_KEY}&append_to_response=credits,videos,recommendations`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch movie details");
        }

        const movie = await response.json();
        renderMovie(movie);
    } catch (error) {
        console.error(error);
        showMessage("Something went wrong", "We could not load this movie right now.");
    }
}

function showMessage(title, text) {
    if (!container) return;

    container.innerHTML = `
        <section class="message-block">
            <h1>${escapeHTML(title)}</h1>
            <p>${escapeHTML(text)}</p>
            <a href="index.php" class="action-btn secondary-btn">Back Home</a>
        </section>
    `;
}

function resolvePoster(movie) {
    return movie.poster_path ? IMG_PATH + movie.poster_path : FALLBACK_IMAGE;
}

function resolveBackdrop(movie, poster) {
    return movie.backdrop_path ? BACKDROP_PATH + movie.backdrop_path : (poster || FALLBACK_IMAGE);
}

function resolveActorImage(actor) {
    return actor.profile_path ? PROFILE_PATH + actor.profile_path : FALLBACK_IMAGE;
}

function getYear(releaseDate) {
    return releaseDate ? releaseDate.split("-")[0] : "";
}

function getGenres(movie) {
    if (!movie.genres || !movie.genres.length) return "N/A";
    return movie.genres.map((genre) => genre.name).join(", ");
}

function getActors(movie) {
    if (!movie.credits || !movie.credits.cast) return "N/A";
    const cast = movie.credits.cast.slice(0, 3).map((actor) => actor.name);
    return cast.length ? cast.join(", ") : "N/A";
}

function getDirector(movie) {
    if (!movie.credits || !movie.credits.crew) return "N/A";
    const director = movie.credits.crew.find((person) => person.job === "Director");
    return director ? director.name : "N/A";
}

function formatRuntime(minutes) {
    if (!minutes || Number.isNaN(minutes)) return "N/A";
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;

    if (!hours) return `${mins} min`;
    if (!mins) return `${hours}h`;
    return `${hours}h ${mins}m`;
}

function getTrailer(movie) {
    const videos = movie.videos?.results || [];

    const trailer =
        videos.find((video) => video.site === "YouTube" && video.type === "Trailer" && video.official) ||
        videos.find((video) => video.site === "YouTube" && video.type === "Trailer") ||
        videos.find((video) => video.site === "YouTube");

    return trailer ? `https://www.youtube.com/embed/${String(trailer.key || "").trim()}` : null;
}

function renderInfoRow(label, value, id = "") {
    if (value === undefined || value === null || value === "") return "";
    const idAttr = id ? ` id="${id}"` : "";

    return `
        <div class="info-row">
            <span>${escapeHTML(label)}</span>
            <strong${idAttr}>${escapeHTML(String(value))}</strong>
        </div>
    `;
}

function updateWatchlistButton() {
    const watchlistBtn = document.getElementById("watchlistBtn");
    if (!watchlistBtn) return;

    if (!isLoggedIn) {
        watchlistBtn.textContent = "Login to Add to Watchlist";
        watchlistBtn.classList.remove("in-watchlist");
        return;
    }

    watchlistBtn.textContent = isInUserWatchlist ? "Remove from Watchlist" : "Add to Watchlist";
    watchlistBtn.classList.toggle("in-watchlist", isInUserWatchlist);
}

async function postForm(url, formData, fallbackMessage) {
    const response = await fetch(url, {
        method: "POST",
        body: formData
    });

    const text = await response.text();

    let data;
    try {
        data = JSON.parse(text);
    } catch (e) {
        throw new Error(text || fallbackMessage);
    }

    if (!response.ok || !data.success) {
        throw new Error(data.message || fallbackMessage);
    }

    return data;
}

async function addToWatchlist() {
    const formData = new FormData();
    formData.append("tmdb_id", tmdbId);
    return await postForm(ADD_TO_WATCHLIST_URL, formData, "Failed to add to watchlist.");
}

async function removeFromWatchlist() {
    const formData = new FormData();
    formData.append("tmdb_id", tmdbId);
    return await postForm(REMOVE_FROM_WATCHLIST_URL, formData, "Failed to remove from watchlist.");
}

async function toggleWatchlistOnServer(button) {
    if (!button || button.disabled) return;

    if (!requireLogin()) return;

    const oldState = isInUserWatchlist;
    button.disabled = true;

    try {
        if (isInUserWatchlist) {
            isInUserWatchlist = false;
            updateWatchlistButton();
            await removeFromWatchlist();
        } else {
            isInUserWatchlist = true;
            updateWatchlistButton();
            await addToWatchlist();
        }
    } catch (error) {
        console.error("Watchlist error:", error);
        isInUserWatchlist = oldState;
        updateWatchlistButton();
        alert(error && error.message ? error.message : "Watchlist error");
    } finally {
        button.disabled = false;
    }
}

function formatCommentDate(dateString) {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) return "";
    return date.toLocaleString();
}

function escapeHTML(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

function renderCommentsList(comments) {
    const commentsList = document.getElementById("commentsList");
    if (!commentsList) return;

    if (!comments.length) {
        commentsList.innerHTML = `
            <div class="comment-line">
                <p class="comment-line-text">No comments yet.</p>
            </div>
        `;
        return;
    }

    commentsList.innerHTML = comments.map((comment) => `
        <div class="comment-line">
            <div class="comment-line-head">
                <strong class="comment-line-user">${escapeHTML(comment.display_name || "User")}</strong>
                <span class="comment-line-date">${escapeHTML(formatCommentDate(comment.created_at))}</span>
            </div>
            <p class="comment-line-text">${escapeHTML(comment.comment_text || "")}</p>
        </div>
    `).join("");
}

function updateStarSelection(stars, selectedRating) {
    stars.forEach((star) => {
        const value = Number(star.dataset.value);
        star.classList.toggle("active", value <= selectedRating);
    });
}

function updateSummaryUI(summary) {
    dbSummary = summary || dbSummary;

    const averageEl = document.getElementById("detailAverageRating");
    const ratingCountEl = document.getElementById("detailRatingCount");
    const commentCountEl = document.getElementById("detailCommentCount");

    if (averageEl) {
        averageEl.textContent = Number(dbSummary.average_rating || 0).toFixed(1);
    }

    if (ratingCountEl) {
        ratingCountEl.textContent = String(Number(dbSummary.rating_count || 0));
    }

    if (commentCountEl) {
        commentCountEl.textContent = String(Number(dbSummary.comment_count || 0));
    }
}

async function submitRating(ratingValue) {
    const formData = new FormData();
    formData.append("tmdb_id", tmdbId);
    formData.append("rating_value", String(ratingValue));
    return await postForm(ADD_RATING_URL, formData, "Failed to submit rating.");
}

async function submitComment(commentTextValue) {
    const formData = new FormData();
    formData.append("tmdb_id", tmdbId);
    formData.append("comment_text", commentTextValue);
    return await postForm(ADD_COMMENT_URL, formData, "Failed to post comment.");
}

function createActorCard(actor) {
    const actorName = actor?.name || "Unknown Actor";
    const characterName = actor?.character || "Cast";
    const actorImage = resolveActorImage(actor);

    return `
        <article class="actor-card">
            <div class="actor-image-wrap">
                <img
                    src="${escapeHTML(actorImage)}"
                    alt="${escapeHTML(actorName)}"
                    class="actor-image"
                    onerror="this.src='${FALLBACK_IMAGE}'"
                >
            </div>
            <div class="actor-info">
                <h3>${escapeHTML(actorName)}</h3>
                <p>${escapeHTML(characterName)}</p>
            </div>
        </article>
    `;
}

function renderActorsSection(movie) {
    const cast = movie?.credits?.cast || [];

    if (!cast.length) {
        return `
            <section class="movie-extra-section actors-section">
                <div class="extra-section-head">
                    <h2>ACTORS</h2>
                </div>
                <div class="empty-extra-box">No cast information available.</div>
            </section>
        `;
    }

    const visibleCast = cast.slice(0, 6);
    const hiddenCast = cast.slice(6);

    return `
        <section class="movie-extra-section actors-section">
            <div class="extra-section-head">
                <h2>ACTORS</h2>

                ${hiddenCast.length ? `
                    <label class="actors-toggle">
                        <input type="checkbox" id="actorsToggle">
                        <span class="actors-toggle-slider"></span>
                        <span class="actors-toggle-text">Show all</span>
                    </label>
                ` : ""}
            </div>

            <div class="actors-grid" id="actorsGrid">
                ${visibleCast.map(createActorCard).join("")}
                ${hiddenCast.map(actor => `
                    <div class="actor-hidden-item" data-hidden-actor="true">
                        ${createActorCard(actor)}
                    </div>
                `).join("")}
            </div>
        </section>
    `;
}

function createRecommendationCard(movie) {
    const poster = resolvePoster(movie);
    const title = movie?.title || "Untitled Movie";

    return `
        <article class="recommendation-card" data-recommendation-id="${escapeHTML(String(movie.id || ""))}">
            <img
                src="${escapeHTML(poster)}"
                alt="${escapeHTML(title)}"
                class="recommendation-poster"
                onerror="this.src='${FALLBACK_IMAGE}'"
            >

            <div class="recommendation-info">
                <h3>${escapeHTML(title)}</h3>
            </div>
        </article>
    `;
}

function renderRecommendationsSection(movie) {
    const recommendations = movie?.recommendations?.results || [];

    if (!recommendations.length) {
        return `
            <section class="movie-extra-section recommendations-section">
                <div class="extra-section-head single-head">
                    <h2>RECOMMENDATIONS</h2>
                </div>
                <div class="empty-extra-box">No recommendations available for this movie.</div>
            </section>
        `;
    }

    const releasedOnly = recommendations.filter(item => item.release_date);

    return `
        <section class="movie-extra-section recommendations-section">
            <div class="extra-section-head single-head">
                <h2>RECOMMENDATIONS</h2>
            </div>

            <div class="recommendations-grid" id="recommendationsGrid">
                ${releasedOnly.slice(0, 10).map(createRecommendationCard).join("")}
            </div>
        </section>
    `;
}

function getGenreNamesFromIds(ids) {
    const genreMap = {
        28: "Action",
        12: "Adventure",
        16: "Animation",
        35: "Comedy",
        80: "Crime",
        99: "Documentary",
        18: "Drama",
        10751: "Family",
        14: "Fantasy",
        36: "History",
        27: "Horror",
        10402: "Music",
        9648: "Mystery",
        10749: "Romance",
        878: "Science Fiction",
        10770: "TV Movie",
        53: "Thriller",
        10752: "War",
        37: "Western"
    };

    return ids.map(id => genreMap[id]).filter(Boolean);
}

function setupActorsToggle() {
    const toggle = document.getElementById("actorsToggle");
    const hiddenItems = document.querySelectorAll("[data-hidden-actor='true']");

    if (!toggle || !hiddenItems.length) return;

    toggle.addEventListener("change", () => {
        hiddenItems.forEach(item => {
            item.classList.toggle("show", toggle.checked);
        });
    });
}

function setupRecommendationClicks() {
    document.querySelectorAll("[data-recommendation-id]").forEach(card => {
        card.addEventListener("click", () => {
            const movieId = card.getAttribute("data-recommendation-id");
            if (!movieId) return;
            window.location.href = getMovieDetailsUrl(movieId);
        });
    });
}

function renderMovie(movie) {
    if (!container) return;

    const title = movie.title || "Untitled Movie";
    const year = getYear(movie.release_date);
    const fullTitle = year ? `${title} (${year})` : title;

    const shortDescription = movie.tagline || "";
    const plot = movie.overview || "No plot available.";
    const genre = getGenres(movie);
    const actors = getActors(movie);
    const director = getDirector(movie);
    const runtime = formatRuntime(movie.runtime);

    const averageRating = Number(dbSummary.average_rating || 0).toFixed(1);
    const ratingCount = Number(dbSummary.rating_count || 0);
    const commentCount = Number(dbSummary.comment_count || 0);
    const viewCount = movie.popularity ? Math.round(movie.popularity) : "";
    const status = "published";

    const poster = resolvePoster(movie);
    const backdrop = resolveBackdrop(movie, poster);
    const trailerUrl = getTrailer(movie);

    const ratingMessageText = isLoggedIn
        ? (dbUserRating > 0 ? `Your rating: ${dbUserRating}/5` : "")
        : "Login to rate this movie.";

    const commentMessageText = isLoggedIn
        ? ""
        : "Login to post a comment.";

    container.innerHTML = `
        <section class="hero" style="background-image: url('${escapeHTML(backdrop)}')">
            <div class="hero-overlay"></div>

            <div class="hero-inner">
                <div class="poster-column">
                    <img src="${escapeHTML(poster)}" alt="${escapeHTML(title)}" class="poster-image">
                </div>

                <div class="content-column">
                    <h1 class="movie-title">${escapeHTML(fullTitle)}</h1>

                    ${shortDescription ? `<p class="short-description">${escapeHTML(shortDescription)}</p>` : ""}

                    <div class="button-row">
                        ${trailerUrl ? `<a href="#trailer-section" class="action-btn primary-btn">Watch Trailer</a>` : ""}
                        <button id="watchlistBtn" class="action-btn watchlist-btn" type="button"></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="details-section">
            <div class="details-grid">
                <article class="detail-panel">
                    <h2>${escapeHTML(fullTitle)}</h2>

                    ${renderInfoRow("Genre", genre)}
                    ${renderInfoRow("Plot", plot)}
                    ${renderInfoRow("Actors", actors)}
                    ${renderInfoRow("Director", director)}
                    ${renderInfoRow("Runtime", runtime)}
                    ${renderInfoRow("Average Rating", averageRating, "detailAverageRating")}
                    ${renderInfoRow("Rating Count", ratingCount, "detailRatingCount")}
                    ${renderInfoRow("Comment Count", commentCount, "detailCommentCount")}
                    ${renderInfoRow("View Count", viewCount)}
                    ${renderInfoRow("Status", status)}
                </article>
            </div>
        </section>

        <section class="engagement-section">
            <div class="engagement-section-inner">
                <div class="engagement-heading">
                    <h2>RATE & COMMENTS</h2>
                    <p>Rate this movie and share your thoughts.</p>
                </div>

                <div class="engagement-layout">
                    <div class="engagement-box">
                        <h3>Your Rating</h3>

                        <div class="star-rating" id="starRating">
                            <button type="button" class="star-btn" data-value="1">★</button>
                            <button type="button" class="star-btn" data-value="2">★</button>
                            <button type="button" class="star-btn" data-value="3">★</button>
                            <button type="button" class="star-btn" data-value="4">★</button>
                            <button type="button" class="star-btn" data-value="5">★</button>
                        </div>

                        <div class="engagement-actions">
                            <button id="saveRatingBtn" class="engagement-btn engagement-btn-primary" type="button">
                                ${isLoggedIn ? "Submit Rating" : "Login to Rate"}
                            </button>
                            <p id="ratingMessage" class="engagement-message">${escapeHTML(ratingMessageText)}</p>
                        </div>
                    </div>

                    <div class="engagement-box">
                        <h3>Write a Comment</h3>

                        <form id="commentForm" class="comment-form">
                            <textarea
                                id="commentText"
                                class="comment-textarea"
                                placeholder="${isLoggedIn ? "Write your comment here..." : "Login to write a comment..."}"
                                maxlength="1000"
                                ${isLoggedIn ? "required" : ""}
                            ></textarea>

                            <div class="engagement-actions">
                                <button type="submit" class="engagement-btn engagement-btn-secondary">
                                    ${isLoggedIn ? "Post Comment" : "Login to Comment"}
                                </button>
                                <p id="commentMessage" class="engagement-message">${escapeHTML(commentMessageText)}</p>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="commentsList" class="comments-list"></div>
            </div>
        </section>

        <section class="trailer-section" id="trailer-section">
            <div class="section-head">
                <h2>Trailer</h2>
            </div>

            ${trailerUrl
                ? `
                    <div class="trailer-frame">
                        <iframe
                            src="${escapeHTML(trailerUrl)}"
                            title="${escapeHTML(title)} Trailer"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    </div>
                `
                : `
                    <div class="no-trailer">
                        Trailer not available for this movie.
                    </div>
                `
            }
        </section>

        ${renderActorsSection(movie)}

        ${renderRecommendationsSection(movie)}
    `;

    const posterImage = document.querySelector(".poster-image");
    if (posterImage) {
        posterImage.addEventListener("error", () => {
            posterImage.src = FALLBACK_IMAGE;
        });
    }

    const heroSection = document.querySelector(".hero");
    if (heroSection && backdrop.includes("image.tmdb.org")) {
        const testImage = new Image();
        testImage.onerror = () => {
            heroSection.style.backgroundImage = `url('${FALLBACK_IMAGE}')`;
        };
        testImage.src = backdrop;
    }

    const watchlistBtn = document.getElementById("watchlistBtn");
    if (watchlistBtn) {
        updateWatchlistButton();
        watchlistBtn.addEventListener("click", () => {
            toggleWatchlistOnServer(watchlistBtn);
        });
    }

    const stars = [...document.querySelectorAll(".star-btn")];
    const saveRatingBtn = document.getElementById("saveRatingBtn");
    const ratingMessage = document.getElementById("ratingMessage");

    let selectedRating = dbUserRating;

    updateStarSelection(stars, selectedRating);
    renderCommentsList(dbComments);
    setupActorsToggle();
    setupRecommendationClicks();

    stars.forEach((star) => {
        star.addEventListener("click", () => {
            if (!requireLogin()) return;

            selectedRating = Number(star.dataset.value);
            updateStarSelection(stars, selectedRating);
            if (ratingMessage) {
                ratingMessage.textContent = `Selected: ${selectedRating}/5`;
            }
        });
    });

    if (saveRatingBtn) {
        saveRatingBtn.addEventListener("click", async () => {
            if (!requireLogin()) return;

            if (!selectedRating) {
                if (ratingMessage) {
                    ratingMessage.textContent = "Please choose a rating first.";
                }
                return;
            }

            saveRatingBtn.disabled = true;
            if (ratingMessage) {
                ratingMessage.textContent = "Submitting rating...";
            }

            try {
                const data = await submitRating(selectedRating);
                dbUserRating = Number(data.user_rating || selectedRating);

                if (ratingMessage) {
                    ratingMessage.textContent = `Your rating: ${dbUserRating}/5`;
                }

                updateSummaryUI(data.summary);
                updateStarSelection(stars, dbUserRating);
            } catch (error) {
                console.error(error);
                if (ratingMessage) {
                    ratingMessage.textContent = error.message || "Error submitting rating.";
                }
            } finally {
                saveRatingBtn.disabled = false;
            }
        });
    }

    const commentForm = document.getElementById("commentForm");
    const commentText = document.getElementById("commentText");
    const commentMessage = document.getElementById("commentMessage");
    const commentSubmitBtn = commentForm ? commentForm.querySelector("button[type='submit']") : null;

    if (commentForm && commentText && commentSubmitBtn) {
        commentForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            if (!requireLogin()) return;

            const text = commentText.value.trim();

            if (!text) {
                if (commentMessage) {
                    commentMessage.textContent = "Please write a comment first.";
                }
                return;
            }

            commentSubmitBtn.disabled = true;
            if (commentMessage) {
                commentMessage.textContent = "Posting comment...";
            }

            try {
                const data = await submitComment(text);
                dbComments = Array.isArray(data.comments) ? data.comments : dbComments;

                if (commentMessage) {
                    commentMessage.textContent = "Comment posted.";
                }

                commentForm.reset();
                renderCommentsList(dbComments);
                updateSummaryUI(data.summary);
            } catch (error) {
                console.error(error);
                if (commentMessage) {
                    commentMessage.textContent = error.message || "Error posting comment.";
                }
            } finally {
                commentSubmitBtn.disabled = false;
            }
        });
    }
}