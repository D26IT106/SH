console.log("StudentHub Javascript loaded successfully");

let studentName = "Jignyasa";
let course = "Information Technology";
let semester = 3;

console.log(studentName);
console.log(course);
console.log(semester);

let college = "Charusat";
let year = 2026;
let isStudent = true;

console.log(college);
console.log(year);
console.log(isStudent);

function welcomeMessage() {
    console.log("Welcome to Student Hub");
}

function welcomeStudent(name) {
    console.log("Welcome " + name);
}

welcomeStudent("Jignyasa");


const heading = document.getElementById("main-heading");
const changeHeadingBtn = document.getElementById("change-heading");

if (heading && changeHeadingBtn) {
    changeHeadingBtn.addEventListener("click", function () {
        if (heading.innerText === "Welcome to Student Portal") {
            heading.innerText = "Welcome to Student Portal ✈️";
            changeHeadingBtn.classList.add("active");
        } else {
            heading.innerText = "Welcome to Student Portal";
            changeHeadingBtn.classList.remove("active");
        }
    });
}


const themeButton = document.getElementById("theme-button");

if (themeButton) {
    themeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");

        if (document.body.classList.contains("dark-mode")) {
            themeButton.innerText = "☀️ Light Mode";
            localStorage.setItem("theme", "dark");
        } else {
            themeButton.innerText = "🌙 Dark Mode";
            localStorage.setItem("theme", "light");
        }
    });
}

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark-mode");

    if (themeButton) {
        themeButton.innerText = "☀️ Light Mode";
    }
}


const slides = document.querySelectorAll(".slide");
const nextButton = document.getElementById("next-slide");
const previousButton = document.getElementById("previous-slide");

let currentSlide = 0;

function showSlide(index) {
    if (slides.length === 0) {
        return;
    }

    slides.forEach(function (slide) {
        slide.style.display = "none";
    });

    slides[index].style.display = "block";
}

if (nextButton) {
    nextButton.addEventListener("click", function () {
        currentSlide++;

        if (currentSlide >= slides.length) {
            currentSlide = 0;
        }

        showSlide(currentSlide);
    });
}

if (previousButton) {
    previousButton.addEventListener("click", function () {
        currentSlide--;

        if (currentSlide < 0) {
            currentSlide = slides.length - 1;
        }

        showSlide(currentSlide);
    });
}

showSlide(currentSlide);

if (slides.length > 0) {
    setInterval(function () {
        currentSlide++;

        if (currentSlide >= slides.length) {
            currentSlide = 0;
        }

        showSlide(currentSlide);
    }, 4000);
}


const faqQuestions = document.querySelectorAll(".faq-question");

faqQuestions.forEach(function (question) {
    question.addEventListener("click", function () {
        const currentAnswer = question.nextElementSibling;
        const isOpen = question.classList.contains("active");

        faqQuestions.forEach(function (otherQuestion) {
            const otherAnswer = otherQuestion.nextElementSibling;

            otherQuestion.classList.remove("active");
            otherAnswer.classList.remove("show");
            otherAnswer.style.maxHeight = null;
        });

        if (!isOpen) {
            question.classList.add("active");
            currentAnswer.classList.add("show");
            currentAnswer.style.maxHeight =
                currentAnswer.scrollHeight + "px";
        }
    });
});


const notification = document.getElementById("notification");
const closeNotification = document.getElementById("close-notification");

if (notification && closeNotification) {
    closeNotification.addEventListener("click", function () {
        closeNotification.classList.add("active");
        notification.classList.add("hide");

        setTimeout(function () {
            notification.style.display = "none";
        }, 400);
    });
}


const announcementButton =
    document.getElementById("announcement-button");

if (announcementButton) {
    announcementButton.addEventListener("click", function () {
        alert(
            "📢 Announcement\n\n" +
            "Workshop Registration opening soon!\n" +
            "Please stay tuned for registration details."
        );
    });
}


const workshopRegister =
    document.getElementById("workshop-register");

if (workshopRegister) {
    workshopRegister.addEventListener("click", function () {
        alert("Registrations will open soon!");
    });
}


const registrationForm =
    document.getElementById("registration-form");

