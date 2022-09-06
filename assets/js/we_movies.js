const bootstrap = require('bootstrap');

document.addEventListener("DOMContentLoaded", function(event) {
    const movieCardButtons = document.querySelectorAll('.js-movie-card-action');

    const showModal = function (modalElementId) {
        const modalElement = document.getElementById(modalElementId);
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    const loadModal = function (movieId, modalElementId) {
        fetch(`/movies/${movieId}`).then((response) => {
            return response.text().then((htmlContent) => {
                document.getElementsByClassName('js-modal-container')[0].innerHTML += htmlContent;
                showModal(modalElementId);
            })
        }).catch(function (error) {
            console.log(error);
            throw error;
        })
    }

    const addMovieCardButtonsListener = function (movieCardButtons) {
        movieCardButtons.forEach(movieCardButton => {
            movieCardButton.addEventListener('click', (event) => {
                event.preventDefault();
                const movieId = movieCardButton.getAttribute('data-movie-id');
                const modalElementId = 'movieModal-' + movieId;

                if (!document.getElementById(modalElementId)) {
                    loadModal(movieId, modalElementId);
                } else {
                    showModal(modalElementId);
                }
            });
        });
    };

    addMovieCardButtonsListener(movieCardButtons);

    const debounce = (func, wait) => {
        let timeout;

        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };

            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    const searchInputElement = document.getElementsByClassName('js-autocomplete-search-input')[0];
    const autoCompleteListContainer = document.getElementsByClassName('js-autocomplete-search-list')[0];

    searchInputElement.addEventListener('input', debounce(function (event) {
        if (event.target.value === '') {
            autoCompleteListContainer.innerHTML = ''
            return;
        }

        fetch(`/search?` + new URLSearchParams({
            'q': event.target.value
        })).then(function (response) {
            return response.text().then(function (htmlContent) {
                autoCompleteListContainer.innerHTML = htmlContent;

                addMovieCardButtonsListener(document.querySelectorAll('.js-movie-search-results-item-action'));
            })
        }).catch(function (error) {
            console.log(error);
            throw error;
        })
    }, 200))

    searchInputElement.addEventListener('focusout', (event) => {
        setTimeout( function () {
            autoCompleteListContainer.innerHTML = ''
        }, 200);
    })
})