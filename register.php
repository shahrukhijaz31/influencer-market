<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Casters.fi</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fdf2f8 0%, #f0f9ff 100%);
            padding: var(--spacing-lg);
        }

        .auth-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            max-width: 1100px;
            width: 100%;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .auth-sidebar {
            position: relative;
            padding: var(--spacing-2xl);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            color: white;
            overflow: hidden;
        }

        .auth-sidebar-image {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.pexels.com/photos/3373739/pexels-photo-3373739.jpeg?auto=compress&cs=tinysrgb&w=800');
            background-size: cover;
            background-position: center;
        }

        .auth-sidebar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.7) 100%);
        }

        .auth-sidebar-content {
            position: relative;
            z-index: 1;
        }

        .auth-sidebar h2 {
            color: white;
            margin-bottom: var(--spacing-sm);
            font-size: 1.75rem;
            font-weight: 700;
        }

        .auth-sidebar p {
            opacity: 0.95;
            margin-bottom: var(--spacing-lg);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .auth-features {
            list-style: none;
        }

        .auth-features li {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
            font-size: 13px;
        }

        .auth-features i {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }

        .auth-form-container {
            padding: var(--spacing-2xl);
            max-height: 90vh;
            overflow-y: auto;
        }

        .auth-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .auth-logo {
            height: 40px;
            margin-bottom: var(--spacing-lg);
        }

        .auth-header h1 {
            font-size: var(--font-size-xl);
            margin-bottom: var(--spacing-xs);
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
        }

        .auth-form .form-group {
            margin-bottom: var(--spacing-md);
        }

        .auth-form .form-label {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .auth-form .form-input,
        .auth-form .form-select {
            padding: 0.6rem 0.75rem;
            font-size: 13px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            z-index: 10;
            padding: 5px;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .auth-footer {
            text-align: center;
            margin-top: var(--spacing-lg);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--primary-dark);
            font-weight: 600;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin: var(--spacing-lg) 0 var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-color);
        }


        /* Searchable Select */
        .searchable-select {
            position: relative;
        }

        .searchable-select-input {
            width: 100%;
            padding: 0.6rem 0.75rem;
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: white;
            cursor: pointer;
        }

        .searchable-select-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .searchable-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
        }

        .searchable-select-dropdown.open {
            display: block;
        }

        .searchable-select-option {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 12px;
        }

        .searchable-select-option:hover {
            background: var(--bg-secondary);
        }

        .searchable-select-option.selected {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Error state for form fields */
        .form-input.error,
        .form-select.error,
        .searchable-select-input.error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        /* Step indicator */
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }

        .step-indicator .step {
            font-size: 11px;
            padding: 6px 12px;
            border-radius: 20px;
            background: var(--bg-secondary);
            color: var(--text-secondary);
        }

        .step-indicator .step.active {
            background: var(--primary-gradient);
            color: white;
        }

        /* Step 2 note */
        .step2-note {
            background: linear-gradient(135deg, rgba(232, 121, 249, 0.1) 0%, rgba(103, 232, 249, 0.1) 100%);
            border: 1px solid rgba(232, 121, 249, 0.2);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-lg);
            font-size: 12px;
            color: var(--text-secondary);
        }

        .step2-note strong {
            color: var(--text-primary);
        }

        .step2-note ul {
            margin: var(--spacing-sm) 0 0 var(--spacing-md);
            padding: 0;
        }

        .step2-note li {
            margin-bottom: 4px;
        }

        @media (max-width: 768px) {
            .auth-container {
                grid-template-columns: 1fr;
            }

            .auth-sidebar {
                display: none;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .category-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-sidebar">
                <div class="auth-sidebar-image"></div>
                <div class="auth-sidebar-overlay"></div>
                <div class="auth-sidebar-content">
                    <h2>Join Casters.fi</h2>
                    <p>Create your influencer profile and start collaborating with amazing brands in Finland.</p>
                    <ul class="auth-features">
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Discover brand campaigns</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Get matched with relevant brands</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Build your portfolio</span>
                        </li>
                        <li>
                            <i class="fas fa-check"></i>
                            <span>Track your performance</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="auth-form-container">
                <div class="auth-header">
                    <a href="index.html">
                        <img src="assets/images/logo.png" alt="Casters.fi" class="auth-logo">
                    </a>
                    <h1>Create Your Account</h1>
                    <p>Fill in your details to get started</p>
                    <div class="step-indicator">
                        <span class="step active">Step 1: Basic Info</span>
                        <span class="step">Step 2: Profile Details</span>
                    </div>
                </div>

                <form class="auth-form" id="registerForm">
                    <!-- Basic Information -->
                    <div class="section-title">Basic Information</div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name *</label>
                            <input type="text" class="form-input" id="first_name" name="first_name" required placeholder="First name">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name *</label>
                            <input type="text" class="form-input" id="last_name" name="last_name" required placeholder="Last name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" class="form-input" id="email" name="email" required placeholder="your@email.com">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password *</label>
                            <div class="password-input-wrapper">
                                <input type="password" class="form-input" id="password" name="password" required placeholder="Min 8 characters" minlength="8">
                                <button type="button" class="password-toggle" id="togglePassword1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password *</label>
                            <div class="password-input-wrapper">
                                <input type="password" class="form-input" id="confirm_password" name="confirm_password" required placeholder="Confirm password" minlength="8">
                                <button type="button" class="password-toggle" id="togglePassword2">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="date_of_birth">Date of Birth *</label>
                            <input type="date" class="form-input" id="date_of_birth" name="date_of_birth" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="country">Country *</label>
                            <div class="searchable-select" id="countrySelect">
                                <input type="text" class="searchable-select-input" id="countryInput" placeholder="Search country..." autocomplete="off">
                                <input type="hidden" name="country" id="countryValue" required>
                                <div class="searchable-select-dropdown" id="countryDropdown"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="creator_type">Creator Type *</label>
                            <select class="form-select" id="creator_type" name="creator_type" required>
                                <option value="">Select type</option>
                                <option value="influencer">Influencer</option>
                                <option value="content_creator">Content Creator</option>
                                <option value="both">Both</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="referral_code">Referral Code</label>
                            <input type="text" class="form-input" id="referral_code" name="referral_code" placeholder="Optional">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="hear_about_us">How did you hear about us? *</label>
                        <select class="form-select" id="hear_about_us" name="hear_about_us" required>
                            <option value="">Select option</option>
                            <option value="social_media">Social Media</option>
                            <option value="friend">Friend / Colleague</option>
                            <option value="google">Google Search</option>
                            <option value="blog">Blog / Article</option>
                            <option value="event">Event / Conference</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Terms -->
                    <div class="form-group" style="margin-top: var(--spacing-md);">
                        <label class="form-check">
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="terms.html" target="_blank">Terms of Service</a> and <a href="privacy.html" target="_blank">Privacy Policy</a> *</span>
                        </label>
                    </div>

                    <!-- Step 2 Note -->
                    <div class="step2-note">
                        <strong>What's next?</strong> After creating your account, you'll complete your profile with:
                        <ul>
                            <li>Social media accounts (Instagram, TikTok, YouTube, etc.)</li>
                            <li>Categories & interests</li>
                            <li>Pricing for different content types</li>
                            <li>Portfolio and bio</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.html">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Countries list
        const countries = [
            'Afghanistan', 'Albania', 'Algeria', 'Argentina', 'Australia', 'Austria', 'Bangladesh', 'Belgium', 'Brazil', 'Bulgaria',
            'Canada', 'Chile', 'China', 'Colombia', 'Croatia', 'Czech Republic', 'Denmark', 'Egypt', 'Estonia', 'Finland',
            'France', 'Germany', 'Greece', 'Hong Kong', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Ireland', 'Israel',
            'Italy', 'Japan', 'Kenya', 'Latvia', 'Lithuania', 'Luxembourg', 'Malaysia', 'Mexico', 'Morocco', 'Netherlands',
            'New Zealand', 'Nigeria', 'Norway', 'Pakistan', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Romania', 'Russia',
            'Saudi Arabia', 'Singapore', 'Slovakia', 'Slovenia', 'South Africa', 'South Korea', 'Spain', 'Sweden', 'Switzerland',
            'Taiwan', 'Thailand', 'Turkey', 'Ukraine', 'United Arab Emirates', 'United Kingdom', 'United States', 'Vietnam'
        ];

        // Searchable country dropdown
        const countryInput = document.getElementById('countryInput');
        const countryValue = document.getElementById('countryValue');
        const countryDropdown = document.getElementById('countryDropdown');

        function renderCountries(filter = '') {
            const filtered = countries.filter(c => c.toLowerCase().includes(filter.toLowerCase()));
            countryDropdown.innerHTML = filtered.map(country =>
                `<div class="searchable-select-option" data-value="${country}">${country}</div>`
            ).join('');

            countryDropdown.querySelectorAll('.searchable-select-option').forEach(option => {
                option.addEventListener('click', () => {
                    countryInput.value = option.dataset.value;
                    countryValue.value = option.dataset.value;
                    countryDropdown.classList.remove('open');
                });
            });
        }

        countryInput.addEventListener('focus', () => {
            renderCountries(countryInput.value);
            countryDropdown.classList.add('open');
        });

        countryInput.addEventListener('input', () => {
            renderCountries(countryInput.value);
            countryDropdown.classList.add('open');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#countrySelect')) {
                countryDropdown.classList.remove('open');
            }
        });

        // Form submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.form-input, .form-select').forEach(field => {
                field.classList.remove('error');
            });
            document.getElementById('countryInput').classList.remove('error');

            // Validate required fields
            const requiredFields = {
                'first_name': 'First Name',
                'last_name': 'Last Name',
                'email': 'Email',
                'password': 'Password',
                'confirm_password': 'Confirm Password',
                'date_of_birth': 'Date of Birth',
                'creator_type': 'Creator Type',
                'hear_about_us': 'How did you hear about us'
            };

            let hasError = false;
            let firstErrorField = null;

            // Check regular fields
            for (const [fieldId, fieldName] of Object.entries(requiredFields)) {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.classList.add('error');
                    if (!firstErrorField) {
                        firstErrorField = field;
                        showToast(`${fieldName} is required`, 'error');
                    }
                    hasError = true;
                }
            }

            // Check country (hidden field)
            const countryValue = document.getElementById('countryValue').value;
            if (!countryValue) {
                document.getElementById('countryInput').classList.add('error');
                if (!firstErrorField) {
                    firstErrorField = document.getElementById('countryInput');
                    showToast('Country is required', 'error');
                }
                hasError = true;
            }

            // Check terms
            const termsCheckbox = document.querySelector('input[name="terms"]');
            if (!termsCheckbox.checked) {
                if (!firstErrorField) {
                    showToast('You must agree to the Terms of Service', 'error');
                }
                hasError = true;
            }

            if (hasError) {
                if (firstErrorField) {
                    firstErrorField.focus();
                }
                return;
            }

            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                document.getElementById('password').classList.add('error');
                document.getElementById('confirm_password').classList.add('error');
                showToast('Passwords do not match', 'error');
                return;
            }

            if (password.length < 8) {
                document.getElementById('password').classList.add('error');
                showToast('Password must be at least 8 characters', 'error');
                return;
            }

            // Validate email format
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('email').classList.add('error');
                showToast('Please enter a valid email address', 'error');
                return;
            }

            showToast('Creating your account...', 'info');

            const formData = new FormData(this);

            fetch('api/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Account created successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || 'login.html';
                    }, 1000);
                } else {
                    // Highlight specific field if error mentions it
                    if (data.field) {
                        const errorField = document.getElementById(data.field);
                        if (errorField) {
                            errorField.classList.add('error');
                            errorField.focus();
                        }
                    }
                    showToast(data.error || 'Registration failed', 'error');
                }
            })
            .catch(error => {
                showToast('Connection error. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    });
    </script>
</body>
</html>
