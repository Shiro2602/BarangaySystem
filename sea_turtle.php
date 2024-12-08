<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sea Turtle Hatchling Facility</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
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
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('placeholder_hero_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 0 20px;
            z-index: 2;
        }

        .hero-title {
            font-size: 4.5em;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            width: 100%;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .logo-container img:hover {
            transform: scale(1.02);
        }

        .objectives-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
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
        }

        .attractions-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
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

        @media (max-width: 768px) {
            .hero-title {
                font-size: 3em;
            }

            .about-section,
            .attractions-section {
                grid-template-columns: 1fr;
            }

            .location-info {
                transform: translateY(-50px);
                padding: 30px;
            }

            .objectives-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sea Turtle Conservation</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login to Main System</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Sea Turtle<br>Hatchling<br>Facility</h1>
        </div>
    </div>

    <div class="content-section">
        <div class="location-info" data-aos="fade-up">
            <p><strong>Location:</strong> Labac is a coastal barangay located in Naic, Cavite. It is known for its rich marine biodiversity, including its role as a nesting site for sea turtles (pawikan), and is also home to several pool resorts that attract tourists.</p>
        </div>

        <div class="about-section">
            <div class="text-content" data-aos="fade-right">
                <h2 class="section-title">About the Samahan ng Labac Pawikan Patrollers</h2>
                <p>The Samahan ng Labac Pawikan Patrollers is a community-based organization dedicated to the conservation and protection of sea turtles and their habitat. This group actively safeguards the coastal environment, monitors sea turtle activities, and raises public awareness about marine conservation.</p>
            </div>
            <div class="logo-container" data-aos="fade-left">
                <img src="placeholder_logo.jpg" alt="Pawikan Patrollers Logo">
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

        <div class="attractions-section">
            <div class="text-content" data-aos="fade-right">
                <h2 class="section-title">Local Attractions</h2>
                <p>Labac is not only a haven for sea turtles but also a tourist destination with a variety of pool resorts that cater to families and visitors. These resorts offer relaxing amenities, scenic views, and a chance to experience the community's charm.</p>
                <p>Tourists can enjoy:</p>
                <ul>
                    <li>Swimming pools for all ages</li>
                    <li>Cottages</li>
                </ul>
            </div>
            <div class="attractions-image" data-aos="fade-left">
                <img src="placeholder_resort.jpg" alt="Pool Resort">
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3">
        <p>&copy; 2024 Sea Turtle Hatchling Facility. All rights reserved.</p>
    </footer>

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
