const inputNom = document.getElementById("nameReviewInput");
const inputPrenom = document.getElementById("firstnameReviwInput");
const selectMenu = document.getElementById("menuReviewFormSelect");
const textareaReview = document.getElementById("reviewTextarea");
const starRating = document.querySelector(".star-rating");
const btnValidation = document.getElementById("btn-validate-review");

inputNom.addEventListener("keyup", validateForm);
inputPrenom.addEventListener("keyup", validateForm);
selectMenu.addEventListener("change", validateForm);
textareaReview.addEventListener("keyup", validateForm);


// Notation par étoiles
document.querySelectorAll('.star-rating:not(.readonly) label').forEach(star => {
    star.addEventListener('click', function() {
        this.style.transform = 'scale(1.2)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 200);
    });
});
document.querySelectorAll('.star-rating input').forEach(input => {
    input.addEventListener('change', validateForm);
});

function validateForm() {

    const nomOk = validateRequired(inputNom);
    const prenomOk = validateRequired(inputPrenom);
    const menuOk = validateSelected(selectMenu);
    const reviewOk = validateRequired(textareaReview);
    const ratingOk = validateRating();

    if (nomOk && prenomOk && menuOk && reviewOk && ratingOk) {
        btnValidation.disabled = false;
    } else {
        btnValidation.disabled = true;
    }
}

function validateRequired(input){
    if(input.value != ""){
        input.classList.add("is-valid");
        input.classList.remove("is-invalid");
        return true;
    }else{
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
        return false;
    }
}
function validateSelected(select){
    if(select.value != ""){
        select.classList.add("is-valid");
        select.classList.remove("is-invalid");
        return true;
    }else{
        select.classList.add("is-invalid");
        select.classList.remove("is-valid");
        return false;
    }
}
function validateRating() {
    const checked = document.querySelector('input[name="rating"]:checked');
    const feedback = starRating.querySelector(".invalid-feedback");

    if (checked) {
        starRating.classList.remove("is-invalid");
        feedback.style.display = "none";
        return true;
    } else {
        starRating.classList.add("is-invalid");
        feedback.style.display = "block";
        return false;
    }
}
