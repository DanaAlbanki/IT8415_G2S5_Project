// Toggles password visibility for the given input field
function togglePassword(inputId, button) {
    // Find the input element by its ID
    const $input = $("#" + inputId);

    // Stop if the input does not exist
    if (!$input.length) return;

    // If password is hidden, show it
    if ($input.attr("type") === "password") {
        $input.attr("type", "text");
        $(button).text("Hide");
    } else {
        // If password is visible, hide it again
        $input.attr("type", "password");
        $(button).text("Show");
    }
}