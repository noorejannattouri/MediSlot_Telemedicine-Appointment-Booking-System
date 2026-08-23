<?php
$page_title = "Welcome";
require_once 'header.php';
?>

<style>
    .hero-section {
        background: linear-gradient(rgba(13, 110, 253, 0.75), rgba(11, 94, 215, 0.85)),
                    url('https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        min-height: 85vh;
        display: flex;
        align-items: center;
        color: white;
        border-radius: 0 0 20px 20px;
        margin-top: -1.5rem;
    }
    .hero-title {
        font-size: 3.2rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 1.2rem;
    }
    .hero-subtitle {
        font-size: 1.25rem;
        opacity: 0.95;
        margin-bottom: 2rem;
    }
    .btn-hero {
        padding: 12px 32px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        margin: 0 8px 10px 0;
    }
</style>

<section class="hero-section">
    <div class="container">
        <div class="text-center text-md-start" style="max-width: 700px;">
            <h1 class="hero-title">
                Quality Healthcare <br> at Your Fingertips
            </h1>
            <p class="hero-subtitle">
                Book appointments with verified doctors, consult online from home, 
                and get AI-powered medicine assistance — all in one platform.
            </p>

            <div class="d-flex flex-wrap justify-content-center justify-content-md-start">
                <a href="signup.php" class="btn btn-light btn-hero">
                    <i class="bi bi-person-plus me-2"></i>Sign Up Free
                </a>
                <a href="login.php" class="btn btn-outline-light btn-hero">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </a>
                <a href="admin/login.php" class="btn btn-outline-light btn-hero">
                    <i class="bi bi-shield-lock me-2"></i>Admin
                </a>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Why Choose MediSlot?</h2>
            <p class="text-muted">Everything you need for modern healthcare</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-3 text-primary fs-1"><i class="bi bi-calendar2-check"></i></div>
                    <h5 class="fw-bold">Easy Appointment Booking</h5>
                    <p class="text-muted mb-0">Find available doctors and book slots in just a few clicks.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-3 text-primary fs-1"><i class="bi bi-camera-video"></i></div>
                    <h5 class="fw-bold">Online Consultation</h5>
                    <p class="text-muted mb-0">Consult with experienced doctors from the comfort of your home.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 text-center p-4">
                    <div class="mb-3 text-primary fs-1"><i class="bi bi-robot"></i></div>
                    <h5 class="fw-bold">AI Medicine Assistant</h5>
                    <p class="text-muted mb-0">Get instant information about medicines using AI.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'footer.php'; ?>