
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MATS Kiosk Test</title>
</head>
<body>
    <h2>MATS College of Technology</h2>
    <h3>Student Queue Kiosk Test</h3>

    <p>Select a service to simulate pressing a physical button.</p>

    <button onclick="createQueue('Examination Payment')">
        Examination Payment
    </button>

    <button onclick="createQueue('Other Student Service')">
        Other Student Service
    </button>

    <p id="result">Waiting for a button press...</p>

    <script>
        async function createQueue(service) {
            const result = document.getElementById('result');

            result.textContent = 'Creating queue...';

            try {
                const response = await fetch('kiosk_request.php', {
                    method: 'POST',
                    headers: {
    'Content-Type': 'application/json',
    'X-Kiosk-Secret': 'MATS_KIOSK_CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_8472'
},
                    body: JSON.stringify({
                        service: service
                    })
                });

                const data = await response.json();

                if (data.success) {
                    result.innerHTML =
                        '<strong>Queue Created Successfully!</strong><br>' +
                        'Queue Number: ' + data.queue_number + '<br>' +
                        'Service: ' + data.service + '<br>' +
                        'Status: ' + data.status + '<br>' +
                        'Date: ' + data.date + '<br>' +
                        'Time: ' + data.time;
                } else {
                    result.textContent =
                        'Failed: ' + data.message;
                }
            } catch (error) {
                result.textContent =
                    'Connection error. Check XAMPP and the PHP files.';
                console.error(error);
            }
        }
    </script>
</body>
</html>
