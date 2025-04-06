/**
 * Main JavaScript for Collibration App
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add animation classes to elements
    animateElements();
    
    // Handle form submissions via AJAX
    setupFormHandlers();
    
    // Setup dynamic content loading
    setupDynamicContent();
    
    // Initialize Bootstrap components
    initializeBootstrapComponents();
    
    // Setup smooth scrolling
    setupSmoothScrolling();
    
    // Handle resource rating system
    setupRatingSystem();
    
    // Initialize animate on scroll
    initAnimateOnScroll();
});

/**
 * Add animation classes to elements on page load
 */
function animateElements() {
    // Add animation to page headers
    document.querySelectorAll('.page-header').forEach((el, index) => {
        el.classList.add('animate__animated', 'animate__fadeIn');
        el.style.animationDelay = `${0.1 * index}s`;
    });
    
    // Add animation to cards
    document.querySelectorAll('.card').forEach((el, index) => {
        el.classList.add('animate-on-scroll');
        el.dataset.animation = 'animate__fadeInUp';
    });
    
    // Add animation to buttons
    document.querySelectorAll('.btn-primary, .btn-success').forEach(el => {
        el.addEventListener('mouseenter', function() {
            this.classList.add('animate__animated', 'animate__pulse', 'animate__faster');
        });
        
        el.addEventListener('mouseleave', function() {
            this.classList.remove('animate__animated', 'animate__pulse', 'animate__faster');
        });
    });
    
    // Add animation to form submissions
    document.querySelectorAll('form button[type="submit"]').forEach(el => {
        el.addEventListener('click', function() {
            // Only add animation if form is valid
            if (this.closest('form').checkValidity()) {
                this.classList.add('animate__animated', 'animate__fadeOutUp', 'animate__faster');
                setTimeout(() => {
                    this.classList.remove('animate__animated', 'animate__fadeOutUp', 'animate__faster');
                }, 700);
            }
        });
    });
}

/**
 * Initialize Animate on Scroll functionality
 */
function initAnimateOnScroll() {
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if(elementPosition < screenPosition) {
                element.classList.add('animate__animated', element.dataset.animation || 'animate__fadeIn');
                element.classList.remove('animate-on-scroll');
            }
        });
    };
    
    // Run on load
    animateOnScroll();
    
    // Run on scroll
    window.addEventListener('scroll', animateOnScroll);
}

/**
 * Setup AJAX form submissions with improved feedback
 */
