document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("forgotForm");
    const emailInput = document.getElementById("email");

    if (!form || !emailInput) return;

    form.addEventListener("submit", function (e) {
        const email = emailInput.value.trim();
        emailInput.value = email;

        if (email === "") {
            e.preventDefault();
            alert("Please enter your email address.");
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailPattern.test(email)) {
            e.preventDefault();
            alert("Please enter a valid email address.");
        }
    });
});