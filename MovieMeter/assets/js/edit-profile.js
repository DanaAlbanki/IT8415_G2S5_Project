// Navbar elements
const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

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

// Add navbar scroll style
$(window).on("scroll", function () {
    if (!$navbar.length) return;

    if ($(window).scrollTop() > 60) {
        $navbar.addClass("scrolled");
    } else {
        $navbar.removeClass("scrolled");
    }
});

// Profile form elements
const $saveBtn = $("#saveBtn");
const $fullNameInput = $("#full_name");
const $usernameInput = $("#username");
const $emailInput = $("#email");
const $imageInput = $("#profile_image");
const $avatarPreviewImage = $("#avatarPreviewImage");
const $avatarFallback = $("#avatarFallback");

// Store original form values
const initialValues = {
    fullName: $fullNameInput.length ? $fullNameInput.val() : "",
    username: $usernameInput.length ? $usernameInput.val() : "",
    email: $emailInput.length ? $emailInput.val() : ""
};

function toggleSaveButton() {
    // Stop if any required form element is missing
    if (
        !$saveBtn.length ||
        !$fullNameInput.length ||
        !$usernameInput.length ||
        !$emailInput.length ||
        !$imageInput.length
    ) return;

    // Check if any text field changed
    const textChanged =
        $fullNameInput.val() !== initialValues.fullName ||
        $usernameInput.val() !== initialValues.username ||
        $emailInput.val() !== initialValues.email;

    // Check if a new image was selected
    const imageInputEl = $imageInput[0];
    const imageChanged = imageInputEl.files && imageInputEl.files.length > 0;

    // Enable save button only when something changed
    $saveBtn.prop("disabled", !(textChanged || imageChanged));
}

// Watch text inputs for changes
[$fullNameInput, $usernameInput, $emailInput].forEach(($input) => {
    if ($input.length) {
        $input.on("input", toggleSaveButton);
    }
});

// Handle profile image upload
if ($imageInput.length) {
    $imageInput.on("change", function () {
        const file = this.files && this.files[0];

        if (!file) {
            toggleSaveButton();
            return;
        }

        // Allow only supported image types
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
            alert("Please choose JPG, JPEG, PNG, or WEBP only.");
            this.value = "";
            toggleSaveButton();
            return;
        }

        // Preview selected image
        const reader = new FileReader();
        reader.onload = function (e) {
            if ($avatarPreviewImage.length) {
                $avatarPreviewImage.attr("src", e.target.result);
                $avatarPreviewImage.css("display", "block");
            }
            if ($avatarFallback.length) {
                $avatarFallback.css("display", "none");
            }
        };
        reader.readAsDataURL(file);

        toggleSaveButton();
    });
}

// Show fallback avatar if preview image fails
if ($avatarPreviewImage.length) {
    $avatarPreviewImage.on("error", function () {
        $(this).css("display", "none");
        if ($avatarFallback.length) {
            $avatarFallback.css("display", "flex");
        }
    });
}

// Set the initial save button state
toggleSaveButton();