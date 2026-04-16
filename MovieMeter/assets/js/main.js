import { API_KEY, BASE_URL, IMG_PATH } from './api.js';

const FALLBACK_IMAGE = 'assets/images/notfound.png';

let currentPage = 1;
let totalPages = 1;

const $container = $("#movies");
const $pagination = $("#pagination");

const $searchForm = $("#searchForm");
const $searchTitle = $("#searchTitle");
const $searchCreator = $("#searchCreator");
const $fromDate = $("#fromDate");
const $toDate = $("#toDate");
const $sortBy = $("#sortBy");
const $resetFiltersBtn = $("#resetFilters");

const $allMoviesSection = $("#all-movies-section");

const $latestTrack = $("#latestTrack");
const $latestPrev = $("#latestPrev");
const $latestNext = $("#latestNext");

const $topRatedTrack = $("#topRatedTrack");
const $topRatedPrev = $("#topRatedPrev");
const $topRatedNext = $("#topRatedNext");

const $resultsCount = $("#resultsCount");
const $slides = $(".slide");
const $dots = $(".dot");
const $nextSlideBtn = $(".next-slide");
const $prevSlideBtn = $(".prev-slide");
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

let currentSlide = 0;
let autoSlide = null;

function getReturnTo() {
    const fileName = window.location.pathname.split("/").pop() || "index.php";
    return `${fileName}${window.location.search}${window.location.hash}`;
}

function getMovieDetailsUrl(movieId) {
    return `movie.php?id=${encodeURIComponent(movieId)}&return_to=${encodeURIComponent(getReturnTo())}`;
}

function showSlide(index) {
    if (!$slides.length || !$dots.length) return;

    $slides.removeClass("active");
    $dots.removeClass("active");

    $slides.eq(index).addClass("active");
    $dots.eq(index).addClass("active");
}

function nextSlide() {
    if (!$slides.length) return;
    currentSlide = (currentSlide + 1) % $slides.length;
    showSlide(currentSlide);
}

function prevSlide() {
    if (!$slides.length) return;
    currentSlide = (currentSlide - 1 + $slides.length) % $slides.length;
    showSlide(currentSlide);
}

function startAutoSlide() {
    if (!$slides.length) return;
    stopAutoSlide();
    autoSlide = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    if (autoSlide) {
        clearInterval(autoSlide);
    }
}

if ($menuToggle.length && $navLinks.length) {
    $menuToggle.on("click", function () {
        $navLinks.toggleClass("open");
    });

    $(".nav-links a").each(function () {
        $(this).on("click", function () {
            $navLinks.removeClass("open");
        });
    });
}

if ($nextSlideBtn.length) {
    $nextSlideBtn.on("click", function () {
        nextSlide();
        startAutoSlide();
    });
}

if ($prevSlideBtn.length) {
    $prevSlideBtn.on("click", function () {
        prevSlide();
        startAutoSlide();
    });
}

$dots.each(function (index) {
    $(this).on("click", function () {
        currentSlide = index;
        showSlide(currentSlide);
        startAutoSlide();
    });
});

$(window).on("scroll", function () {
    if (!$navbar.length) return;

    if ($(window).scrollTop() > 60) {
        $navbar.addClass("scrolled");
    } else {
        $navbar.removeClass("scrolled");
    }
});

showSlide(currentSlide);
startAutoSlide();

const filters = {
    title: "",
    creator: "",
    fromDate: "",
    toDate: "",
    sortBy: "primary_release_date.desc"
};

const carousels = {
    latest: {
        track: $latestTrack,
        prev: $latestPrev,
        next: $latestNext,
        index: 0,
        total: 0
    },
    topRated: {
        track: $topRatedTrack,
        prev: $topRatedPrev,
        next: $topRatedNext,
        index: 0,
        total: 0
    }
};

init();

async function init() {
    attachEvents();
    await Promise.all([loadLatestMovies(), loadTopRatedMovies()]);
    await getMovies(1);
}

