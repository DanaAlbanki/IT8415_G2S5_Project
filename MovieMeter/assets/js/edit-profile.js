const $navbar = $(".navbar");
const $menuToggle = $("#menuToggle");
const $navLinks = $("#navLinks");

if ($menuToggle.length && $navLinks.length) {
    $menuToggle.on("click", function () {
        $navLinks.toggleClass("open");
    });
}

$(".nav-links a").each(function () {
    $(this).on("click", function () {
        if ($navLinks.length) {
            $navLinks.removeClass("open");
        }
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

const $saveBtn = $("#saveBtn");
const $fullNameInput = $("#full_name");
const $usernameInput = $("#username");
const $emailInput = $("#email");
const $imageInput = $("#profile_image");
const $avatarPreviewImage = $("#avatarPreviewImage");
const $avatarFallback = $("#avatarFallback");

const initialValues = {
    fullName: $fullNameInput.length ? $fullNameInput.val() : "",
    username: $usernameInput.length ? $usernameInput.val() : "",
    email: $emailInput.length ? $emailInput.val() : ""
};

function toggleSaveButton() {
    if (
        !$saveBtn.length ||
        !$fullNameInput.length ||
        !$usernameInput.length ||
        !$emailInput.length ||
        !$imageInput.length
    ) return;

    const textChanged =
        $fullNameInput.val() !== initialValues.fullName ||
        $usernameInput.val() !== initialValues.username ||
        $emailInput.val() !== initialValues.email;

    const imageInputEl = $imageInput[0];
    const imageChanged = imageInputEl.files && imageInputEl.files.length > 0;

    $saveBtn.prop("disabled", !(textChanged || imageChanged));
}

[$fullNameInput, $usernameInput, $emailInput].forEach(($input) => {
    if ($input.length) {
        $input.on("input", toggleSaveButton);
    }
});

if ($imageInput.length) {
    $imageInput.on("change", function () {
        const file = this.files && this.files[0];

        if (!file) {
            toggleSaveButton();
            return;
        }

        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
            alert("Please choose JPG, JPEG, PNG, or WEBP only.");
            this.value = "";
            toggleSaveButton();
            return;
        }

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

if ($avatarPreviewImage.length) {
    $avatarPreviewImage.on("error", function () {
        $(this).css("display", "none");
        if ($avatarFallback.length) {
            $avatarFallback.css("display", "flex");
        }
    });
}

toggleSaveButton();