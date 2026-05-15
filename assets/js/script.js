document.addEventListener("DOMContentLoaded", function () {

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            e.preventDefault();

            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();
            let role = document.getElementById("role").value;

            // ---- SIMPLE DEMO LOGIN LOGIC ----
            if (role === "student") {
                // Temporary login - you can change later
                window.location.href = "student-dashboard.html";
            } 
            else if (role === "faculty") {
                window.location.href = "faculty-dashboard.html";
            }
            else if (role === "admin") {
                window.location.href = "admin-dashboard.html";
            }
            else {
                alert("Invalid Login!");
            }
        });
    }

});