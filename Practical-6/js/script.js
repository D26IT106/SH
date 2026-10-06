console.log("StudentHub Javascript loaded successfully");


// ============================================================
// BASIC VARIABLES
// ============================================================

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


// ============================================================
// WELCOME FUNCTIONS
// ============================================================

function welcomeMessage() {
    console.log("Welcome to Student Hub");
}

function welcomeStudent(name) {
    console.log("Welcome " + name);
}

welcomeMessage();
welcomeStudent("Jignyasa");


// ============================================================
// CHANGE HEADING
// ============================================================

const heading = document.getElementById("main-heading");
const changeHeadingBtn =
    document.getElementById("change-heading");

if (heading && changeHeadingBtn) {

    changeHeadingBtn.addEventListener(
        "click",
        function () {

            if (
                heading.innerText ===
                "Welcome to Student Portal"
            ) {

                heading.innerText =
                    "Welcome to Student Portal ✈️";

                changeHeadingBtn.classList.add("active");

            } else {

                heading.innerText =
                    "Welcome to Student Portal";

                changeHeadingBtn.classList.remove("active");
            }
        }
    );
}


// ============================================================
// DARK MODE
// ============================================================

const themeButton =
    document.getElementById("theme-button");


// Load saved theme

if (localStorage.getItem("theme") === "dark") {

    document.body.classList.add("dark-mode");

    if (themeButton) {

        themeButton.innerText =
            "☀️ Light Mode";

    }
}


// Theme button

if (themeButton) {

    themeButton.addEventListener(
        "click",
        function () {

            document.body.classList.toggle(
                "dark-mode"
            );


            if (
                document.body.classList.contains(
                    "dark-mode"
                )
            ) {

                themeButton.innerText =
                    "☀️ Light Mode";

                localStorage.setItem(
                    "theme",
                    "dark"
                );

            } else {

                themeButton.innerText =
                    "🌙 Dark Mode";

                localStorage.setItem(
                    "theme",
                    "light"
                );

            }

        }
    );
}


// ============================================================
// IMAGE SLIDER
// ============================================================

const slides =
    document.querySelectorAll(".slide");

const nextButton =
    document.getElementById("next-slide");

const previousButton =
    document.getElementById("previous-slide");

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


// Next slide

if (nextButton) {

    nextButton.addEventListener(
        "click",
        function () {

            currentSlide++;

            if (
                currentSlide >=
                slides.length
            ) {

                currentSlide = 0;
            }

            showSlide(currentSlide);

        }
    );
}


// Previous slide

if (previousButton) {

    previousButton.addEventListener(
        "click",
        function () {

            currentSlide--;

            if (currentSlide < 0) {

                currentSlide =
                    slides.length - 1;
            }

            showSlide(currentSlide);

        }
    );
}


// Show first slide

showSlide(currentSlide);


// Automatic slider

if (slides.length > 0) {

    setInterval(function () {

        currentSlide++;

        if (
            currentSlide >=
            slides.length
        ) {

            currentSlide = 0;
        }

        showSlide(currentSlide);

    }, 4000);
}


// ============================================================
// NOTIFICATION
// ============================================================

const notification =
    document.getElementById("notification");

const closeNotification =
    document.getElementById(
        "close-notification"
    );


if (
    notification &&
    closeNotification
) {

    closeNotification.addEventListener(
        "click",
        function () {

            closeNotification.classList.add(
                "active"
            );

            notification.classList.add(
                "hide"
            );

            setTimeout(function () {

                notification.style.display =
                    "none";

            }, 400);

        }
    );
}


// ============================================================
// ANNOUNCEMENT BUTTON
// ============================================================

const announcementButton =
    document.getElementById(
        "announcement-button"
    );


if (announcementButton) {

    announcementButton.addEventListener(
        "click",
        function () {

            alert(
                "📢 Announcement\n\n" +
                "Workshop Registration opening soon!\n" +
                "Please stay tuned for registration details."
            );

        }
    );
}


// ============================================================
// WORKSHOP REGISTER BUTTON
// ============================================================

