
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATS Smart Queue System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        header {
            background: #123b70;
            color: white;
            padding: 28px 20px;
            text-align: center;
        }

        header h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        header p {
            margin: 0;
            color: #dbeafe;
            line-height: 1.6;
        }

        main {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome {
            text-align: center;
            margin-bottom: 32px;
        }

        .welcome h2 {
            font-size: 28px;
            margin-bottom: 12px;
            color: #123b70;
        }

        .welcome p {
            color: #64748b;
            line-height: 1.7;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .service-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .service-card h3 {
            color: #123b70;
            margin-top: 0;
            min-height: 44px;
        }

        .service-card p {
            color: #64748b;
            line-height: 1.6;
            min-height: 48px;
        }

        .service-button {
            display: block;
            width: 100%;
            padding: 12px;
            background: #123b70;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }

        .service-button:hover {
            background: #1d4f91;
        }

        .notice {
            margin-top: 28px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        footer {
            margin-top: 40px;
            padding: 20px;
            background: #e2e8f0;
            text-align: center;
            color: #475569;
        }

        @media (max-width: 600px) {
            header h1 {
                font-size: 23px;
            }

            .welcome h2 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Welcome to MATS College of Technology!</h1>
    <p>IoT-Based Smart Queue Management and Examination Payment Monitoring System</p>
</header>

<main>
    <section class="welcome">
        
        <p>
            Welcome to the Student Queue Kiosk.
            Please select the service you need below to request your queue number.
        </p>
    </section>

    <section class="services">

        <div class="service-card">
            <h3>Tuition Fee Payment</h3>
            <p>For students who need to pay their tuition fees.</p>
            <a class="service-button"
               href="student_queue.php?service=Tuition%20Fee%20Payment">
                Select Service
            </a>
        </div>

        <div class="service-card">
            <h3>Enrollment Fee</h3>
            <p>For students who need assistance with enrollment fee payment.</p>
            <a class="service-button"
               href="student_queue.php?service=Enrollment%20Fee">
                Select Service
            </a>
        </div>

        <div class="service-card">
            <h3>Tuition Balance</h3>
            <p>For students who need to check or settle their tuition balance.</p>
            <a class="service-button"
               href="student_queue.php?service=Tuition%20Balance">
                Select Service
            </a>
        </div>

        <div class="service-card">
            <h3>White Form</h3>
            <p>For students requesting assistance with the white form.</p>
            <a class="service-button"
               href="student_queue.php?service=White%20Form">
                Select Service
            </a>
        </div>

        <div class="service-card">
            <h3>Intramurals Payment</h3>
            <p>For students who need to pay their intramurals fees.</p>
            <a class="service-button"
               href="student_queue.php?service=Intramurals%20Payment">
                Select Service
            </a>
        </div>

        <div class="service-card">
            <h3>Credentials Payment</h3>
            <p>For students paying fees for school credentials or documents.</p>
            <a class="service-button"
               href="student_queue.php?service=Credentials%20Payment">
                Select Service
            </a>
        </div>

    </section>

    <p class="notice">
        Please select the service that matches your transaction.
        Follow the instructions to request your queue number.
    </p>

    <p class="notice">
        <a href="register.php">Student Registration</a>
        &nbsp; | &nbsp;
        <a href="queue_status.php">View Queue Status</a>
    </p>
</main>

<footer>
    &copy; 2026 MATS College of Technology
</footer>

</body>
</html>
