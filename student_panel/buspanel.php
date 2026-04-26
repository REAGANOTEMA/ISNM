<?php
session_start();
include_once '../includes/config.php';
include_once '../includes/functions.php';
include_once '../includes/auth_functions.php';

// Check if student is logged in
checkAuth('Student');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ERP - Buses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/oranbyte-google-translator.css">
    <script src="../js/oranbyte-google-translator.js"></script>
    <link rel="stylesheet" href="../includes/navigation_helper.php">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #3949ab;
            --accent-color: #ffd700;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: bold;
            color: var(--primary-color) !important;
        }
        
        .navbar-brand img {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            border-radius: 50%;
        }
        
        .bus-icon {
            height: 50px;
            width: 50px;
            border: 2px solid var(--primary-color);
            margin-top: 15px;
            margin-left: 10px;
            border-radius: 50%;
            background-image: url('../images/1.png');
            background-position: center;
            background-size: cover;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .pending{
            margin-left: 30%;
            text-align: center;
        }
        
        #pen{
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .bus-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--success-color) !important;
            margin-bottom: 20px;
        }
        
        .bus-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .bus-card a {
            text-decoration: none;
            color: inherit;
        }
        
        .bus-info h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .bus-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .request-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.3);
        }
        
        .request-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.4);
        }
        
        .student-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .student-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid var(--accent-color);
            margin-bottom: 10px;
        }
        
        @media only screen and (max-width: 700px) {
            #pen {
                height: 200px;
                width: 350px;
                margin-left: -60%;
            }
            
            .bus-icon {
                height: 40px;
                width: 40px;
            }
            
            .student-header {
                padding: 15px;
            }
            
            .student-header img {
                width: 50px;
                height: 50px;
            }
        }

        
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../student_profile.php">
                <img src="../images/school-logo.png" alt="ISNM Logo">
                IGANGA SCHOOL OF NURSING AND MIDWIFERY
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../student_profile.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="buspanel.php">
                            <i class="fas fa-bus"></i> Bus Service
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../student_communication_system.php">
                            <i class="fas fa-envelope"></i> Messages
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../student_profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="../student_communication_system.php">
                                <i class="fas fa-envelope"></i> Messages
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Student Header -->
        <div class="student-header">
            <img src="../images/default-avatar.png" alt="Student Avatar" id="student-avatar">
            <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
            <p class="mb-0">
                <i class="fas fa-id-card"></i> Index: <?php echo htmlspecialchars($_SESSION['nsin_number']); ?> | 
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($_SESSION['phone']); ?> | 
                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($_SESSION['program']); ?>
            </p>
        </div>

        <!-- Bus Service Section -->
        <div class="container border-0 p-3 shadow">
            <h3 class="mb-4">
                <i class="fas fa-bus"></i> Bus Service Management
            </h3>
            
            <?php
            // Get student information using the enhanced authentication system
            $student_id = $_SESSION['user_id'];
            
            // Prepare and execute SQL query using the enhanced database
            $query = "SELECT * FROM students WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
                
                // Update student avatar if profile image exists
                if (!empty($student['profile_image']) && $student['profile_image'] !== 'default-student.png') {
                    echo '<script>
                        document.getElementById("student-avatar").src = "../images/' . $student['profile_image'] . '";
                    </script>';
                }
                
                // Check bus request status
                if (empty($student['bus_request_status']) || $student['bus_request_status'] === 'none') {
                    echo '<div class="text-center">
                        <button type="button" data-student-id="' . $student_id . '" id="request" class="btn request-btn">
                            <i class="fas fa-paper-plane me-2"></i> Request For Bus Service
                        </button>
                        <p class="mt-3 text-muted">Click to apply for transportation service</p>
                    </div>';

                } else if ($student['bus_request_status'] === 'accepted') {
                    echo '<div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Your bus service request has been approved. Available buses:
                    </div>';
                    
                    // Display available buses
                    $sql = "SELECT * FROM buses WHERE status = 'active' ORDER BY bus_number";
                    $bus_result = $conn->query($sql);

                    if ($bus_result && $bus_result->num_rows > 0) {
                        while ($bus = $bus_result->fetch_assoc()) {
                            echo "
                            <div class='bus-card shadow'>
                                <a href='buslocation.php?bus_id={$bus['bus_id']}' class='text-decoration-none text-dark'>
                                    <div class='card-body'>
                                        <div class='d-flex align-items-center'>
                                            <div class='bus-icon'></div>
                                            <div class='bus-info ms-3'>
                                                <h5><i class='fas fa-bus'></i> Bus No: {$bus['bus_number']}</h5>
                                                <p><i class='fas fa-route'></i> Route: {$bus['route']}</p>
                                                <p><i class='fas fa-clock'></i> Departure: {$bus['departure_time']}</p>
                                                <p><i class='fas fa-user-tie'></i> Driver: {$bus['driver_name']}</p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>";
                        }
                    } else {
                        echo "<div class='alert alert-warning text-center'>
                            <i class='fas fa-exclamation-triangle'></i> No active buses available at the moment.
                        </div>";
                    }
                    
                } else if ($student['bus_request_status'] === 'pending') {
                    echo "<div class='pending'>
                        <img src='../images/attention.png' id='pen' alt='Pending'>
                        <h4 class='mt-3'>Request Pending</h4>
                        <p class='text-muted'>Your bus service request is being reviewed by the administration.</p>
                    </div>";
                    
                } else if ($student['bus_request_status'] === 'rejected') {
                    echo "<div class='alert alert-danger text-center'>
                        <i class='fas fa-times-circle'></i> Your bus service request was rejected.
                        <br><small>Please contact the administration for more information.</small>
                    </div>";
                }
            } else {
                echo "<div class='alert alert-danger'>
                    <i class='fas fa-exclamation-triangle'></i> Student not found in the system.
                </div>";
            }

            // Close the database connection
            $stmt->close();
            $conn->close();
            ?>
        </div>

        <!-- Additional Student Services -->
        <div class="container mt-4 border-0 p-3 shadow">
            <h4 class="mb-3">
                <i class="fas fa-th-large"></i> Student Services
            </h4>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                            <h5>Academic Records</h5>
                            <p class="text-muted">View your grades and transcripts</p>
                            <a href="../student_profile.php#academic" class="btn btn-outline-primary btn-sm">View Records</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                            <h5>Fee Balance</h5>
                            <p class="text-muted">Check your financial status</p>
                            <a href="../student_profile.php#fees" class="btn btn-outline-success btn-sm">Check Balance</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-3x text-info mb-3"></i>
                            <h5>Messages</h5>
                            <p class="text-muted">Communicate with administration</p>
                            <a href="../student_communication_system.php" class="btn btn-outline-info btn-sm">View Messages</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>

<script type="text/javascript">
  document.getElementById("request")?.addEventListener("click", function(event) {
    var result = window.confirm("Do you really want to apply for bus service?");
    if (result) {
        var studentId = event.target.getAttribute("data-student-id");
        
        var requestData = {
            student_id: studentId,
            action: 'request_bus'
        };

        fetch("../includes/ajax_student_request.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Bus service request submitted successfully!");
                window.location.reload();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("An error occurred while submitting your request. Please try again.");
        });
    }
});

// Add smooth transitions and animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });

    document.querySelectorAll('.bus-card, .card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});
</script>

</html>