const workshopRegister =
    document.getElementById(
        "workshop-register"
    );


if (workshopRegister) {

    workshopRegister.addEventListener(
        "click",
        function () {

            alert(
                "Registrations will open soon!"
            );

        }
    );
}


// ============================================================
// REGISTRATION FORM VALIDATION
// ============================================================

const registrationForm =
    document.getElementById(
        "registration-form"
    );


if (registrationForm) {

    const password =
        document.getElementById("password");

    const confirmPassword =
        document.getElementById(
            "confirm-password"
        );

    const confirmPasswordError =
        document.getElementById(
            "confirm-password-error"
        );


    // Password matching function

    function checkPasswords() {

        if (
            !confirmPassword ||
            !confirmPasswordError ||
            !password
        ) {
            return;
        }

        if (
            confirmPassword.value === ""
        ) {

            confirmPasswordError.innerText =
                "";

            return;
        }


        if (
            password.value !==
            confirmPassword.value
        ) {

            confirmPasswordError.innerText =
                "Passwords do not match.";

        } else {

            confirmPasswordError.innerText =
                "";
        }
    }


    if (password) {

        password.addEventListener(
            "input",
            checkPasswords
        );

    }


    if (confirmPassword) {

        confirmPassword.addEventListener(
            "input",
            checkPasswords
        );

    }


    // Registration submit

    registrationForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const fullnameElement =
                document.getElementById(
                    "fullname"
                );

            const emailElement =
                document.getElementById(
                    "email"
                );

            const mobileElement =
                document.getElementById(
                    "mobile"
                );

            const courseElement =
                document.getElementById(
                    "course"
                );

            const yearElement =
                document.getElementById(
                    "year"
                );

            const termsElement =
                document.getElementById(
                    "terms"
                );


            const fullname =
                fullnameElement ?
                fullnameElement.value.trim() :
                "";

            const email =
                emailElement ?
                emailElement.value.trim() :
                "";

            const mobile =
                mobileElement ?
                mobileElement.value.trim() :
                "";

            const passwordValue =
                password ?
                password.value :
                "";

            const confirmPasswordValue =
                confirmPassword ?
                confirmPassword.value :
                "";

            const courseValue =
                courseElement ?
                courseElement.value :
                "";

            const yearValue =
                yearElement ?
                yearElement.value :
                "";

            const gender =
                document.querySelector(
                    'input[name="gender"]:checked'
                );

            const terms =
                termsElement ?
                termsElement.checked :
                false;


            // Validation Regular Expressions

            const nameRegex =
                /^[A-Za-z ]{2,50}$/;

            const emailRegex =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            const mobileRegex =
                /^[6-9][0-9]{9}$/;

            const passwordRegex =
                /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;


            // Error elements

            const fullnameError =
                document.getElementById(
                    "fullname-error"
                );

            const emailError =
                document.getElementById(
                    "email-error"
                );

            const mobileError =
                document.getElementById(
                    "mobile-error"
                );

            const passwordError =
                document.getElementById(
                    "password-error"
                );

            const courseError =
                document.getElementById(
                    "course-error"
                );

            const yearError =
                document.getElementById(
                    "year-error"
                );

            const genderError =
                document.getElementById(
                    "gender-error"
                );

            const termsError =
                document.getElementById(
                    "terms-error"
                );

            const successMessage =
                document.getElementById(
                    "registration-success"
                );


            // Clear errors

            if (fullnameError)
                fullnameError.innerText = "";

            if (emailError)
                emailError.innerText = "";

            if (mobileError)
                mobileError.innerText = "";

            if (passwordError)
                passwordError.innerText = "";

            if (confirmPasswordError)
                confirmPasswordError.innerText = "";

            if (courseError)
                courseError.innerText = "";

            if (yearError)
                yearError.innerText = "";

            if (genderError)
                genderError.innerText = "";

            if (termsError)
                termsError.innerText = "";

            if (successMessage)
                successMessage.innerText = "";


            let isValid = true;


            // Name

            if (fullname === "") {

                if (fullnameError)
                    fullnameError.innerText =
                        "Name is required.";

                isValid = false;

            } else if (
                !nameRegex.test(fullname)
            ) {

                if (fullnameError)
                    fullnameError.innerText =
                        "Name must contain only alphabets and spaces.";

                isValid = false;
            }


            // Email

            if (email === "") {

                if (emailError)
                    emailError.innerText =
                        "Email is required.";

                isValid = false;

            } else if (
                !emailRegex.test(email)
            ) {

                if (emailError)
                    emailError.innerText =
                        "Enter a valid email address.";

                isValid = false;
            }


            // Mobile

            if (mobile === "") {

                if (mobileError)
                    mobileError.innerText =
                        "Mobile number is required.";

                isValid = false;

            } else if (
                !mobileRegex.test(mobile)
            ) {

                if (mobileError)
                    mobileError.innerText =
                        "Enter a valid 10-digit mobile number.";

                isValid = false;
            }


            // Password

            if (passwordValue === "") {

                if (passwordError)
                    passwordError.innerText =
                        "Password is required.";

                isValid = false;

            } else if (
                !passwordRegex.test(
                    passwordValue
                )
            ) {

                if (passwordError)
                    passwordError.innerText =
                        "Password must contain uppercase, lowercase, number, special character and be at least 8 characters.";

                isValid = false;
            }


            // Confirm password

            if (confirmPasswordValue === "") {

                if (confirmPasswordError)
                    confirmPasswordError.innerText =
                        "Please confirm your password.";

                isValid = false;

            } else if (
                passwordValue !==
                confirmPasswordValue
            ) {

                if (confirmPasswordError)
                    confirmPasswordError.innerText =
                        "Passwords do not match.";

                isValid = false;
            }


            // Course

            if (courseValue === "") {

                if (courseError)
                    courseError.innerText =
                        "Please select your course.";

                isValid = false;
            }


            // Year

            if (yearValue === "") {

                if (yearError)
                    yearError.innerText =
                        "Please select your year.";

                isValid = false;
            }


            // Gender

            if (!gender) {

                if (genderError)
                    genderError.innerText =
                        "Please select your gender.";

                isValid = false;
            }


            // Terms

            if (!terms) {

                if (termsError)
                    termsError.innerText =
                        "You must accept the Terms and Conditions.";

                isValid = false;
            }


            // Successful registration

            if (isValid) {

                if (successMessage) {

                    successMessage.innerText =
                        "Registration successful! Welcome to StudentHub 🎓";
                }

                registrationForm.reset();

                if (confirmPasswordError) {

                    confirmPasswordError.innerText =
                        "";
                }
            }

        }
    );
}


