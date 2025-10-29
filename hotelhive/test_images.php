<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Test</title>
    <style>
        body {
            background: #000;
            color: #fff;
            padding: 20px;
        }
        .test-image {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border: 2px solid #ffd700;
            margin: 10px;
        }
        .image-info {
            margin: 10px 0;
            padding: 10px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>Image Test Page</h1>
    
    <div class="image-info">
        <h3>Testing Hotel Images:</h3>
    </div>
    
    <div>
        <h4>Oceanview 1:</h4>
        <img src="images/hotel/oceanview_1.jpg" alt="Oceanview 1" class="test-image" 
             onerror="this.style.border='2px solid red'; this.alt='Image not found: oceanview_1.jpg';">
    </div>
    
    <div>
        <h4>Citylights 1:</h4>
        <img src="images/hotel/citylights_1.jpg" alt="Citylights 1" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: citylights_1.jpg';">
    </div>
    
    <div>
        <h4>Luxe Suites 1:</h4>
        <img src="images/hotel/luxe_suites_1.jpg" alt="Luxe Suites 1" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: luxe_suites_1.jpg';">
    </div>
    
    <div>
        <h4>Budget Inn 1:</h4>
        <img src="images/hotel/budget_inn_1.jpg" alt="Budget Inn 1" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: budget_inn_1.jpg';">
    </div>
    
    <div class="image-info">
        <h3>Fallback Images:</h3>
    </div>
    
    <div>
        <h4>Oceanview (fallback):</h4>
        <img src="images/hotel/oceanview.jpg" alt="Oceanview" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: oceanview.jpg';">
    </div>
    
    <div>
        <h4>Citylights (fallback):</h4>
        <img src="images/hotel/citylights.jpg" alt="Citylights" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: citylights.jpg';">
    </div>
    
    <div>
        <h4>Luxe Suites (fallback):</h4>
        <img src="images/hotel/luxe_suites.jpg" alt="Luxe Suites" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: luxe_suites.jpg';">
    </div>
    
    <div>
        <h4>Budget Inn (fallback):</h4>
        <img src="images/hotel/budget_inn.jpg" alt="Budget Inn" class="test-image"
             onerror="this.style.border='2px solid red'; this.alt='Image not found: budget_inn.jpg';">
    </div>
    
    <div class="image-info">
        <h3>Available Images List:</h3>
        <p>Check the images/hotel/ directory for available files.</p>
    </div>
</body>
</html>
