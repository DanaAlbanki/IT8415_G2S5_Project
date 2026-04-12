import { API_KEY, BASE_URL, IMG_PATH } from './api.js';

const BACKDROP_PATH = "https://image.tmdb.org/t/p/original";
const FALLBACK_IMAGE = "images/no-image.png";

const LOCAL_RATING_KEY_PREFIX = "moviemeter_rating_";
const LOCAL_COMMENTS_KEY_PREFIX = "moviemeter_comments_";

const container = document.getElementById("movie-detail");
const params = new URLSearchParams(window.location.search);
const movieId = params.get("id");

if (!movieId) {
  showMessage("Movie not found", "No movie ID was provided.");
} else {
  loadMovie();
}

async function loadMovie() {
  try {
    const response = await fetch(
      `${BASE_URL}/movie/${movieId}?api_key=${API_KEY}&append_to_response=credits,videos`
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
  container.innerHTML = `
    <section class="message-block">
      <h1>${title}</h1>
      <p>${text}</p>
      <a href="index.php" class="action-btn secondary-btn">Back Home</a>
    </section>
  `;
}

function resolvePoster(movie) {
  if (movie.poster_image) return movie.poster_image;
  if (movie.poster_path) return IMG_PATH + movie.poster_path;
  return FALLBACK_IMAGE;
}

function resolveBackdrop(movie, poster) {
  if (movie.backdrop_path) return BACKDROP_PATH + movie.backdrop_path;
  return poster;
}

function getYear(releaseDate) {
  if (!releaseDate) return "";
  return releaseDate.split("-")[0];
}

function getGenres(movie) {
  if (!movie.genres || !movie.genres.length) return "N/A";
  return movie.genres.map(genre => genre.name).join(", ");
}

function getActors(movie) {
  if (!movie.credits || !movie.credits.cast) return "N/A";
  const cast = movie.credits.cast.slice(0, 3).map(actor => actor.name);
  return cast.length ? cast.join(", ") : "N/A";
}

function getDirector(movie) {
  if (!movie.credits || !movie.credits.crew) return "N/A";
  const director = movie.credits.crew.find(person => person.job === "Director");
  return director ? director.name : "N/A";
}

function formatRuntime(minutes) {
  if (!minutes || Number.isNaN(minutes)) return "N/A";
  return `${minutes} min`;
}

function getTrailer(movie) {
  if (movie.trailer_url) {
    return toEmbedUrl(movie.trailer_url);
  }

  const videos = movie.videos?.results || [];

  const trailer =
    videos.find(video => video.site === "YouTube" && video.type === "Trailer" && video.official) ||
    videos.find(video => video.site === "YouTube" && video.type === "Trailer") ||
    videos.find(video => video.site === "YouTube");

  return trailer ? `https://www.youtube.com/embed/${trailer.key}` : null;
}

function toEmbedUrl(url) {
  if (!url) return null;

  if (url.includes("youtube.com/embed/")) return url;

  if (url.includes("watch?v=")) {
    const id = url.split("watch?v=")[1].split("&")[0];
    return `https://www.youtube.com/embed/${id}`;
  }

  if (url.includes("youtu.be/")) {
    const id = url.split("youtu.be/")[1].split("?")[0];
    return `https://www.youtube.com/embed/${id}`;
  }

  return url;
}

function renderInfoRow(label, value, id = "") {
  if (value === undefined || value === null || value === "") return "";

  const idAttr = id ? ` id="${id}"` : "";

  return `
    <div class="info-row">
      <span>${label}</span>
      <strong${idAttr}>${value}</strong>
    </div>
  `;
}

function addToWatchlist(movie) {
  const watchlist = JSON.parse(localStorage.getItem("moviemeter_watchlist")) || [];
  const exists = watchlist.some(item => String(item.id) === String(movie.id));

  if (exists) {
    alert("This movie is already in your watchlist.");
    return;
  }

  const movieData = {
    id: movie.id,
    title: movie.title || "Untitled Movie",
    poster: movie.poster_path ? IMG_PATH + movie.poster_path : FALLBACK_IMAGE,
    release_date: movie.release_date || "",
    rating: movie.vote_average || ""
  };

  watchlist.push(movieData);
  localStorage.setItem("moviemeter_watchlist", JSON.stringify(watchlist));

  alert("Movie added to watchlist.");
}

function getLocalRating(movieId) {
  const value = Number(localStorage.getItem(`${LOCAL_RATING_KEY_PREFIX}${movieId}`));
  return Number.isFinite(value) && value > 0 ? value : 0;
}

function saveLocalRating(movieId, rating) {
  localStorage.setItem(`${LOCAL_RATING_KEY_PREFIX}${movieId}`, String(rating));
}

function getComments(movieId) {
  return JSON.parse(localStorage.getItem(`${LOCAL_COMMENTS_KEY_PREFIX}${movieId}`)) || [];
}

function saveComments(movieId, comments) {
  localStorage.setItem(`${LOCAL_COMMENTS_KEY_PREFIX}${movieId}`, JSON.stringify(comments));
}

function formatCommentDate(dateString) {
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return "";
  return date.toLocaleDateString();
}

function escapeHTML(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function getBaseCommentCount(movie) {
  if (movie.comment_count !== undefined && movie.comment_count !== null && movie.comment_count !== "") {
    return Number(movie.comment_count) || 0;
  }
  return 0;
}

function updateCommentCount(movie) {
  const detailCommentCount = document.getElementById("detailCommentCount");
  if (!detailCommentCount) return;

  const baseCommentCount = getBaseCommentCount(movie);
  const localComments = getComments(movie.id);
  detailCommentCount.textContent = String(baseCommentCount + localComments.length);
}

function renderComments(movieId) {
  const commentsList = document.getElementById("commentsList");
  if (!commentsList) return;

  const comments = getComments(movieId);

  if (!comments.length) {
    commentsList.innerHTML = "";
    return;
  }

  commentsList.innerHTML = comments.map(comment => `
    <div class="comment-line">
      <p class="comment-line-text">${escapeHTML(comment.text)}</p>
      <span class="comment-line-date">${formatCommentDate(comment.date)}</span>
    </div>
  `).join("");
}

function updateStarSelection(stars, selectedRating) {
  stars.forEach(star => {
    const value = Number(star.dataset.value);
    star.classList.toggle("active", value <= selectedRating);
  });
}

function renderMovie(movie) {
  const title = movie.title || "Untitled Movie";
  const year = getYear(movie.release_date);
  const fullTitle = year ? `${title} (${year})` : title;

  const shortDescription = movie.short_description || movie.tagline || "";
  const plot = movie.full_description || movie.overview || "No plot available.";
  const genre = getGenres(movie);
  const actors = getActors(movie);
  const director = getDirector(movie);
  const runtime = formatRuntime(movie.runtime);

  const averageRating =
    movie.average_rating !== undefined && movie.average_rating !== null && movie.average_rating !== 0
      ? movie.average_rating
      : (movie.vote_average ? movie.vote_average.toFixed(1) : "N/A");

  const ratingCount =
    movie.rating_count !== undefined && movie.rating_count !== null && movie.rating_count !== 0
      ? movie.rating_count
      : (movie.vote_count || "N/A");

  const viewCount = movie.view_count ?? "";
  const status = movie.status || "published";

  const poster = resolvePoster(movie);
  const backdrop = resolveBackdrop(movie, poster);
  const trailerUrl = getTrailer(movie);
  const localRating = getLocalRating(movie.id);
  const initialCommentCount = getBaseCommentCount(movie) + getComments(movie.id).length;

  container.innerHTML = `
    <section class="hero" style="background-image: url('${backdrop}')">
      <div class="hero-overlay"></div>

      <div class="hero-inner">
        <div class="poster-column">
          <img src="${poster}" alt="${title}" class="poster-image">
        </div>

        <div class="content-column">
          <h1 class="movie-title">${fullTitle}</h1>

          ${shortDescription ? `<p class="short-description">${shortDescription}</p>` : ""}

          <div class="button-row">
            ${trailerUrl ? `<a href="#trailer-section" class="action-btn primary-btn">Watch Trailer</a>` : ""}
            <button id="watchlistBtn" class="action-btn watchlist-btn" type="button">Add to Watchlist</button>
          </div>
        </div>
      </div>
    </section>

    <section class="details-section">
      <div class="details-grid">
        <article class="detail-panel">
          <h2>${fullTitle}</h2>

          ${renderInfoRow("Genre", genre)}
          ${renderInfoRow("Plot", plot)}
          ${renderInfoRow("Actors", actors)}
          ${renderInfoRow("Director", director)}
          ${renderInfoRow("Runtime", runtime)}
          ${renderInfoRow("Average Rating", averageRating)}
          ${renderInfoRow("Rating Count", ratingCount)}
          ${renderInfoRow("Comment Count", initialCommentCount, "detailCommentCount")}
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
              <button id="saveRatingBtn" class="engagement-btn engagement-btn-primary" type="button">Submit Rating</button>
              <p id="ratingMessage" class="engagement-message">${localRating > 0 ? `Your rating: ${localRating}/5` : ""}</p>
            </div>
          </div>

          <div class="engagement-box">
            <h3>Write a Comment</h3>

            <form id="commentForm" class="comment-form">
              <textarea
                id="commentText"
                class="comment-textarea"
                placeholder="Write your comment here..."
                maxlength="300"
                required
              ></textarea>

              <div class="engagement-actions">
                <button type="submit" class="engagement-btn engagement-btn-secondary">Post Comment</button>
                <p id="commentMessage" class="engagement-message"></p>
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
              src="${trailerUrl}"
              title="${title} Trailer"
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
  `;

  const watchlistBtn = document.getElementById("watchlistBtn");
  if (watchlistBtn) {
    watchlistBtn.addEventListener("click", () => addToWatchlist(movie));
  }

  const stars = [...document.querySelectorAll(".star-btn")];
  const saveRatingBtn = document.getElementById("saveRatingBtn");
  const ratingMessage = document.getElementById("ratingMessage");

  let selectedRating = localRating;

  updateStarSelection(stars, selectedRating);

  stars.forEach(star => {
    star.addEventListener("click", () => {
      selectedRating = Number(star.dataset.value);
      updateStarSelection(stars, selectedRating);
      ratingMessage.textContent = `Selected: ${selectedRating}/5`;
    });
  });

  if (saveRatingBtn) {
    saveRatingBtn.addEventListener("click", () => {
      if (!selectedRating) {
        ratingMessage.textContent = "Please choose a rating first.";
        return;
      }

      saveLocalRating(movie.id, selectedRating);
      ratingMessage.textContent = `Your rating: ${selectedRating}/5`;
    });
  }

  const commentForm = document.getElementById("commentForm");
  const commentText = document.getElementById("commentText");
  const commentMessage = document.getElementById("commentMessage");

  if (commentForm) {
    commentForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const text = commentText.value.trim();

      if (!text) {
        commentMessage.textContent = "Please write a comment first.";
        return;
      }

      const comments = getComments(movie.id);

      comments.unshift({
        id: Date.now(),
        text,
        date: new Date().toISOString()
      });

      saveComments(movie.id, comments);
      commentForm.reset();
      commentMessage.textContent = "Comment posted.";
      renderComments(movie.id);
      updateCommentCount(movie);
    });
  }

  renderComments(movie.id);
  updateCommentCount(movie);
}