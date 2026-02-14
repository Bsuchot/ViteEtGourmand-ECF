const inputNom = document.getElementById("nameReviewInput");
const inputPrenom = document.getElementById("firstnameReviwInput");
const selectMenu = document.getElementById("menuReviewFormSelect");
const textareaReview = document.getElementById("reviewTextarea");
const btnValidation = document.getElementById("btn-validate-review");

inputNom.addEventListener("keyup", validateForm);
inputPrenom.addEventListener("keyup", validateForm);
selectMenu.addEventListener("change", validateForm);
textareaReview.addEventListener("keyup", validateForm);


// Note par étoiles
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
    const menuOk = selectMenu.value !== "1";
    const reviewOk = validateRequired(textareaReview);
    const ratingOk = isRatingSelected();

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

function isRatingSelected() {
    return document.querySelector('.star-rating input:checked') !== null;
}