// ============================================================
// EVENTS FROM EXTERNAL JSON
// ============================================================

let events = [];
let filteredEvents = [];
let eventPage = 1;

const eventsPerPage = 3;


// ------------------------------------------------------------
// FETCH EVENTS
// ------------------------------------------------------------

const eventList =
    document.getElementById("eventList");


if (eventList) {

    fetch("../data/events.json")

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Unable to load events.json"
                );
            }

            return response.json();

        })

        .then(function (data) {

            events = data;

            filteredEvents = [...events];

            loadEventCategories();

            displayEvents();

        })

        .catch(function (error) {

            console.error(
                "Events Error:",
                error
            );

            eventList.innerHTML =
                "<p>Unable to load events. Please check your JSON file and Live Server.</p>";

        });
}


// ============================================================
// EVENT CATEGORIES
// ============================================================

function loadEventCategories() {

    const filter =
        document.getElementById(
            "eventFilter"
        );


    if (!filter) {
        return;
    }


    // Prevent duplicate categories

    const categories = [
        ...new Set(
            events.map(function (event) {

                return event.category;

            })
        )
    ];


    categories.forEach(function (category) {

        const option =
            document.createElement(
                "option"
            );

        option.value = category;

        option.textContent = category;

        filter.appendChild(option);

    });
}


// ============================================================
// EVENT SEARCH
// ============================================================

