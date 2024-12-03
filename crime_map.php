<?php
require_once 'auth_check.php';
require_once 'config.php';

// Get all blotter records with location data
$query = "SELECT b.*, 
    CONCAT(c.last_name, ', ', c.first_name) as complainant_name,
    CONCAT(r.last_name, ', ', r.first_name) as respondent_name
    FROM blotter b 
    JOIN residents c ON b.complainant_id = c.id 
    JOIN residents r ON b.respondent_id = r.id 
    ORDER BY b.incident_date DESC";
$result = $conn->query($query);
$incidents = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Map - Barangay Labac</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #2c3e50;
            padding-top: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar h3 {
            color: white;
            font-size: 1.5rem;
            padding: 0 15px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar nav {
            padding: 0 15px;
        }
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a.active {
            background-color: #3498db;
            color: white;
        }
        .sidebar i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        /* Main Content Adjustment */
        .main-content {
            margin-left: 250px;
            padding: 20px 30px;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        /* Additional Styles */
        .main-content {
            padding: 20px;
            margin-left: 250px; /* Adjust based on your sidebar width */
        }
        #map {
            height: 70vh;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .incident-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 70vh;
            overflow-y: auto;
        }
        .legend {
            background: white;
            padding: 15px;
            border-radius: 5px;
            position: absolute;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .legend h6 {
            margin-bottom: 10px;
            font-weight: bold;
        }
        .legend div {
            margin: 5px 0;
        }
        .page-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .form-select, .form-control {
            margin-bottom: 0;
        }
        .filter-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <?php require_once 'includes/header.php'; ?>

            <div class="col main-content">
                <div class="page-title">
                    <h2>Crime Map - Barangay Labac</h2>
                    <p class="text-muted">Interactive map showing reported incidents in Barangay Labac, Naic, Cavite</p>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="filter-label">Incident Type</label>
                            <select id="incidentType" class="form-select">
                                <option value="">All Incident Types</option>
                                <?php
                                $types = array_unique(array_column($incidents, 'incident_type'));
                                foreach($types as $type) {
                                    echo "<option value='".htmlspecialchars($type)."'>".htmlspecialchars($type)."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="filter-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Resolved">Resolved</option>
                                <option value="Dismissed">Dismissed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="filter-label">Start Date</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="filter-label">End Date</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8 position-relative">
                        <!-- Map Container -->
                        <div id="map"></div>
                        <!-- Legend -->
                        <div class="legend">
                            <h6>Status Legend</h6>
                            <div><span style="color: red">●</span> Pending</div>
                            <div><span style="color: orange">●</span> Ongoing</div>
                            <div><span style="color: green">●</span> Resolved</div>
                            <div><span style="color: gray">●</span> Dismissed</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <!-- Incident Information Panel -->
                        <div class="incident-info">
                            <h4 class="mb-3">Incident Information</h4>
                            <div id="incident-details">
                                <p class="text-muted">Select a marker on the map to view incident details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBdF_zkpToHo6ULm-Dv9LDL2r_qQcwb9x8&callback=initMap" async defer></script>
    <script>
        const incidents = <?php echo json_encode($incidents); ?>;
        let map;
        let markers = [];
        let geocoder;

        function initMap() {
            geocoder = new google.maps.Geocoder();
            
            // Center on Labac, Naic, Cavite
            const labacCenter = { lat: 14.3190, lng: 120.7750 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: labacCenter,
                zoom: 16,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            // Add Barangay Labac marker
            new google.maps.Marker({
                position: labacCenter,
                map: map,
                title: 'Barangay Labac',
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            });

            plotIncidents();

            // Add filter listeners
            document.getElementById('incidentType').addEventListener('change', filterMarkers);
            document.getElementById('status').addEventListener('change', filterMarkers);
            document.getElementById('startDate').addEventListener('change', filterMarkers);
            document.getElementById('endDate').addEventListener('change', filterMarkers);
        }

        function plotIncidents() {
            incidents.forEach(incident => {
                let fullAddress = incident.incident_location;
                if (!fullAddress.toLowerCase().includes('labac')) {
                    fullAddress += ', Labac, Naic, Cavite';
                }

                geocoder.geocode({ address: fullAddress }, (results, status) => {
                    if (status === 'OK') {
                        let markerColor;
                        switch(incident.status) {
                            case 'Pending': markerColor = 'red'; break;
                            case 'Ongoing': markerColor = 'orange'; break;
                            case 'Resolved': markerColor = 'green'; break;
                            case 'Dismissed': markerColor = 'gray'; break;
                            default: markerColor = 'red';
                        }

                        const marker = new google.maps.Marker({
                            map: map,
                            position: results[0].geometry.location,
                            title: incident.incident_type,
                            icon: `http://maps.google.com/mapfiles/ms/icons/${markerColor}-dot.png`
                        });

                        const infoWindow = new google.maps.InfoWindow({
                            content: `<strong>${incident.incident_type}</strong><br>
                                     Date: ${incident.incident_date}<br>
                                     Status: ${incident.status}`
                        });

                        marker.addListener('mouseover', () => infoWindow.open(map, marker));
                        marker.addListener('mouseout', () => infoWindow.close());
                        marker.addListener('click', () => showIncidentDetails(incident));

                        marker.incident = incident;
                        markers.push(marker);
                    }
                });
            });
        }

        function showIncidentDetails(incident) {
            const details = document.getElementById('incident-details');
            details.innerHTML = `
                <p><strong>Case #:</strong> ${incident.id}</p>
                <p><strong>Type:</strong> ${incident.incident_type}</p>
                <p><strong>Date:</strong> ${incident.incident_date}</p>
                <p><strong>Location:</strong> ${incident.incident_location}</p>
                <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(incident.status)}">${incident.status}</span></p>
                <p><strong>Details:</strong> ${incident.incident_details}</p>
                <p><strong>Complainant:</strong> ${incident.complainant_name}</p>
                <p><strong>Respondent:</strong> ${incident.respondent_name}</p>
            `;
        }

        function getStatusColor(status) {
            switch(status) {
                case 'Pending': return 'danger';
                case 'Ongoing': return 'warning';
                case 'Resolved': return 'success';
                case 'Dismissed': return 'secondary';
                default: return 'primary';
            }
        }

        function filterMarkers() {
            const type = document.getElementById('incidentType').value;
            const status = document.getElementById('status').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            markers.forEach(marker => {
                let isVisible = true;
                const incident = marker.incident;

                if (type && incident.incident_type !== type) isVisible = false;
                if (status && incident.status !== status) isVisible = false;
                if (startDate && incident.incident_date < startDate) isVisible = false;
                if (endDate && incident.incident_date > endDate) isVisible = false;

                marker.setVisible(isVisible);
            });
        }
    </script>
</body>
</html>
