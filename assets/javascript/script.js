document.addEventListener("DOMContentLoaded", function () {

    console.log("NAVA Fade Studio website loaded.");

    const links = document.querySelectorAll(
        'a[href^="#"]'
    );

    links.forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                this.getAttribute("href");

            const target =
                document.querySelector(targetId);

            if (target) {

                event.preventDefault();

                target.scrollIntoView({
                    behavior: "smooth"
                });

            }

        });

    });

});