const showPassword = document.getElementById("showPassword");
const password = document.getElementById("password");

showPassword.addEventListener("change", function () {
    if (this.checked) {
        password.type = "text";
    } else {
        password.type = "password";
    }
});
document.getElementById("showPassword").addEventListener("change", function () {
    var password = document.getElementById("password");
    var confirmPassword = document.getElementById("confirm_password");

    if (this.checked) {
        password.type = "text";
        confirmPassword.type = "text";
    } else {
        password.type = "password";
        confirmPassword.type = "password";
    }
});
document.querySelectorAll(".page-link").forEach(function (link) {
    link.addEventListener("click", function (e) {
        var href = this.getAttribute("href");
        e.preventDefault();
        document.body.classList.add("page-exit");
        setTimeout(function () {
            window.location.href = href;
        }, 180);
    });
});