const eventSearch =
    document.getElementById(
        "eventSearch"
    );


if (eventSearch) {

    eventSearch.addEventListener(
        "input",
        filterEvents
    );
}


// ============================================================
// EVENT FILTER
// ============================================================

const eventFilter =
    document.getElementById(
        "eventFilter"
    );


if (eventFilter) {

    eventFilter.addEventListener(
        "change",
        filterEvents
    );
}


// ============================================================
// EVENT SORT
// ============================================================

const eventSort =
    document.getElementById(
        "eventSort"
    );


if (eventSort) {

    eventSort.addEventListener(
        "change",
        filterEvents
    );
}


// ============================================================
// FILTER + SEARCH + SORT EVENTS
// ============================================================

function filterEvents() {

    const searchElement =
        document.getElementById(
            "eventSearch"
        );

    const filterElement =
        document.getElementById(
            "eventFilter"
        );

    const sortElement =
        document.getElementById(
            "eventSort"
        );


    const search =
        searchElement ?
        searchElement.value.toLowerCase().trim() :
        "";

    const category =
        filterElement ?
        filterElement.value :
        "all";


    filteredEvents =
        events.filter(function (event) {

            const title =
                String(event.title || "")
                    .toLowerCase();

            const eventCategory =
                String(event.category || "")
                    .toLowerCase();

            const location =
                String(event.location || "")
                    .toLowerCase();


            const matchSearch =
                title.includes(search) ||
                eventCategory.includes(search) ||
                location.includes(search);


            const matchCategory =
                category === "all" ||
                event.category === category;


            return (
                matchSearch &&
                matchCategory
            );

        });


    // Sorting

    const sort =
        sortElement ?
        sortElement.value :
        "default";


    if (sort === "titleAsc") {

        filteredEvents.sort(
            function (a, b) {

                return String(a.title)
                    .localeCompare(
                        String(b.title)
                    );

            }
        );

    }


    if (sort === "titleDesc") {

        filteredEvents.sort(
            function (a, b) {

                return String(b.title)
                    .localeCompare(
                        String(a.title)
                    );

            }
        );

    }


    if (sort === "dateAsc") {

        filteredEvents.sort(
            function (a, b) {

                return new Date(a.date) -
                    new Date(b.date);

            }
        );

    }


    if (sort === "dateDesc") {

        filteredEvents.sort(
            function (a, b) {

                return new Date(b.date) -
                    new Date(a.date);

            }
        );

    }


    // Return to page 1 after search/filter

    eventPage = 1;

    displayEvents();
}


// ============================================================
// DISPLAY EVENTS
// ============================================================

function displayEvents() {

    const container =
        document.getElementById(
            "eventList"
        );


    if (!container) {
        return;
    }


    container.innerHTML = "";


    const start =
        (eventPage - 1) *
        eventsPerPage;

    const end =
        start + eventsPerPage;


    const pageEvents =
        filteredEvents.slice(
            start,
            end
        );


    if (pageEvents.length === 0) {

        container.innerHTML =
            "<p class='loading'>No events found.</p>";

        displayEventPagination();

        return;
    }


    pageEvents.forEach(
        function (event) {

            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "event-card";


            card.innerHTML = `
                <h3>${event.title}</h3>

                <p>
                    <strong>Category:</strong>
                    ${event.category}
                </p>

                <p>
                    <strong>Date:</strong>
                    ${event.date}
                </p>

                <p>
                    <strong>Location:</strong>
                    ${event.location}
                </p>

                <button class="event-register-button">
                    Register
                </button>
            `;


            const registerButton =
                card.querySelector(
                    ".event-register-button"
                );


            registerButton.addEventListener(
                "click",
                function () {

                    registerEvent(
                        event.title
                    );

                }
            );


            container.appendChild(card);

        }
    );


    displayEventPagination();
}


// ============================================================
// EVENT PAGINATION
// ============================================================

