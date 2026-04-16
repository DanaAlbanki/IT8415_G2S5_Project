function togglePassword(inputId, button) {
    const $input = $("#" + inputId);

    if (!$input.length) return;

    if ($input.attr("type") === "password") {
        $input.attr("type", "text");
        $(button).text("Hide");
    } else {
        $input.attr("type", "password");
        $(button).text("Show");
    }
}