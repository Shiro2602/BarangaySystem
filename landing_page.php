<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Labac</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2C3E50;
            --accent-color: #3498DB;
            --text-color: #333;
            --light-bg: #F8F9FA;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            background-color: var(--light-bg);
        }

        .hero-section {
            height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
        }

        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: white;
        }

        .hero-title {
            font-size: 4em;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .content-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .location-info {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 60px;
            transform: translateY(-100px);
        }

        .about-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            margin-bottom: 80px;
        }

        .logo-container img {
            width: 300px;
            height: 300px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            display: block;
            margin: 0 auto;
        }

        .logo-container img:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .objectives-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .objective-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .objective-card:hover {
            transform: translateY(-5px);
        }

        .objective-card h4 {
            color: var(--accent-color);
            margin-bottom: 15px;
            font-size: 1.2em;
        }

        .attractions-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            padding-top: 20px;
            margin-bottom: 60px;
        }

        .attractions-section .text-content {
            padding: 20px;
        }

        .attractions-section img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .attractions-section img:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .fareast-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            padding-top: 20px;
            margin-bottom: 60px;
            background-color: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
        }

        .fareast-section .text-content {
            padding: 20px;
        }

        .fareast-section img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .fareast-section img:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .section-title {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 30px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }

        /* Elegant Navbar styles */
        .navbar {
            background: linear-gradient(to right, #1a2a3a, #2C3E50);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-logo {
            height: 40px;
            width: 40px;
            margin-right: 10px;
            border-radius: 50%;
            object-fit: cover;
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .navbar-nav {
            align-items: center;
        }

        .nav-link {
            font-family: 'Poppins', sans-serif;
            color: white !important;
            font-weight: 500;
            padding: 0.7rem 1.5rem !important;
            margin: 0 0.3rem;
            border: 2px solid transparent;
            border-radius: 30px;
            transition: all 0.3s ease;
            background: linear-gradient(to right, #3498DB, #2980b9);
        }

        .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .nav-link {
                margin: 0.5rem 0;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3em;
            }

            .about-section,
            .attractions-section,
            .fareast-section {
                grid-template-columns: 1fr;
            }

            .location-info {
                transform: translateY(-50px);
                padding: 30px;
            }

            .objectives-list {
                grid-template-columns: 1fr;
            }

            .fareast-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="image/logo.png" alt="Barangay Logo" class="navbar-logo me-2">
                Barangay Labac
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login to Main System
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <img src="image/barangaylabac.jpg" alt="Barangay Labac Logo" class="hero-image">
        <div class="hero-content">
            <h1 class="hero-title">Barangay<br>Labac<br></h1>
        </div>
    </div>

    <div class="content-section">
        <div class="location-info" data-aos="fade-up">
            <p><strong>Description:</strong> Labac is a coastal barangay located in Naic, Cavite. It is known for its nesting site for sea turtles (pawikan). The area is also home to the Far East Maritime Foundation, a premier maritime training institution that provides comprehensive programs for aspiring seafarers. Additionally, Labac features a pool resort that attract tourists, offering a blend of natural beauty and recreational facilities.</p>
        </div>

        <div class="about-section">
            <div class="text-content" data-aos="fade-right">
                <h2 class="section-title">About the Samahan ng Labac Pawikan Patrollers</h2>
                <p>The Samahan ng Labac Pawikan Patrollers is a community-based organization dedicated to the conservation and protection of sea turtles and their habitat. This group actively safeguards the coastal environment, monitors sea turtle activities, and raises public awareness about marine conservation.</p>
            </div>
            <div class="logo-container" data-aos="fade-left">
                <img src="image/slpp.png" alt="Pawikan Patrollers Logo">
            </div>
        </div>

        <h2 class="section-title">Our Objectives</h2>
        <div class="objectives-list">
            <div class="objective-card" data-aos="fade-up" data-aos-delay="100">
                <h4>Conservation Efforts</h4>
                <ul>
                    <li>Monitor and protect sea turtle nests to ensure the successful hatching of eggs.</li>
                    <li>Assist in the safe release of hatchlings into the sea.</li>
                </ul>
            </div>

            <div class="objective-card" data-aos="fade-up" data-aos-delay="200">
                <h4>Community Engagement</h4>
                <ul>
                    <li>Educate the local community about the importance of sea turtles and marine conservation.</li>
                    <li>Encourage sustainable fishing practices to protect marine life.</li>
                </ul>
            </div>

            <div class="objective-card" data-aos="fade-up" data-aos-delay="300">
                <h4>Environmental Protection</h4>
                <ul>
                    <li>Conduct regular coastal clean-ups to maintain a safe environment.</li>
                    <li>Advocate against illegal activities, such as poaching or destruction of nests.</li>
                </ul>
            </div>
        </div>

        <div class="fareast-section">
            <div class="text-content" data-aos="fade-right">
                <h1 style="color: #2C3E50; margin-bottom: 30px;">Marine Training Center</h1>
                <p>Far East Maritime Foundation Inc. is a premier maritime training institution located in Barangay Labac, dedicated to developing skilled maritime professionals. The foundation provides comprehensive training programs and state-of-the-art facilities for aspiring seafarers.</p>
                <p>Key features of the training center:</p>
                <ul>
                    <li>Advanced maritime simulation facilities</li>
                    <li>Professional certification courses</li>
                    <li>Modern training equipment and facilities</li>
                    <li>Experienced maritime instructors</li>
                    <li>Industry-standard safety training</li>
                </ul>
            </div>
            <div class="attractions-image" data-aos="fade-left">
                <img src="image/fareast.jpg" alt="Far East Maritime Foundation">
            </div>
        </div>

        <div class="attractions-section">
            <div class="text-content" data-aos="fade-right">
                <h1 style="color: #2C3E50; margin-bottom: 30px;">Local Attractions</h1>
                <p>Labac is not only a haven for sea turtles but also features Roberto's Pool Resort, a tourist destination that caters to families and visitors. The resort offers relaxing amenities, event catering services, and a chance to experience the community's charm.</p>
                <p>Tourists can enjoy:</p>
                <ul>
                    <li>Swimming pools for all ages</li>
                    <li>Cottages</li>
                </ul>
            </div>
            <div class="attractions-image" data-aos="fade-left">
                <img src="image/robertos.jpg" alt="Pool Resort">
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });
    </script>
</body>
</html>
