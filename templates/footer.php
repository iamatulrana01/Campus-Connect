    </div><!-- End of container -->
    </div><!-- End of content container -->
    
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0 animate__animated animate__fadeIn">
                    <h4 class="mb-4"><i class="fas fa-graduation-cap me-2"></i>Campus Connect</h4>
                    <p class="text-muted">A streamlined academic resource platform designed to help students collaborate, share resources, and enhance their learning experience.</p>
                    <div class="social-links mt-4">
                        <a href="#" class="me-3 text-decoration-none text-white fs-5"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="me-3 text-decoration-none text-white fs-5"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3 text-decoration-none text-white fs-5"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-3 text-decoration-none text-white fs-5"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0 animate__animated animate__fadeIn" style="animation-delay: 0.1s;">
                    <h6 class="text-uppercase fw-bold mb-4">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Home</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=resources" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Resources</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=discussions" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Discussions</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=study-groups" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Study Groups</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0 animate__animated animate__fadeIn" style="animation-delay: 0.2s;">
                    <h6 class="text-uppercase fw-bold mb-4">Features</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=resources" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Resource Library</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=discussions" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Discussion Forums</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=study-groups" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Collaboration Tools</a></li>
                        <li class="mb-2"><a href="<?php echo APP_URL; ?>?route=messages" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Messaging</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-4 animate__animated animate__fadeIn" style="animation-delay: 0.3s;">
                    <h6 class="text-uppercase fw-bold mb-4">Support</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Help Center</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Contact Us</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none text-muted footer-link"><i class="fas fa-chevron-right me-1 small"></i>Terms of Service</a></li>
                    </ul>
                    <div class="mt-4">
                        <a href="#" class="btn btn-outline-light btn-sm">Get the App <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-md-0">&copy; <?php echo date('Y'); ?> Campus Connect. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted mb-0">Version <?php echo APP_VERSION; ?> | <a href="#" class="text-decoration-none text-muted">Site Map</a></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    
    <script>
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    
    // Add animation classes to elements when they come into view
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if(elementPosition < screenPosition) {
                element.classList.add('animate__animated', element.dataset.animation || 'animate__fadeIn');
            }
        });
    };
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', () => {
        animateOnScroll();
        
        // Run on scroll
        window.addEventListener('scroll', animateOnScroll);
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
    
    <style>
    .footer-link {
        transition: all 0.3s ease;
        position: relative;
        display: inline-block;
    }
    
    .footer-link:hover {
        color: #fff !important;
        transform: translateX(5px);
    }
    
    .footer-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 1px;
        background-color: #fff;
        bottom: 0;
        left: 0;
        transition: width 0.3s ease;
    }
    
    .footer-link:hover::after {
        width: 100%;
    }
    
    /* Back to top button */
    .back-to-top {
        position: fixed;
        bottom: 25px;
        right: 25px;
        display: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        text-align: center;
        line-height: 40px;
        z-index: 99;
        transition: all 0.3s ease;
    }
    
    .back-to-top:hover {
        background: var(--primary-color);
        opacity: 0.8;
        transform: translateY(-3px);
    }
    </style>
    
    <!-- Back to top button -->
    <a href="#" class="back-to-top animate__animated animate__fadeIn" style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </a>
    
    <script>
    // Back to top button
    window.addEventListener('scroll', function() {
        const backToTop = document.querySelector('.back-to-top');
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'block';
        } else {
            backToTop.style.display = 'none';
        }
    });
    </script>
</body>
</html>