function displayEventPagination() {

    const pagination =
        document.getElementById(
            "eventPagination"
        );


    if (!pagination) {
        return;
    }


    pagination.innerHTML = "";


    const totalPages =
        Math.ceil(
            filteredEvents.length /
            eventsPerPage
        );


    if (totalPages <= 1) {
        return;
    }


    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {

        const button =
            document.createElement(
                "button"
            );


        button.textContent = i;


        if (i === eventPage) {

            button.classList.add(
                "active"
            );

        }


        button.addEventListener(
            "click",
            function () {

                eventPage = i;

                displayEvents();

                // Scroll back to event section

                const eventSection =
                    document.querySelector(
                        ".events-section"
                    );

                if (eventSection) {

                    eventSection.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }

            }
        );


        pagination.appendChild(
            button
        );

    }
}


// ============================================================
// EVENT REGISTER
// ============================================================

function registerEvent(eventName) {

    alert(
        "Registration for " +
        eventName +
        " will open soon!"
    );

}


// ============================================================
// STUDENTS FROM EXTERNAL JSON
// ============================================================

let students = [];
let filteredStudents = [];
let studentPage = 1;

const studentsPerPage = 3;


// ------------------------------------------------------------
// FETCH STUDENTS
// ------------------------------------------------------------

const studentList =
    document.getElementById(
        "studentList"
    );


if (studentList) {

    fetch("../data/students.json")

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Unable to load students.json"
                );
            }

            return response.json();

        })

        .then(function (data) {

            students = data;

            filteredStudents =
                [...students];

            displayStudents();

        })

        .catch(function (error) {

            console.error(
                "Students Error:",
                error
            );

            studentList.innerHTML =
                "<p>Unable to load students. Please check your JSON file and Live Server.</p>";

        });
}


// ============================================================
// STUDENT SEARCH
// ============================================================

const studentSearch =
    document.getElementById(
        "studentSearch"
    );


if (studentSearch) {

    studentSearch.addEventListener(
        "input",
        function () {

            const search =
                studentSearch.value
                    .toLowerCase()
                    .trim();


            filteredStudents =
                students.filter(
                    function (student) {

                        const name =
                            String(
                                student.name || ""
                            ).toLowerCase();

                        const studentCourse =
                            String(
                                student.course || ""
                            ).toLowerCase();

                        const studentYear =
                            String(
                                student.year || ""
                            ).toLowerCase();

                        const email =
                            String(
                                student.email || ""
                            ).toLowerCase();


                        return (

                            name.includes(search)

                            ||

                            studentCourse.includes(search)

                            ||

                            studentYear.includes(search)

                            ||

                            email.includes(search)

                        );

                    }
                );


            studentPage = 1;

            displayStudents();

        }
    );
}


// ============================================================
// DISPLAY STUDENTS
// ============================================================

function displayStudents() {

    const container =
        document.getElementById(
            "studentList"
        );


    if (!container) {
        return;
    }


    container.innerHTML = "";


    const start =
        (studentPage - 1) *
        studentsPerPage;

    const end =
        start + studentsPerPage;


    const pageStudents =
        filteredStudents.slice(
            start,
            end
        );


    if (pageStudents.length === 0) {

        container.innerHTML =
            "<p class='loading'>No students found.</p>";

        displayStudentPagination();

        return;
    }


    pageStudents.forEach(
        function (student) {

            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "student-card";


            card.innerHTML = `
                <h3>
                    ${student.name}
                </h3>

                <p>
                    <strong>Course:</strong>
                    ${student.course}
                </p>

                <p>
                    <strong>Year:</strong>
                    ${student.year}
                </p>

                <p>
                    <strong>Email:</strong>
                    ${student.email}
                </p>
            `;


            container.appendChild(
                card
            );

        }
    );


    displayStudentPagination();
}


// ============================================================
// STUDENT PAGINATION
// ============================================================

