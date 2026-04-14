const navbar = document.querySelector(".navbar");
const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

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

const saveBtn = document.getElementById("saveBtn");
const fullNameInput = document.getElementById("full_name");
const usernameInput = document.getElementById("username");
const emailInput = document.getElementById("email");
const imageInput = document.getElementById("profile_image");
const avatarPreviewImage = document.getElementById("avatarPreviewImage");
const avatarFallback = document.getElementById("avatarFallback");

const initialValues = {
    fullName: fullNameInput ? fullNameInput.value : "",
    username: usernameInput ? usernameInput.value : "",
    email: emailInput ? emailInput.value : ""
};

function toggleSaveButton() {
    if (!saveBtn || !fullNameInput || !usernameInput || !emailInput || !imageInput) return;

    const textChanged =
        fullNameInput.value !== initialValues.fullName ||
        usernameInput.value !== initialValues.username ||
        emailInput.value !== initialValues.email;

    const imageChanged = imageInput.files && imageInput.files.length > 0;

    saveBtn.disabled = !(textChanged || imageChanged);
}

[fullNameInput, usernameInput, emailInput].forEach((input) => {
    if (input) {
        input.addEventListener("input", toggleSaveButton);
    }
});

if (imageInput) {
    imageInput.addEventListener("change", function () {
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
            if (avatarPreviewImage) {
                avatarPreviewImage.src = e.target.result;
                avatarPreviewImage.style.display = "block";
            }
            if (avatarFallback) {
                avatarFallback.style.display = "none";
            }
        };
        reader.readAsDataURL(file);

        toggleSaveButton();
    });
}

if (avatarPreviewImage) {
    avatarPreviewImage.addEventListener("error", function () {
        this.style.display = "none";
        if (avatarFallback) {
            avatarFallback.style.display = "flex";
        }
    });
}

toggleSaveButton();