function attachEvents() {
    if ($searchForm.length) {
        $searchForm.on("submit", async function (e) {
            e.preventDefault();

            filters.title = $searchTitle.length ? $searchTitle.val().trim() : "";
            filters.creator = $searchCreator.length ? $searchCreator.val().trim() : "";
            filters.fromDate = $fromDate.length ? $fromDate.val() : "";
            filters.toDate = $toDate.length ? $toDate.val() : "";
            filters.sortBy = $sortBy.length ? $sortBy.val() : "primary_release_date.desc";

            await getMovies(1);
            scrollToAllMovies();
        });
    }

    if ($resetFiltersBtn.length) {
        $resetFiltersBtn.on("click", async function () {
            if ($searchForm.length) {
                $searchForm[0].reset();
            }

            filters.title = "";
            filters.creator = "";
            filters.fromDate = "";
            filters.toDate = "";
            filters.sortBy = "primary_release_date.desc";

            if ($sortBy.length) {
                $sortBy.val("primary_release_date.desc");
            }

            await getMovies(1);
            scrollToAllMovies();
        });
    }

    if ($latestPrev.length && $latestNext.length) {
        $latestPrev.on("click", function () {
            moveCarousel("latest", -1);
        });

        $latestNext.on("click", function () {
            moveCarousel("latest", 1);
        });
    }

    if ($topRatedPrev.length && $topRatedNext.length) {
        $topRatedPrev.on("click", function () {
            moveCarousel("topRated", -1);
        });

        $topRatedNext.on("click", function () {
            moveCarousel("topRated", 1);
        });
    }

    $(".hero-detail-btn").each(function () {
        $(this).on("click", async function (e) {
            e.preventDefault();

            const title = $(this).data("title");

            try {
                const params = new URLSearchParams({
                    api_key: API_KEY,
                    query: title,
                    page: 1,
                    include_adult: "false"
                });

                const url = `${BASE_URL}/search/movie?${params.toString()}`;
                const data = await fetchJSON(url);

                if (data.results && data.results.length > 0) {
                    const movieId = data.results[0].id;
                    window.location.href = getMovieDetailsUrl(movieId);
                } else {
                    alert("Movie not found.");
                }
            } catch (error) {
                console.error("Failed to find movie ID:", error);
                alert("Something went wrong.");
            }
        });
    });
}

async function loadLatestMovies() {
    if (!$latestTrack.length) return;

    try {
        const today = new Date().toISOString().split("T")[0];
        const url = `${BASE_URL}/discover/movie?api_key=${API_KEY}&sort_by=primary_release_date.desc&primary_release_date.lte=${today}&include_adult=false&include_video=false&page=1`;
        const data = await fetchJSON(url);

        const releasedOnly = (data.results || [])
            .filter(movie => movie.release_date && movie.release_date <= today)
            .sort((a, b) => new Date(b.release_date || 0) - new Date(a.release_date || 0));

        renderCarousel("latest", releasedOnly.slice(0, 10));
    } catch (error) {
        if ($latestTrack.length) {
            $latestTrack.html(`<div class="empty-state">Failed to load latest movies.</div>`);
        }
    }
}

async function loadTopRatedMovies() {
    if (!$topRatedTrack.length) return;

    try {
        const today = new Date().toISOString().split("T")[0];
        const url = `${BASE_URL}/movie/top_rated?api_key=${API_KEY}&page=1`;
        const data = await fetchJSON(url);

        const releasedOnly = (data.results || [])
            .filter(movie => movie.release_date && movie.release_date <= today);

        renderCarousel("topRated", releasedOnly.slice(0, 10));
    } catch (error) {
        if ($topRatedTrack.length) {
            $topRatedTrack.html(`<div class="empty-state">Failed to load top rated movies.</div>`);
        }
    }
}