function displayStudentPagination() {

    const pagination =
        document.getElementById(
            "studentPagination"
        );


    if (!pagination) {
        return;
    }


    pagination.innerHTML = "";


    const totalPages =
        Math.ceil(
            filteredStudents.length /
            studentsPerPage
        );


    if (totalPages <= 1) {
        return;
    }


    for (
        let i = 1;
        i <= totalPages;
        i++
    ) {

        const button =
            document.createElement(
                "button"
            );


        button.textContent = i;


        if (i === studentPage) {

            button.classList.add(
                "active"
            );

        }


        button.addEventListener(
            "click",
            function () {

                studentPage = i;

                displayStudents();


                const studentSection =
                    document.querySelector(
                        ".student-directory"
                    );

                if (studentSection) {

                    studentSection.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }

            }
        );


        pagination.appendChild(
            button
        );

    }
}


// ============================================================
// FAQ FROM EXTERNAL JSON
// ============================================================

let faqs = [];


// ------------------------------------------------------------
// FETCH FAQ
// ------------------------------------------------------------

const faqList =
    document.getElementById(
        "faqList"
    );


if (faqList) {

    fetch("../data/faq.json")

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    "Unable to load faq.json"
                );
            }

            return response.json();

        })

        .then(function (data) {

            faqs = data;

            displayFAQs(faqs);

        })

        .catch(function (error) {

            console.error(
                "FAQ Error:",
                error
            );

            faqList.innerHTML =
                "<p>Unable to load FAQs. Please check your JSON file and Live Server.</p>";

        });
}


// ============================================================
// DISPLAY FAQ
// ============================================================

function displayFAQs(faqData) {

    const container =
        document.getElementById(
            "faqList"
        );


    if (!container) {
        return;
    }


    container.innerHTML = "";


    if (faqData.length === 0) {

        container.innerHTML =
            "<p class='loading'>No matching questions found.</p>";

        return;
    }


    faqData.forEach(
        function (faq) {

            const faqBox =
                document.createElement(
                    "div"
                );


            faqBox.className =
                "faq";


            faqBox.innerHTML = `
                <button class="faq-question">
                    ${faq.question}
                </button>

                <div class="faq-answer">
                    <p>
                        ${faq.answer}
                    </p>
                </div>
            `;


            container.appendChild(
                faqBox
            );


            // FAQ click

            const question =
                faqBox.querySelector(
                    ".faq-question"
                );

            const answer =
                faqBox.querySelector(
                    ".faq-answer"
                );


            question.addEventListener(
                "click",
                function () {

                    const isOpen =
                        question.classList.contains(
                            "active"
                        );


                    // Close all other FAQs

                    document
                        .querySelectorAll(
                            ".faq-question"
                        )
                        .forEach(
                            function (otherQuestion) {

                                otherQuestion
                                    .classList
                                    .remove(
                                        "active"
                                    );

                            }
                        );


                    document
                        .querySelectorAll(
                            ".faq-answer"
                        )
                        .forEach(
                            function (otherAnswer) {

                                otherAnswer
                                    .classList
                                    .remove(
                                        "show"
                                    );

                                otherAnswer
                                    .style
                                    .maxHeight =
                                    null;

                            }
                        );


                    // Open selected FAQ

                    if (!isOpen) {

                        question.classList.add(
                            "active"
                        );

                        answer.classList.add(
                            "show"
                        );

                        answer.style.maxHeight =
                            answer.scrollHeight +
                            "px";

                    }

                }
            );

        }
    );
}


// ============================================================
// FAQ SEARCH
// ============================================================

const faqSearch =
    document.getElementById(
        "faqSearch"
    );


if (faqSearch) {

    faqSearch.addEventListener(
        "input",
        function () {

            const search =
                faqSearch.value
                    .toLowerCase()
                    .trim();


            const result =
                faqs.filter(
                    function (faq) {

                        const question =
                            String(
                                faq.question || ""
                            ).toLowerCase();

                        const answer =
                            String(
                                faq.answer || ""
                            ).toLowerCase();


                        return (
                            question.includes(
                                search
                            )

                            ||

                            answer.includes(
                                search
                            )
                        );

                    }
                );


            displayFAQs(result);

        }
    );
}


// ============================================================
// END OF STUDENTHUB JAVASCRIPT
// ============================================================

console.log(
    "StudentHub Javascript initialized successfully"
);