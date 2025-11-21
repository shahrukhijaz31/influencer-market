/**
 * Casters.fi - Main JavaScript
 * Handles navigation, animations, and interactive elements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    const navActions = document.querySelector('.nav-actions');

    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            navActions.classList.toggle('active');

            // Toggle icon
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-bars')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const headerOffset = 80;
                const elementPosition = target.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                if (navLinks && navLinks.classList.contains('active')) {
                    navLinks.classList.remove('active');
                    navActions.classList.remove('active');
                }
            }
        });
    });

    // Animate elements on scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.step-card, .category-item, .pricing-card, .split-content');

        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;

            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('animate-fade-in-up');
            }
        });
    };

    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on load

    // Form validation helpers
    window.validateEmail = function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    };

    window.validatePhone = function(phone) {
        const re = /^[\d\s\-\+\(\)]{7,}$/;
        return re.test(phone);
    };

    window.validatePassword = function(password) {
        return password.length >= 8;
    };

    // Password visibility toggle
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            // Find the input - either previous sibling or within same parent wrapper
            let input = this.previousElementSibling;
            if (!input || input.tagName !== 'INPUT') {
                input = this.parentElement.querySelector('input[type="password"], input[type="text"]');
            }
            const icon = this.querySelector('i');

            if (input && input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else if (input) {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Multi-step form handling
    window.initMultiStepForm = function(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        const steps = form.querySelectorAll('.form-step');
        const progressSteps = form.querySelectorAll('.progress-step');
        let currentStep = 0;

        const showStep = function(stepIndex) {
            steps.forEach((step, index) => {
                step.classList.toggle('active', index === stepIndex);
            });

            progressSteps.forEach((step, index) => {
                step.classList.toggle('active', index <= stepIndex);
                step.classList.toggle('completed', index < stepIndex);
            });

            currentStep = stepIndex;
        };

        form.querySelectorAll('.next-step').forEach(btn => {
            btn.addEventListener('click', function() {
                if (validateStep(currentStep)) {
                    if (currentStep < steps.length - 1) {
                        showStep(currentStep + 1);
                    }
                }
            });
        });

        form.querySelectorAll('.prev-step').forEach(btn => {
            btn.addEventListener('click', function() {
                if (currentStep > 0) {
                    showStep(currentStep - 1);
                }
            });
        });

        const validateStep = function(stepIndex) {
            const step = steps[stepIndex];
            const requiredFields = step.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            return isValid;
        };

        showStep(0);
    };

    // Category selection (for registration)
    window.initCategorySelection = function(maxCategories = 3) {
        const categoryItems = document.querySelectorAll('.selectable-category');
        const selectedInput = document.getElementById('selected-categories');
        let selected = [];

        categoryItems.forEach(item => {
            item.addEventListener('click', function() {
                const value = this.dataset.value;

                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selected = selected.filter(v => v !== value);
                } else {
                    if (selected.length < maxCategories) {
                        this.classList.add('selected');
                        selected.push(value);
                    } else {
                        alert(`You can select up to ${maxCategories} categories`);
                    }
                }

                if (selectedInput) {
                    selectedInput.value = selected.join(',');
                }
            });
        });
    };

    // Star rating system
    window.initStarRating = function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('input[type="hidden"]');

        stars.forEach((star, index) => {
            star.addEventListener('click', function() {
                const rating = index + 1;
                input.value = rating;

                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseenter', function() {
                const rating = index + 1;
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });

            star.addEventListener('mouseleave', function() {
                stars.forEach(s => s.classList.remove('hover'));
            });
        });
    };

    // File upload preview
    window.initFileUpload = function(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        if (!input || !preview) return;

        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    };

    // Dropdown menus
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('show');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    });

    // Toast notifications
    window.showToast = function(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 100);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // Modal handling
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    };

    // Close modal on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // Follower count formatter
    window.formatFollowerCount = function(count) {
        if (count >= 1000000) {
            return (count / 1000000).toFixed(1) + 'M';
        } else if (count >= 1000) {
            return (count / 1000).toFixed(1) + 'K';
        }
        return count.toString();
    };

    // Search functionality
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Trigger search
                const event = new CustomEvent('search', { detail: this.value });
                document.dispatchEvent(event);
            }, 300);
        });
    }

    // Filter handling
    document.querySelectorAll('.filter-option').forEach(option => {
        option.addEventListener('click', function() {
            const filterGroup = this.closest('.filter-group');
            const isMultiple = filterGroup.dataset.multiple === 'true';

            if (!isMultiple) {
                filterGroup.querySelectorAll('.filter-option').forEach(opt => {
                    opt.classList.remove('active');
                });
            }

            this.classList.toggle('active');

            // Trigger filter change
            const event = new CustomEvent('filterChange', {
                detail: getActiveFilters()
            });
            document.dispatchEvent(event);
        });
    });

    function getActiveFilters() {
        const filters = {};
        document.querySelectorAll('.filter-group').forEach(group => {
            const name = group.dataset.filter;
            const active = group.querySelectorAll('.filter-option.active');
            filters[name] = Array.from(active).map(opt => opt.dataset.value);
        });
        return filters;
    }

    // Infinite scroll (for campaign listings)
    window.initInfiniteScroll = function(containerId, loadMore) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let loading = false;
        let page = 1;

        window.addEventListener('scroll', function() {
            if (loading) return;

            const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

            if (scrollTop + clientHeight >= scrollHeight - 100) {
                loading = true;
                page++;

                loadMore(page).then(() => {
                    loading = false;
                }).catch(() => {
                    loading = false;
                });
            }
        });
    };

    // Copy to clipboard
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        }).catch(() => {
            showToast('Failed to copy', 'error');
        });
    };

    // Date formatting
    window.formatDate = function(date, format = 'short') {
        const d = new Date(date);
        const options = format === 'short'
            ? { day: 'numeric', month: 'short', year: 'numeric' }
            : { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' };

        return d.toLocaleDateString('en-US', options);
    };

    console.log('Casters.fi initialized');
});

// Add mobile menu styles dynamically
const mobileStyles = document.createElement('style');
mobileStyles.textContent = `
    @media (max-width: 768px) {
        .nav-links.active,
        .nav-actions.active {
            display: flex !important;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .nav-links.active {
            border-bottom: 1px solid #e5e7eb;
        }

        .nav-links.active a {
            padding: 0.75rem 0;
        }

        .nav-actions.active {
            padding-top: 0;
        }

        .nav-actions.active .btn {
            width: 100%;
            justify-content: center;
        }
    }

    .navbar.scrolled {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s ease;
        z-index: 9999;
    }

    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .toast-success { border-left: 4px solid #10b981; }
    .toast-error { border-left: 4px solid #ef4444; }
    .toast-info { border-left: 4px solid #e879f9; }

    .toast-success i { color: #10b981; }
    .toast-error i { color: #ef4444; }
    .toast-info i { color: #e879f9; }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 10000;
    }

    .modal.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .form-input.error,
    .form-select.error {
        border-color: #ef4444;
    }

    .selectable-category {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .selectable-category.selected {
        background: linear-gradient(135deg, #e879f9 0%, #67e8f9 100%);
        color: white;
        border-color: #e879f9;
    }

    .selectable-category.selected i {
        color: white;
    }

    .star {
        cursor: pointer;
        color: #e5e7eb;
        font-size: 1.5rem;
        transition: color 0.2s ease;
    }

    .star.active,
    .star.hover {
        color: #f59e0b;
    }
`;
document.head.appendChild(mobileStyles);