async function getMovies(page) {
    if (!$container.length) return;

    try {
        currentPage = page;

        const data = await fetchMovies(page);

        if ($resultsCount.length) {
            $resultsCount.html(`Found <span class="count-number">${(data.total_results || 0).toLocaleString()}</span> results`);
        }

        totalPages = Math.min(Math.max(1, data.total_pages || 1), 500);

        const finalMovies = applyClientFilters(data.results || []);
        showMovies(finalMovies);
        renderPagination();
    } catch (error) {
        console.error("Error fetching movies:", error);
        $container.html(`<div class="empty-state">Failed to load movies.</div>`);

        if ($pagination.length) {
            $pagination.html("");
        }
    }
}

async function fetchMovies(apiPage) {
    if (filters.creator) {
        const personId = await getPersonId(filters.creator);

        if (!personId) {
            return {
                results: [],
                total_pages: 1,
                total_results: 0
            };
        }

        const url = buildDiscoverUrl(apiPage, { with_people: personId });
        return fetchJSON(url);
    }

    if (filters.title) {
        const params = new URLSearchParams({
            api_key: API_KEY,
            query: filters.title,
            page: apiPage,
            include_adult: "false"
        });

        const url = `${BASE_URL}/search/movie?${params.toString()}`;
        return fetchJSON(url);
    }

    const url = buildDiscoverUrl(apiPage);
    return fetchJSON(url);
}

function buildDiscoverUrl(apiPage, extraParams = {}) {
    const today = new Date().toISOString().split("T")[0];

    const params = new URLSearchParams({
        api_key: API_KEY,
        page: apiPage,
        include_adult: "false",
        include_video: "false",
        sort_by: filters.sortBy || "primary_release_date.desc",
        "primary_release_date.lte": filters.toDate || today
    });

    if (filters.fromDate) {
        params.set("primary_release_date.gte", filters.fromDate);
    }

    Object.entries(extraParams).forEach(([key, value]) => {
        params.set(key, value);
    });

    return `${BASE_URL}/discover/movie?${params.toString()}`;
}

async function getPersonId(name) {
    const params = new URLSearchParams({
        api_key: API_KEY,
        query: name,
        page: 1,
        include_adult: "false"
    });

    const url = `${BASE_URL}/search/person?${params.toString()}`;
    const data = await fetchJSON(url);

    return data.results && data.results.length ? data.results[0].id : null;
}

function applyClientFilters(movies) {
    let filtered = [...movies];
    const today = new Date().toISOString().split("T")[0];

    filtered = filtered.filter(movie =>
        movie.release_date && movie.release_date <= today
    );

    if (filters.creator && filters.title) {
        filtered = filtered.filter(movie =>
            (movie.title || "").toLowerCase().includes(filters.title.toLowerCase())
        );
    }

    if (filters.fromDate) {
        filtered = filtered.filter(movie =>
            movie.release_date && movie.release_date >= filters.fromDate
        );
    }

    if (filters.toDate) {
        filtered = filtered.filter(movie =>
            movie.release_date && movie.release_date <= filters.toDate
        );
    }

    return sortMovies(filtered);
}

function sortMovies(movies) {
    const sorted = [...movies];

    switch (filters.sortBy) {
        case "vote_average.desc":
            sorted.sort((a, b) => (b.vote_average || 0) - (a.vote_average || 0));
            break;

        case "primary_release_date.desc":
            sorted.sort((a, b) => new Date(b.release_date || 0) - new Date(a.release_date || 0));
            break;

        case "primary_release_date.asc":
            sorted.sort((a, b) => new Date(a.release_date || 0) - new Date(b.release_date || 0));
            break;

        default:
            sorted.sort((a, b) => (b.popularity || 0) - (a.popularity || 0));
            break;
    }

    return sorted;
}