if (registrationForm) {

    const password = document.getElementById("password");
    const confirmPassword =
        document.getElementById("confirm-password");
    const confirmPasswordError =
        document.getElementById("confirm-password-error");

    function checkPasswords() {
        if (confirmPassword.value === "") {
            confirmPasswordError.innerText = "";
            return;
        }

        if (password.value !== confirmPassword.value) {
            confirmPasswordError.innerText =
                "Passwords do not match.";
        } else {
            confirmPasswordError.innerText = "";
        }
    }

    password.addEventListener("input", checkPasswords);
    confirmPassword.addEventListener("input", checkPasswords);


    registrationForm.addEventListener("submit", function (event) {
        event.preventDefault();

        const fullname =
            document.getElementById("fullname").value.trim();

        const email =
            document.getElementById("email").value.trim();

        const mobile =
            document.getElementById("mobile").value.trim();

        const passwordValue =
            password.value;

        const confirmPasswordValue =
            confirmPassword.value;

        const course =
            document.getElementById("course").value;

        const year =
            document.getElementById("year").value;

        const gender =
            document.querySelector(
                'input[name="gender"]:checked'
            );

        const terms =
            document.getElementById("terms").checked;


        const nameRegex =
            /^[A-Za-z ]{2,50}$/;

        const emailRegex =
            /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        const mobileRegex =
            /^[6-9][0-9]{9}$/;

        const passwordRegex =
            /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;


        const fullnameError =
            document.getElementById("fullname-error");

        const emailError =
            document.getElementById("email-error");

        const mobileError =
            document.getElementById("mobile-error");

        const passwordError =
            document.getElementById("password-error");

        const courseError =
            document.getElementById("course-error");

        const yearError =
            document.getElementById("year-error");

        const genderError =
            document.getElementById("gender-error");

        const termsError =
            document.getElementById("terms-error");

        const successMessage =
            document.getElementById("registration-success");


        fullnameError.innerText = "";
        emailError.innerText = "";
        mobileError.innerText = "";
        passwordError.innerText = "";
        confirmPasswordError.innerText = "";
        courseError.innerText = "";
        yearError.innerText = "";
        genderError.innerText = "";
        termsError.innerText = "";
        successMessage.innerText = "";

        let isValid = true;


        if (fullname === "") {
            fullnameError.innerText =
                "Name is required.";
            isValid = false;
        } else if (!nameRegex.test(fullname)) {
            fullnameError.innerText =
                "Name must contain only alphabets and spaces.";
            isValid = false;
        }


        if (email === "") {
            emailError.innerText =
                "Email is required.";
            isValid = false;
        } else if (!emailRegex.test(email)) {
            emailError.innerText =
                "Enter a valid email address.";
            isValid = false;
        }


        if (mobile === "") {
            mobileError.innerText =
                "Mobile number is required.";
            isValid = false;
        } else if (!mobileRegex.test(mobile)) {
            mobileError.innerText =
                "Enter a valid 10-digit mobile number.";
            isValid = false;
        }


        if (passwordValue === "") {
            passwordError.innerText =
                "Password is required.";
            isValid = false;
        } else if (!passwordRegex.test(passwordValue)) {
            passwordError.innerText =
                "Password must contain uppercase, lowercase, number, special character and be at least 8 characters.";
            isValid = false;
        }


        if (confirmPasswordValue === "") {
            confirmPasswordError.innerText =
                "Please confirm your password.";
            isValid = false;
        } else if (passwordValue !== confirmPasswordValue) {
            confirmPasswordError.innerText =
                "Passwords do not match.";
            isValid = false;
        }


        if (course === "") {
            courseError.innerText =
                "Please select your course.";
            isValid = false;
        }


        if (year === "") {
            yearError.innerText =
                "Please select your year.";
            isValid = false;
        }


        if (!gender) {
            genderError.innerText =
                "Please select your gender.";
            isValid = false;
        }


        if (!terms) {
            termsError.innerText =
                "You must accept the Terms and Conditions.";
            isValid = false;
        }


        if (isValid) {
            successMessage.innerText =
                "Registration successful! Welcome to StudentHub 🎓";

            registrationForm.reset();
            confirmPasswordError.innerText = "";
        }
    });
}