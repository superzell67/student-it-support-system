function logout() {
    let confirmLogout = confirm("Do you want to logout?");

    if (confirmLogout) {
        fetch("../logout.php").then(() => {
            window.location.href = "../login.php";
        });
    }
}

function toggleProfileOptions() {
    const options = document.getElementById("profileOptions");
    options.hidden = !options.hidden;
}
function toggleProfileOptions() {
    const options = document.getElementById("profileOptions");

    if (options.style.display === "none") {
        options.style.display = "block";
    } else {
        options.style.display = "none";
    }
}
 function toggleProfileOptions() {
        const options = document.getElementById("profileOptions");
        if (options.style.display === "none") {
            options.style.display = "block";
        } else {
            options.style.display = "none";
        }
    }

    function openFilePicker() {
        document.getElementById("profileInput").click();
    }

    function uploadProfilePhoto() {
        const fileInput = document.getElementById("profileInput");
        if (fileInput.files.length > 0) {
            document.getElementById("uploadForm").submit();
        }
    }

    document.addEventListener("click", function(event) {
        const wrapper = document.querySelector(".profile-photo-wrapper");
        const options = document.getElementById("profileOptions");
        if (!wrapper.contains(event.target)) {
            options.style.display = "none";
        }
    });