function showMovies(movies) {
    if (!$container.length) return;

    $container.html("");

    if (!movies.length) {
        $container.html(`
            <div class="empty-state">
                No released movies found. Try another title, creator, or date range.
            </div>
        `);
        return;
    }

    movies.forEach(movie => {
        $container.append(createMovieCard(movie));
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

    const $title = $("<h3></h3>").text((movie.title || "").trim() ? movie.title : "Untitled Movie");

    $movieEl.append($image);
    $movieEl.append($title);

    $movieEl.on("click", function () {
        window.location.href = getMovieDetailsUrl(movie.id);
    });

    return $movieEl;
}

function renderCarousel(type, movies) {
    const carousel = carousels[type];

    if (!carousel || !carousel.track.length) return;

    carousel.track.html("");
    carousel.index = 0;

    const groups = chunkArray(movies, 5);
    carousel.total = groups.length;

    if (!groups.length) {
        carousel.track.html(`<div class="empty-state">No movies found.</div>`);
        updateCarousel(type);
        return;
    }

    groups.forEach(group => {
        const $page = $("<div></div>").addClass("carousel-page");

        group.forEach(movie => {
            $page.append(createMovieCard(movie));
        });

        carousel.track.append($page);
    });

    updateCarousel(type);
}

function moveCarousel(type, direction) {
    const carousel = carousels[type];
    if (!carousel) return;

    carousel.index += direction;

    if (carousel.index < 0) {
        carousel.index = 0;
    }

    if (carousel.index > carousel.total - 1) {
        carousel.index = carousel.total - 1;
    }

    updateCarousel(type);
}

function updateCarousel(type) {
    const carousel = carousels[type];

    if (!carousel || !carousel.track.length) return;

    carousel.track.css("transform", `translateX(-${carousel.index * 100}%)`);

    if (carousel.prev.length) {
        carousel.prev.prop("disabled", carousel.index === 0);
    }

    if (carousel.next.length) {
        carousel.next.prop("disabled", carousel.index >= carousel.total - 1 || carousel.total <= 1);
    }
}

function renderPagination() {
    if (!$pagination.length) return;

    $pagination.html("");

    $pagination.append(createArrowButton("←", currentPage > 1, function () {
        changePage(currentPage - 1);
    }));

    const pages = getPaginationPages(currentPage, totalPages);

    pages.forEach(item => {
        if (item === "...") {
            const $dotsElement = $("<span></span>").addClass("dots").text("...");
            $pagination.append($dotsElement);
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

function createArrowButton(symbol, enabled, onClick) {
    const $btn = $("<button></button>").text(symbol).addClass("arrow");
    $btn.prop("disabled", !enabled);

    if (enabled) {
        $btn.on("click", onClick);
    }

    return $btn;
}

function getPaginationPages(current, total) {
    const pages = [];

    if (total <= 3) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
        return pages;
    }

    if (current === 1) {
        pages.push(1, 2, 3, "...");
    } else if (current === total) {
        pages.push("...", total - 2, total - 1, total);
    } else {
        if (current - 1 > 1) {
            pages.push("...");
        }

        pages.push(current - 1, current, current + 1);

        if (current + 1 < total) {
            pages.push("...");
        }
    }

    return pages.filter((item, index, arr) => {
        if (typeof item !== "number") return true;
        return item >= 1 && item <= total && arr.indexOf(item) === index;
    });
}

function changePage(page) {
    getMovies(page);
    scrollToAllMovies();
}

function scrollToAllMovies() {
    if (!$allMoviesSection.length) return;

    window.scrollTo({
        top: $allMoviesSection[0].offsetTop - 80,
        behavior: "smooth"
    });
}

function chunkArray(array, size) {
    const result = [];

    for (let i = 0; i < array.length; i += size) {
        result.push(array.slice(i, i + size));
    }

    return result;
}

async function fetchJSON(url) {
    try {
        const data = await $.ajax({
            url: url,
            method: "GET",
            dataType: "json"
        });

        return data;
    } catch (xhr) {
        const data = xhr.responseJSON || {};
        throw new Error(data.status_message || data.message || "Failed to fetch data");
    }
}