function setupFormHandlers() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Add shake animation to invalid fields
                form.querySelectorAll(':invalid').forEach(input => {
                    input.classList.add('animate__animated', 'animate__shakeX');
                    setTimeout(() => {
                        input.classList.remove('animate__animated', 'animate__shakeX');
                    }, 1000);
                });
                
                // Show error message
                showAlert('danger', 'Please correct the errors in the form.');
            }
            
            form.classList.add('was-validated');
        }, false);
    });

    // Login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) return;
            
            e.preventDefault();
            
            const formData = new FormData(loginForm);
            const loginBtn = loginForm.querySelector('button[type="submit"]');
            
            // Show loading state
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Logging in...';
            loginBtn.disabled = true;
            
            fetch('?route=api/auth/login', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message, true);
                    
                    // Add success animation to form
                    loginForm.classList.add('animate__animated', 'animate__fadeOutUp');
                    
                    // Redirect to home page after successful login
                    setTimeout(() => {
                        window.location.href = '?route=home';
                    }, 1000);
                } else {
                    // Reset button
                    loginBtn.innerHTML = 'Login';
                    loginBtn.disabled = false;
                    
                    // Show error with shake animation
                    const alertEl = showAlert('danger', data.message);
                    alertEl.classList.add('animate__animated', 'animate__shakeX');
                }
            })
            .catch(error => {
                // Reset button
                loginBtn.innerHTML = 'Login';
                loginBtn.disabled = false;
                
                showAlert('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
    
    // Registration form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) return;
            
            e.preventDefault();
            
            const formData = new FormData(registerForm);
            const registerBtn = registerForm.querySelector('button[type="submit"]');
            
            // Show loading state
            registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Creating account...';
            registerBtn.disabled = true;
            
            fetch('?route=api/auth/register', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', data.message, true);
                    
                    // Add success animation to form
                    registerForm.classList.add('animate__animated', 'animate__fadeOutUp');
                    
                    // Redirect to login page after successful registration
                    setTimeout(() => {
                        window.location.href = '?route=login';
                    }, 1500);
                } else {
                    // Reset button
                    registerBtn.innerHTML = 'Register';
                    registerBtn.disabled = false;
                    
                    // Show error with shake animation
                    const alertEl = showAlert('danger', data.message);
                    alertEl.classList.add('animate__animated', 'animate__shakeX');
                }
            })
            .catch(error => {
                // Reset button
                registerBtn.innerHTML = 'Register';
                registerBtn.disabled = false;
                
                showAlert('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
    
    // Profile update form with preview image
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        const imageInput = document.getElementById('profile-image-input');
        const imagePreview = document.getElementById('profile-image-preview');
        
        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.classList.add('animate__animated', 'animate__fadeIn');
                    };
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
        
        profileForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) return;
            
            e.preventDefault();
            
            const formData = new FormData(profileForm);
            const updateBtn = profileForm.querySelector('button[type="submit"]');
            
            // Show loading state
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Updating...';
            updateBtn.disabled = true;
            
            fetch('?route=api/profile/update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                updateBtn.innerHTML = 'Save Changes';
                updateBtn.disabled = false;
                
                if (data.status === 'success') {
                    const alertEl = showAlert('success', data.message);
                    alertEl.classList.add('animate__animated', 'animate__fadeIn');
                } else {
                    const alertEl = showAlert('danger', data.message);
                    alertEl.classList.add('animate__animated', 'animate__shakeX');
                }
            })
            .catch(error => {
                // Reset button
                updateBtn.innerHTML = 'Save Changes';
                updateBtn.disabled = false;
                
                showAlert('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
}

/**
 * Setup smooth scrolling for anchor links
 */
function setupSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]:not([data-bs-toggle])').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href !== '#') {
                e.preventDefault();
                
                const targetEl = document.querySelector(href);
                if (targetEl) {
                    targetEl.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}

/**
 * Setup dynamic content loading with loading indicators
 */
function setupDynamicContent() {
    // Resource filtering with animation
    const resourceFilters = document.getElementById('resource-filters');
    if (resourceFilters) {
        const categorySelect = document.getElementById('category-filter');
        const searchInput = document.getElementById('search-filter');
        const filterBtn = document.getElementById('apply-filters');
        const resourceList = document.querySelector('.resource-list');
        
        filterBtn.addEventListener('click', function() {
            const category = categorySelect ? categorySelect.value : '';
            const search = searchInput ? searchInput.value : '';
            
            // Add loading animation
            if (resourceList) {
                resourceList.classList.add('animate__animated', 'animate__fadeOut', 'animate__faster');
                
                setTimeout(() => {
                    let url = '?route=resources';
                    if (category) url += '&category=' + encodeURIComponent(category);
                    if (search) url += '&search=' + encodeURIComponent(search);
                    
                    window.location.href = url;
                }, 300);
            } else {
                let url = '?route=resources';
                if (category) url += '&category=' + encodeURIComponent(category);
                if (search) url += '&search=' + encodeURIComponent(search);
                
                window.location.href = url;
            }
        });
    }
    
    // Comment submission with real-time updates
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            if (!this.checkValidity()) return;
            
            e.preventDefault();
            
            const discussionId = this.getAttribute('data-discussion-id');
            const commentContent = document.getElementById('comment-content');
            const submitBtn = commentForm.querySelector('button[type="submit"]');
            
            if (!commentContent.value.trim()) {
                commentContent.classList.add('is-invalid', 'animate__animated', 'animate__shakeX');
                setTimeout(() => {
                    commentContent.classList.remove('animate__animated', 'animate__shakeX');
                }, 1000);
                return;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Posting...';
            submitBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('content', commentContent.value);
            
            fetch(`?route=api/discussions/${discussionId}/comments`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = 'Post Comment';
                submitBtn.disabled = false;
                
                if (data.status === 'success') {
                    // Clear the form with animation
                    commentContent.classList.add('animate__animated', 'animate__fadeOutDown', 'animate__faster');
                    setTimeout(() => {
                        commentContent.value = '';
                        commentContent.classList.remove('animate__animated', 'animate__fadeOutDown', 'animate__faster');
                        commentContent.classList.add('animate__animated', 'animate__fadeIn');
                        setTimeout(() => {
                            commentContent.classList.remove('animate__animated', 'animate__fadeIn');
                        }, 500);
                    }, 300);
                    
                    // Reload the comments section
                    loadComments(discussionId);
                    
                    showAlert('success', 'Comment added successfully');
                } else {
                    const alertEl = showAlert('danger', data.message || data.error);
                    alertEl.classList.add('animate__animated', 'animate__shakeX');
                }
            })
            .catch(error => {
                // Reset button
                submitBtn.innerHTML = 'Post Comment';
                submitBtn.disabled = false;
                
                showAlert('danger', 'An error occurred. Please try again.');
                console.error('Error:', error);
            });
        });
    }
}

/**
 * Setup rating system for resources
 */
