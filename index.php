<?php
require_once 'config.php';
require_once 'security.php';
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participer - Emploitic Connect 2026</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0a1929 0%, #1a2980 50%, #26d0ce 100%);
        background-attachment: fixed;
        color: #fff;
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* Animated Background Particles */
    .background-animation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        animation: float 20s infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0) translateX(0);
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            transform: translateY(-100vh) translateX(50px);
            opacity: 0;
        }
    }

    /* Glassmorphism Effect */
    .glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }

    /* Top Bar */
    .top-bar {
        background: rgba(10, 25, 41, 0.95);
        backdrop-filter: blur(10px);
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        z-index: 101;
    }

    .top-bar-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        max-width: 1400px;
        justify-content: space-evenly;
        align-content: flex-start;
    }

    .contact-info {
        display: flex;
        gap: 40px;
        flex-wrap: wrap;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.9);
    }

    .contact-item::before {
        content: '';
        width: 6px;
        height: 6px;
        margin-top: 2px;
        background: #26d0ce;
        border-radius: 50%;
        box-shadow: 0 0 10px #26d0ce;
    }

    /* Navigation */
    nav {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: sticky;
        top: 0;
        z-index: 100;
        transition: all 0.3s ease;
    }

    nav.scrolled {
        background: rgba(10, 25, 41, 0.98);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .nav-content {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        min-height: 80px;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logo img {
        height: 55px;
        filter: drop-shadow(0 4px 10px rgba(38, 208, 206, 0.3));
        transition: transform 0.3s ease;
    }

    .logo:hover img {
        transform: scale(1.05);
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 40px;
        align-items: center;
    }

    .nav-links a {
        text-decoration: none;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        font-size: 15px;
        transition: all 0.3s;
        position: relative;
    }

    .nav-links a:not(.btn-primary):hover {
        color: #26d0ce;
    }

    .nav-links a:not(.btn-primary)::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #26d0ce, #1a2980);
        transition: width 0.3s ease;
    }

    .nav-links a:not(.btn-primary):hover::after {
        width: 100%;
    }

    .btn-primary {
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        color: white;
        padding: 12px 30px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(38, 208, 206, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-primary:hover::before {
        width: 260px;
        height: 200px;
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(38, 208, 206, 0.5);
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
        display: none;
        flex-direction: column;
        gap: 6px;
        cursor: pointer;
        z-index: 102;
    }

    .menu-toggle span {
        width: 30px;
        height: 3px;
        background: white;
        transition: 0.3s;
        border-radius: 2px;
    }

    /* Hero Section */
    .hero {
        position: relative;
        padding: 120px 30px 80px;
        text-align: center;
        overflow: hidden;
    }

    .hero::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(38, 208, 206, 0.1) 0%, transparent 70%);
        animation: rotate 30s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .hero-content {
        max-width: 900px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
    }

    .hero h1 {
        font-size: 4.5em;
        margin-bottom: 25px;
        background: linear-gradient(135deg, #fff, #26d0ce);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: fadeInDown 1s ease;
        font-weight: 800;
        letter-spacing: -2px;
    }

    .hero .subtitle {
        font-size: 1.5em;
        margin-bottom: 40px;
        color: rgba(255, 255, 255, 0.9);
        animation: fadeInUp 1s ease 0.2s both;
        font-weight: 300;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 60px;
        margin-top: 60px;
        flex-wrap: wrap;
    }

    .stat-item {
        text-align: center;
        animation: fadeInUp 1s ease 0.4s both;
    }

    .stat-number {
        font-size: 3em;
        font-weight: 800;
        background: linear-gradient(135deg, #26d0ce, #fff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        font-size: 1.1em;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 10px;
    }

    /* Registration Section */
    .registration {
        padding: 100px 30px;
        position: relative;
    }

    .registration-content {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 80px;
        align-items: start;
    }

    .registration-info {
        animation: fadeInLeft 1s ease;
    }

    .registration-info h2 {
        font-size: 3.5em;
        margin-bottom: 40px;
        background: linear-gradient(135deg, #fff, #26d0ce);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
        line-height: 1.2;
    }

    .info-box {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 25px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .info-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(38, 208, 206, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .info-box:hover::before {
        left: 100%;
    }

    .info-box:hover {
        transform: translateX(10px);
        border-color: rgba(38, 208, 206, 0.3);
        box-shadow: 0 10px 40px rgba(38, 208, 206, 0.2);
    }

    .info-box h3 {
        color: #26d0ce;
        margin-bottom: 15px;
        font-size: 1.5em;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .info-box ul {
        padding-left: 20px;
        margin-top: 15px;
    }

    .info-box li {
        margin-bottom: 10px;
        color: rgba(255, 255, 255, 0.85);
        position: relative;
        padding-left: 10px;
    }

    .info-box li::before {
        bottom: 1px;
        content: '→';
        position: absolute;
        left: -7px;
        color: #26d0ce;
    }

    /* Registration Form */
    .registration-form {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        padding: 50px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        animation: fadeInRight 1s ease;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
    }

    .registration-form::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(38, 208, 206, 0.05) 0%, transparent 70%);
        animation: rotate 20s linear infinite reverse;
    }

    .registration-form h3 {
        font-size: 2.5em;
        margin-bottom: 40px;
        color: #fff;
        position: relative;
        z-index: 1;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
        z-index: 1;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.9);
        font-size: 15px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 16px 20px;
        background: rgba(255, 255, 255, 0.08);
        border: 2px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        font-size: 16px;
        color: white;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .form-group input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #26d0ce;
        background: rgba(255, 255, 255, 0.12);
        box-shadow: 0 0 20px rgba(38, 208, 206, 0.3);
    }

    .form-group select {
        cursor: pointer;
    }

    .form-group select option {
        background: #1a2980;
        color: white;
    }

    .btn-submit {
        width: 100%;
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        color: white;
        padding: 18px;
        border: none;
        border-radius: 15px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 30px rgba(38, 208, 206, 0.3);
        position: relative;
        overflow: hidden;
        margin-top: 10px;
    }

    .btn-submit::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-submit:hover::before {
        width: 400px;
        height: 400px;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(38, 208, 206, 0.5);
    }

    /* Sponsors Section */
    .sponsors {
        padding: 100px 30px;
        position: relative;
    }

    .sponsors h2 {
        font-size: 3em;
        text-align: center;
        margin-bottom: 70px;
        background: linear-gradient(135deg, #fff, #26d0ce);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 800;
    }

    .sponsors-grid {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 40px;
        align-items: center;
    }

    .sponsor-logo {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(20px);
        padding: 40px;
        border-radius: 20px;
        height: 140px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        overflow: hidden;
    }

    .sponsor-logo::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: radial-gradient(circle, rgba(38, 208, 206, 0.2), transparent);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.5s, height 0.5s;
    }

    .sponsor-logo:hover::before {
        width: 300px;
        height: 300px;
    }

    .sponsor-logo:hover {
        transform: translateY(-10px);
        border-color: rgba(38, 208, 206, 0.4);
        box-shadow: 0 15px 40px rgba(38, 208, 206, 0.3);
    }

    .sponsor-logo img {
        max-width: 100%;
        max-height: 80px;
        filter: brightness(0) invert(1);
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }

    .sponsor-logo:hover img {
        filter: brightness(1) invert(0);
        transform: scale(1.1);
    }

    /* Footer */
    footer {
        background: rgba(10, 25, 41, 0.95);
        backdrop-filter: blur(20px);
        padding: 80px 30px 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .footer-content {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 50px;
        margin-bottom: 40px;
    }

    .footer-section h3 {
        color: #26d0ce;
        margin-bottom: 25px;
        font-size: 1.4em;
    }

    .footer-section p {
        margin-bottom: 12px;
        color: rgba(255, 255, 255, 0.8);
    }

    .footer-section img {
        margin-bottom: 20px;
    }

    .social-links {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }

    .social-links a {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
        font-weight: bold;
    }

    .social-links a:hover {
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(38, 208, 206, 0.4);
    }

    .copyright {
        text-align: center;
        padding-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.6);
    }

    /* Success Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(10px);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(30px);
        padding: 50px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        text-align: center;
        max-width: 500px;
        animation: scaleIn 0.5s ease;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0.8);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .modal-content h3 {
        font-size: 2.5em;
        margin-bottom: 20px;
        color: #26d0ce;
    }

    .modal-content p {
        font-size: 1.2em;
        margin-bottom: 30px;
        color: rgba(255, 255, 255, 0.9);
    }

    .checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #26d0ce, #1a2980);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        animation: bounce 0.6s ease;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.2);
        }
    }

    /* Animations */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Responsive */
    @media (max-width: 968px) {
        .hero h1 {
            font-size: 3em;
        }

        .registration-content {
            grid-template-columns: 1fr;
            gap: 50px;
        }

        .sponsors-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        }

        .footer-content {
            grid-template-columns: 1fr;
        }

        .nav-links {
            display: none;
            position: absolute;
            top: 80px;
            left: 0;
            right: 0;
            background: rgba(10, 25, 41, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            padding: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-links.active {
            display: flex;
        }

        .menu-toggle {
            display: flex;
        }

        .hero-stats {
            gap: 30px;
        }

        .stat-number {
            font-size: 2.5em;
        }
    }

    @media (max-width: 640px) {
        .hero h1 {
            font-size: 2.2em;
        }

        .hero .subtitle {
            font-size: 1.1em;
        }

        .registration-info h2 {
            font-size: 2.5em;
        }

        .registration-form {
            padding: 30px;
        }

        .contact-info {
            gap: 15px;
            font-size: 12px;
        }

        .top-bar-content {
            justify-content: center;
        }
    }

    /* Modern Scroll Animations 2026 */
    .scroll-animate {
        opacity: 0;
        transform: translateY(60px) scale(0.95);
        transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        will-change: opacity, transform;
    }

    .scroll-animate.animate {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    /* Hero Section - Zoom In Effect */
    .scroll-animate.zoom-in {
        transform: scale(0.8) translateY(40px);
        opacity: 0;
    }

    .scroll-animate.zoom-in.animate {
        transform: scale(1) translateY(0);
        opacity: 1;
    }

    /* Fade In Up - Enhanced */
    .scroll-animate.fade-in-up {
        transform: translateY(80px) rotateX(10deg);
        opacity: 0;
    }

    .scroll-animate.fade-in-up.animate {
        transform: translateY(0) rotateX(0deg);
        opacity: 1;
    }

    /* Slide In Left - Modern */
    .scroll-animate.slide-in-left {
        transform: translateX(-100px) rotateY(-15deg);
        opacity: 0;
    }

    .scroll-animate.slide-in-left.animate {
        transform: translateX(0) rotateY(0deg);
        opacity: 1;
    }

    /* Slide In Right - Modern */
    .scroll-animate.slide-in-right {
        transform: translateX(100px) rotateY(15deg);
        opacity: 0;
    }

    .scroll-animate.slide-in-right.animate {
        transform: translateX(0) rotateY(0deg);
        opacity: 1;
    }

    /* Slide In Up - Enhanced */
    .scroll-animate.slide-in-up {
        transform: translateY(120px) scale(0.9);
        opacity: 0;
    }

    .scroll-animate.slide-in-up.animate {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    /* Stagger Animation for Children */
    .scroll-animate.stagger-children>* {
        opacity: 0;
    </style>
</head>

<body>
    <!-- Animated Background -->
    <div class="background-animation" id="particles"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact-info">
                <div class="contact-item">
                    N°1 Cité Yassmine Draria, 16000
                </div>
                <div class="contact-item">
                    Recruteur@emploitic.com
                </div>
                <div class="contact-item">
                    +213 560 90 61 16
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav id="mainNav">
        <div class="nav-content">
            <div class="logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2023/12/logo-connect.png"
                    alt="Emploitic Connect">
            </div>
            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home">Accueil</a></li>
                <li><a href="#prepare">Préparer sa visite</a></li>
                <li><a href="#sponsor">Devenez sponsor</a></li>
                <li><a href="https://emploitic.com/offres-d-emploi" target="_blank">Offres d'emploi</a></li>
                <li><a href="#registrationForm" class="btn-primary">Je participe</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero scroll-animate fade-in-up" id="home">
        <div class="hero-content">
            <h1>Participer</h1>
            <p class="subtitle">Rejoignez le plus grand salon de l'emploi en Algérie<br>Emploitic Connect 2026</p>
            <a href="#registrationForm" class="btn-primary"
                style="display: inline-block; font-size: 1.2em; padding: 18px 50px; position: relative; z-index: 1;">Je
                participe maintenant</a>

            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Entreprises</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">10K+</div>
                    <div class="stat-label">Visiteurs</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2K+</div>
                    <div class="stat-label">Offres d'emploi</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section class="registration scroll-animate fade-in-up" id="sign-up">
        <div class="registration-content">
            <div class="registration-info">
                <h2>Participez à Emploitic Connect 2026</h2>
                <div class="info-box">
                    <h3>📧 Confirmation par Email</h3>
                    <p>Vous recevrez votre confirmation d'accès contenant votre <strong>code QR personnel</strong> par
                        email dans les minutes suivant votre inscription.</p>
                </div>
                <div class="info-box">
                    <h3>⚠️ Important</h3>
                    <p><strong>Inscription obligatoire pour accéder au salon</strong></p>
                    <p>N'oubliez pas de vérifier votre dossier <strong>Spam</strong> pour l'email de confirmation</p>
                </div>
                <div class="info-box">
                    <h3>✨ Pourquoi participer?</h3>
                    <ul>
                        <li>Rencontrez les meilleurs recruteurs du pays</li>
                        <li>Découvrez des milliers d'opportunités d'emploi</li>
                        <li>Assistez à des conférences et ateliers exclusifs</li>
                        <li>Élargissez votre réseau professionnel</li>
                        <li>Bénéficiez de conseils carrière personnalisés</li>
                    </ul>
                </div>
            </div>

            <div class="registration-form" id="registrationForm">
                <h3>Inscrivez-vous gratuitement</h3>
                <form id="registrationFormForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="votre@email.com" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone *</label>
                        <input type="tel" id="telephone" name="telephone" placeholder="+213 XXX XX XX XX" required>
                    </div>
                    <div class="form-group">
                        <label for="statut">Statut *</label>
                        <select id="statut" name="statut" required>
                            <option value="">Sélectionnez votre statut</option>
                            <option value="etudiant">Étudiant</option>
                            <option value="diplome">Diplômé</option>
                            <option value="emploi">En recherche d'emploi</option>
                            <option value="professionnel">Professionnel en activité</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="domaine">Domaine d'intérêt</label>
                        <input type="text" id="domaine" name="domaine"
                            placeholder="Ex: Informatique, Marketing, Finance...">
                    </div>
                    <button type="submit" class="btn-submit" id="inscrire-maintenant">S'inscrire maintenant 🚀</button>
                </form>
            </div>
        </div>
    </section>
    <!-- Sponsors Section -->
    <section class="sponsors scroll-animate fade-in-up" id="sponsor">
        <h2>Nos Partenaires Premium</h2>
        <div class="sponsors-grid">
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/emp-white.png" alt="EMP">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/oordoo-white.png" alt="Ooredoo">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/Untitled-1-1.png" alt="Partner">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/elkendi-white.png" alt="El Kendi">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/mdi-white.png" alt="MDI">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/henkel-white.png" alt="Henkel">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/etalent-white.png" alt="eTalent">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/insag-white.png" alt="INSAG">
            </div>
            <div class="sponsor-logo">
                <img src="https://connect.emploitic.com/wp-content/uploads/2025/12/swissport-white.png" alt="Swissport">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="scroll-animate fade-in-up">
        <div class="footer-content">
            <div class="footer-section">
                <img src="https://connect.emploitic.com/wp-content/uploads/2022/11/logo.png" alt="Emploitic"
                    style="height: 45px; margin-bottom: 25px; filter: drop-shadow(0 2px 10px rgba(38, 208, 206, 0.3));">
                <p>Le plus grand salon de l'emploi en Algérie</p>
                <p style="margin-top: 20px; color: rgba(255, 255, 255, 0.6);">Connectez-vous avec les meilleures
                    opportunités de carrière</p>
            </div>
            <div class="footer-section">
                <h3>Adresse</h3>
                <p>Lot El Yasmine N°1</p>
                <p>Draria, 16000</p>
                <p>Alger, Algérie</p>
            </div>
            <div class="footer-section">
                <h3>Contacts</h3>
                <p>📧 recruteur@emploitic.com</p>
                <p>📱 +213 560 90 61 16</p>
                <p style="margin-top: 15px; color: rgba(255, 255, 255, 0.6);">Disponible du Dimanche au Jeudi<br>9h00 -
                    17h00</p>
            </div>
            <div class="footer-section">
                <h3>Suivez-nous</h3>
                <p>Restez informé des dernières actualités</p>
                <div class="social-links">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Twitter">𝕏</a>
                    <a href="#" title="LinkedIn">in</a>
                    <a href="#" title="Instagram">📷</a>
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2026 Emploitic Connect. Tous droits réservés. | Sayenkiller </p>
        </div>
    </footer>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="checkmark">
                <span style="font-size: 3em;">✓</span>
            </div>
            <h3>Inscription réussie!</h3>
            <p>Merci pour votre inscription! Vous recevrez bientôt un email de confirmation avec votre code QR
                personnel.</p>
            <p style="font-size: 0.9em; color: rgba(255, 255, 255, 0.7);">N'oubliez pas de vérifier votre dossier Spam
            </p>
            <button class="btn-primary" onclick="closeModal()" style="margin-top: 20px;">Fermer</button>
        </div>
    </div>

    <script>
    // Create floating particles
    function createParticles() {
        const container = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 20 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 15) + 's';
            container.appendChild(particle);
        }
    }

    createParticles();

    // Toggle mobile menu
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('active');
    }

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });

    // Form submission
    document.getElementById('registrationFormForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = this.querySelector('.btn-submit');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Inscription en cours...';
        submitBtn.disabled = true;

        const formData = new FormData(this);

        fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);

                if (data.success) {
                    // Show success modal
                    document.getElementById('successModal').classList.add('active');
                    // Reset form
                    this.reset();
                } else {
                    // Show error message
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    });

    function closeModal() {
        document.getElementById('successModal').classList.remove('active');
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Close mobile menu if open
                document.getElementById('navLinks').classList.remove('active');
            }
        });
    });

    // Close modal when clicking outside
    document.getElementById('successModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Add parallax effect to hero
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero');
        if (hero) {
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });

    // Scroll animations using IntersectionObserver
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, observerOptions);

    // Observe elements with scroll-animate class
    document.querySelectorAll('.scroll-animate').forEach(el => {
        observer.observe(el);
    });
    </script>
    <?php require 'mouse-cursor.php'; ?>
</body>

</html>