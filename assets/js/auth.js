
const showPassword = document.getElementById("showPassword");
const password = document.getElementById("password");
const confirmPassword = document.getElementById("confirm_password");

if (showPassword && password) {
    showPassword.addEventListener("change", function () {
        const type = this.checked ? "text" : "password";
        password.type = type;

        if (confirmPassword) {
            confirmPassword.type = type;
        }
    });
}

document.querySelectorAll(".page-link").forEach(function (link) {
    link.addEventListener("click", function (e) {
        const href = link.getAttribute("href");

        if (!href) return;

        e.preventDefault();
        document.body.classList.add("page-exit");

        setTimeout(function () {
            window.location.href = href;
        }, 180);
    });
});