function setupRatingSystem() {
    const ratingForms = document.querySelectorAll('.rating-form');
    
    ratingForms.forEach(form => {
        const stars = form.querySelectorAll('.rating-star');
        const ratingInput = form.querySelector('input[name="rating"]');
        const resourceId = form.getAttribute('data-resource-id');
        
        // Set up star hover effects
        stars.forEach((star, index) => {
            const starValue = index + 1;
            
            // Hover effect
            star.addEventListener('mouseenter', () => {
                // Fill stars up to current
                for (let i = 0; i < stars.length; i++) {
                    if (i < starValue) {
                        stars[i].classList.remove('far');
                        stars[i].classList.add('fas');
                    } else {
                        stars[i].classList.remove('fas');
                        stars[i].classList.add('far');
                    }
                }
            });
            
            // Click to set rating
            star.addEventListener('click', () => {
                ratingInput.value = starValue;
                
                // Submit the rating automatically
                submitRating(form, resourceId, starValue);
            });
        });
        
        // Reset stars on form mouseout
        form.addEventListener('mouseleave', () => {
            const currentRating = parseInt(ratingInput.value) || 0;
            
            stars.forEach((star, index) => {
                if (index < currentRating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
        });
    });
}

/**
 * Submit a resource rating
 */
function submitRating(form, resourceId, rating) {
    const formData = new FormData();
    formData.append('resource_id', resourceId);
    formData.append('rating', rating);
    
    fetch('?route=api/resources/rate', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update the average rating display
            const avgRatingEl = document.getElementById(`avg-rating-${resourceId}`);
            const ratingCountEl = document.getElementById(`rating-count-${resourceId}`);
            
            if (avgRatingEl) {
                avgRatingEl.textContent = data.avg_rating;
                avgRatingEl.classList.add('animate__animated', 'animate__heartBeat');
                setTimeout(() => {
                    avgRatingEl.classList.remove('animate__animated', 'animate__heartBeat');
                }, 1000);
            }
            
            if (ratingCountEl && data.rating_count) {
                ratingCountEl.textContent = `(${data.rating_count} ${data.rating_count === 1 ? 'rating' : 'ratings'})`;
                ratingCountEl.classList.add('animate__animated', 'animate__fadeIn');
                setTimeout(() => {
                    ratingCountEl.classList.remove('animate__animated', 'animate__fadeIn');
                }, 1000);
            }
            
            // Show success message
            showAlert('success', 'Thank you for your rating!');
        } else {
            showAlert('danger', data.message || 'Error submitting rating');
        }
    })
    .catch(error => {
        showAlert('danger', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

/**
 * Load comments for a discussion
 */
function loadComments(discussionId) {
    const commentsContainer = document.getElementById('comments-container');
    if (!commentsContainer) return;
    
    // Show loading indicator
    commentsContainer.innerHTML = '<div class="text-center my-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    
    fetch(`?route=api/discussions/${discussionId}/comments`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.comments) {
                // Fade out the current content
                commentsContainer.classList.add('animate__animated', 'animate__fadeOut', 'animate__faster');
                
                setTimeout(() => {
                    // Create HTML for comments
                    let commentsHtml = '';
                    
                    if (data.comments.length === 0) {
                        commentsHtml = '<div class="alert alert-info">No comments yet. Be the first to comment!</div>';
                    } else {
                        data.comments.forEach((comment, index) => {
                            commentsHtml += `
                                <div class="comment-box animate-on-scroll" data-animation="animate__fadeInUp" style="animation-delay: ${index * 0.1}s">
                                    <div class="comment-header">
                                        <div class="comment-author">
                                            <strong>${comment.username}</strong> · ${formatDate(comment.created_at)}
                                        </div>
                                    </div>
                                    <div class="comment-text">
                                        ${comment.content}
                                    </div>
                                </div>
                            `;
                        });
                    }
                    
                    // Update the container
                    commentsContainer.innerHTML = commentsHtml;
                    commentsContainer.classList.remove('animate__animated', 'animate__fadeOut', 'animate__faster');
                    commentsContainer.classList.add('animate__animated', 'animate__fadeIn');
                    
                    // Initialize the animate on scroll
                    initAnimateOnScroll();
                    
                    setTimeout(() => {
                        commentsContainer.classList.remove('animate__animated', 'animate__fadeIn');
                    }, 500);
                }, 300);
            } else {
                commentsContainer.innerHTML = '<div class="alert alert-danger">Error loading comments</div>';
            }
        })
        .catch(error => {
            commentsContainer.innerHTML = '<div class="alert alert-danger">Error loading comments</div>';
            console.error('Error:', error);
        });
}

/**
 * Initialize Bootstrap components
 */
function initializeBootstrapComponents() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize toasts
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });
}

/**
 * Show an alert message with animation
 */
function showAlert(type, message, autoHide = true) {
    // Create alert element
    const alertEl = document.createElement('div');
    alertEl.className = `alert alert-${type} alert-dismissible fade show animate__animated animate__fadeIn`;
    alertEl.role = 'alert';
    
    alertEl.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Find alert container or create one
    let alertContainer = document.getElementById('alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alert-container';
        alertContainer.className = 'position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1050';
        document.body.appendChild(alertContainer);
    }
    
    // Add to container
    alertContainer.appendChild(alertEl);
    
    // Auto-dismiss after 5 seconds
    if (autoHide) {
        setTimeout(() => {
            alertEl.classList.remove('animate__fadeIn');
            alertEl.classList.add('animate__fadeOut');
            
            setTimeout(() => {
                alertEl.remove();
                
                // Remove container if empty
                if (alertContainer.children.length === 0) {
                    alertContainer.remove();
                }
            }, 500);
        }, 5000);
    }
    
    return alertEl;
}

/**
 * Format date to a readable string
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }
    
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    } else {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString(undefined, options);
    }
}
