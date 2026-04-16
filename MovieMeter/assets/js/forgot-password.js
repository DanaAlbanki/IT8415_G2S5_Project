$(document).ready(function () {
    const $form = $("#forgotForm");
    const $emailInput = $("#email");

    if (!$form.length || !$emailInput.length) return;

    $form.on("submit", function (e) {
        const email = $emailInput.val().trim();
        $emailInput.val(email);

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