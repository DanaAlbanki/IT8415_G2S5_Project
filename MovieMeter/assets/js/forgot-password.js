// Run after the page finishes loading
$(document).ready(function () {
    // Form and email input elements
    const $form = $("#forgotForm");
    const $emailInput = $("#email");

    // Stop if the form or email input does not exist
    if (!$form.length || !$emailInput.length) return;

    // Validate the form before submitting
    $form.on("submit", function (e) {
        // Trim spaces from the email input
        const email = $emailInput.val().trim();
        $emailInput.val(email);

        // Check if email is empty
        if (email === "") {
            e.preventDefault();
            alert("Please enter your email address.");
            return;
        }

        // Basic email format pattern
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Check if email format is valid
        if (!emailPattern.test(email)) {
            e.preventDefault();
            alert("Please enter a valid email address.");
        }
    });
});