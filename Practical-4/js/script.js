console.log("StudentHub Javascript loaded successfully");
console.log("Welcome to StudentHub");
console.log("Practical 4 - Javascript");

// ==============================
// VARIABLES
// ==============================

let studentName = "Jignyasa";
let course = "Information Technology";
let semester = 3;

console.log(studentName);
console.log(course);
console.log(semester);

// ==============================
// DATA TYPES
// ==============================

let college = "Charusat";
let year = 2026;
let isStudent = true;

console.log(college);
console.log(year);
console.log(isStudent);

// ==============================
// FUNCTIONS
// ==============================

function welcomeMessage() {
console.log("Welcome to Student Hub");
}

function welcomeStudent(name) {
console.log("Welcome " + name);
}

welcomeStudent("Jignyasa");

// ==============================
// CHANGE HEADING
// ==============================

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

// ==============================
// LIGHT / DARK THEME
// ==============================

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

// ==============================
// IMAGE SLIDER
// ==============================

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

// ==============================
// FAQ ACCORDION
// ==============================

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

// ==============================
// NOTIFICATION
// ==============================

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

// ==============================
// ANNOUNCEMENT ALERT
// ==============================

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

// ==============================
// WORKSHOP REGISTER BUTTON
// ==============================

const workshopRegister =
document.getElementById("workshop-register");

if (workshopRegister) {

workshopRegister.addEventListener("click", function () {

    alert("Registrations will open soon!");

});

}

// ======================================================
// STUDENT REGISTRATION FORM VALIDATION
// ======================================================

const registrationForm =
document.getElementById("registration-form");

if (registrationForm) {


registrationForm.addEventListener("submit", function (event) {

    // Prevent form from submitting automatically
    event.preventDefault();


    // ==============================
    // GET FORM VALUES
    // ==============================

    const fullname =
        document.getElementById("fullname").value.trim();

    const email =
        document.getElementById("email").value.trim();

    const mobile =
        document.getElementById("mobile").value.trim();

    const password =
        document.getElementById("password").value;

    const confirmPassword =
        document.getElementById("confirm-password").value;

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


    // ==============================
    // REGULAR EXPRESSIONS
    // ==============================

    // Name: only alphabets and spaces
    const nameRegex =
        /^[A-Za-z ]{2,50}$/;


    // Email validation
    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    // Indian mobile number
    // Starts with 6, 7, 8 or 9
    // Total 10 digits
    const mobileRegex =
        /^[6-9][0-9]{9}$/;


    // Password:
    // At least 8 characters
    // One uppercase
    // One lowercase
    // One number
    // One special character
    const passwordRegex =
        /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;


    // ==============================
    // ERROR ELEMENTS
    // ==============================

    const fullnameError =
        document.getElementById("fullname-error");

    const emailError =
        document.getElementById("email-error");

    const mobileError =
        document.getElementById("mobile-error");

    const passwordError =
        document.getElementById("password-error");

    const confirmPasswordError =
        document.getElementById("confirm-password-error");

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


    // Clear previous messages
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


    // ==============================
    // NAME VALIDATION
    // ==============================

    if (fullname === "") {

        fullnameError.innerText =
            "Name is required.";

        isValid = false;

    } else if (!nameRegex.test(fullname)) {

        fullnameError.innerText =
            "Name must contain only alphabets and spaces.";

        isValid = false;

    }


    // ==============================
    // EMAIL VALIDATION
    // ==============================

    if (email === "") {

        emailError.innerText =
            "Email is required.";

        isValid = false;

    } else if (!emailRegex.test(email)) {

        emailError.innerText =
            "Enter a valid email address.";

        isValid = false;

    }


    // ==============================
    // MOBILE VALIDATION
    // ==============================

    if (mobile === "") {

        mobileError.innerText =
            "Mobile number is required.";

        isValid = false;

    } else if (!mobileRegex.test(mobile)) {

        mobileError.innerText =
            "Enter a valid 10-digit mobile number.";

        isValid = false;

    }


    // ==============================
    // PASSWORD VALIDATION
    // ==============================

    if (password === "") {

        passwordError.innerText =
            "Password is required.";

        isValid = false;

    } else if (!passwordRegex.test(password)) {

        passwordError.innerText =
            "Password must contain uppercase, lowercase, number, special character and be at least 8 characters.";

        isValid = false;

    }


    // ==============================
    // CONFIRM PASSWORD
    // ==============================

    if (confirmPassword === "") {

    confirmPasswordError.innerText =
        "Please confirm your password.";

    isValid = false;

} else if (password !== confirmPassword) {

    confirmPasswordError.innerText =
        "Passwords do not match.";

    isValid = false;

} else {

    confirmPasswordError.innerText = "";

}


    // ==============================
    // COURSE VALIDATION
    // ==============================

    if (course === "") {

        courseError.innerText =
            "Please select your course.";

        isValid = false;

    }


    // ==============================
    // YEAR VALIDATION
    // ==============================

    if (year === "") {

        yearError.innerText =
            "Please select your year.";

        isValid = false;

    }


    // ==============================
    // GENDER VALIDATION
    // ==============================

    if (!gender) {

        genderError.innerText =
            "Please select your gender.";

        isValid = false;

    }


    // ==============================
    // TERMS VALIDATION
    // ==============================

    if (!terms) {

        termsError.innerText =
            "You must accept the Terms and Conditions.";

        isValid = false;

    }


    // ==============================
    // FINAL RESULT
    // ==============================

    if (isValid) {

        successMessage.innerText =
            "Registration successful! Welcome to StudentHub 🎓";

        // Reset form after successful registration
        registrationForm.reset();

    }

});

}
