<!DOCTYPE html>
<?php
function getDatesAndCameras($path) {
    //echo "Path: " . $path . "<br>";
    $dates = [];
    $cameras = [];
    $dirIterator = new DirectoryIterator($path);

    foreach ($dirIterator as $dateDir) {
        if ($dateDir->isDir() && !$dateDir->isDot()) {
            $date = $dateDir->getFilename();
            $dates[] = $date;

            $cameraIterator = new DirectoryIterator($path . '/' . $date);
            foreach ($cameraIterator as $cameraDir) {
                if ($cameraDir->isDir() && !$cameraDir->isDot()) {
                    $cameras[$date][] = $cameraDir->getFilename();
                }
            }
        }
    }

    if (!is_dir($path)) {
        echo "Path not found: " . $path . "<br>";
        return ['dates' => [], 'cameras' => []];
    }

    $dates = [];
    $cameras = [];
    $dirIterator = new DirectoryIterator($path);

    foreach ($dirIterator as $dateDir) {
        if ($dateDir->isDir() && !$dateDir->isDot()) {
            $date = $dateDir->getFilename();
            $dates[] = $date;
            //echo "Date found: " . $date . "<br>"; // Add this line

            $cameraIterator = new DirectoryIterator($path . '/' . $date);
            foreach ($cameraIterator as $cameraDir) {
                if ($cameraDir->isDir() && !$cameraDir->isDot()) {
                    $camera = $cameraDir->getFilename();
                    $cameras[$date][] = $camera;
                    //echo "Camera found for " . $date . ": " . $camera . "<br>"; // Add this line
                }
            }
        }
    }

    $images = [];

    foreach ($dirIterator as $dateDir) {
        if ($dateDir->isDir() && !$dateDir->isDot()) {
            $date = $dateDir->getFilename();

            $cameraIterator = new DirectoryIterator($path . '/' . $date);
            foreach ($cameraIterator as $cameraDir) {
                if ($cameraDir->isDir() && !$cameraDir->isDot()) {
                    $camera = $cameraDir->getFilename();

                    $imageIterator = new DirectoryIterator($path . '/' . $date . '/' . $camera);
                    foreach ($imageIterator as $imageFile) {
                        if ($imageFile->isFile()) {
                            $filename = $imageFile->getFilename();
                            preg_match('/(\d+_\d+_\d+_\d+)_([A-Z0-9]+)\.jpeg$/', $filename, $matches);

                            if (count($matches) === 3) {
                                $timestamp = $matches[1];
                                $licensePlate = $matches[2];

                                $images[] = [
                                    'filepath' => $path . '/' . $date . '/' . $camera . '/' . $filename,
                                    'timestamp' => $timestamp,
                                    'licensePlate' => $licensePlate,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    return [
        'dates' => $dates,
        'cameras' => $cameras,
        'images' => $images,
    ];
}

$data = getDatesAndCameras('/data/plateminder/data/images');


?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plate Finder</title>

    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="./bootstrap.min.css">

    <!-- Add OpenSeadragon CSS and JS -->
    <script src="./openseadragon.min.js"></script>

    <!--favicon in ./favicon.ico-->
    <link rel="icon" href="./favicon.ico" type="image/x-icon"/>

    <!-- Add custom CSS (optional) -->
    <link rel="stylesheet" href="./styles.css">
</head>
<body>
    <!-- Your content goes here -->
    <div class="container">
        <h1>Plate Finder</h1>
        <form id="search-form">
            <input type="text" id="search-input" class="form-control" placeholder="Search by license plate">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <div id="dates">
            <h2>Dates
            <!-- Allow collapsing the dates list -->
            <button class="btn btn-secondary" id="toggle-dates">Toggle Hide</button>
            </h2>
            <ul>
                <?php foreach ($data['dates'] as $date): ?>
                    <button class="btn btn-secondary date-item m-1" data-date="<?php echo $date; ?>"><?php echo $date; ?></button>
                <?php endforeach; ?>
            </ul>
        </div>
        <div id="cameras">
            <h2>Cameras
            <!-- Allow collapsing the cameras list -->
            <button class="btn btn-secondary" id="toggle-cameras">Toggle Hide</button>
            </h2>
            <ul id="cameras-list">
                <!-- Cameras will be populated here -->
            </ul>
        </div>
        <div id="results">
            Search or Select a Date/Cam to show images.<br> Double Click/Tap an image to enter zoom, click/tap or use mouse wheel or pinch to adjust zoom, drag to move, press esc or double tap to exit zoom mode.<br>AI Detection of plate text may be wrong.<br>Last 30 days of plates are saved.
            <!-- Results will be populated here -->
        </div>
    </div>


    <!-- Add jQuery and Bootstrap JS -->
    <script src="./jquery.min.js"></script>
    <script src="./bootstrap.min.js"></script>

    <!-- Add custom JS (optional) -->
    <script src="./scripts.js"></script>

    <script>
        //collapse and expand dates
        $(document).ready(function () {
            $("#toggle-dates").click(function () {
                $(".date-item").animate({width: 'toggle'});
            });
        });

        //collapse and expand cameras
        $(document).ready(function () {
            $("#toggle-cameras").click(function () {
                $(".camera-item").animate({width: 'toggle'});
            });
        });

        //collapse both when search is clicked
        $(document).ready(function () {
            $("#search-form").submit(function () {
                //if dates are expanded, collapse them
                if ($(".date-item").is(":visible")) {
                    $(".date-item").animate({width: 'toggle'});
                }
                //if cameras are expanded, collapse them
                if ($(".camera-item").is(":visible")) {
                    $(".camera-item").animate({width: 'toggle'});
                }
            });
        });
        
    </script>
</body>

<script>
    $(document).ready(function () {
        var cameras = <?php echo json_encode($data['cameras']); ?>;
        var allImages = <?php echo json_encode($data['images']); ?>;

        function searchImages(licensePlate) {
            // Clear the previous search results
            $("#results").empty();
            var filteredImages = allImages.filter(function (image) {
                return image.licensePlate.toLowerCase().includes(licensePlate.toLowerCase());
            });

            $("#results").empty();

            filteredImages.forEach(function (image) {
                //convert the HH_MM_SS_SSS timestamp to readable time
                var timestamp = image.timestamp;
                var timestampArray = timestamp.split("_");
                //convert to EST
                var hour = (parseInt(timestampArray[0]) - 4 + 24) % 24; // Add 24 and use modulo to handle negative hours
                //am or pm
                var ampm = "AM";
                if (hour >= 12) {
                    ampm = "PM";
                }
                //convert to 12 hour time
                if (hour > 12) {
                    hour = hour - 12;
                } else if (hour === 0) {
                    hour = 12; // Convert 0 hours to 12 for 12-hour time format
                }
                var minute = timestampArray[1];
                var second = timestampArray[2];
                var millisecond = timestampArray[3];
                //truncate milliseconds
                millisecond = millisecond.substring(0, 2);
                var readableDate = hour + ":" + minute + ":" + second + " " + ampm;
                //console.log(readableDate);
                date = image.filepath.split("/")[5].split("_").join("-");
                readableDate = date + " " + readableDate

                var resultItem = $("<div>").addClass("result-item");
                var resultImage = $("<img>").attr("src", image.filepath).attr("width", "200"); // Adjust thumbnail width as needed
                var resultTimestamp = $("<p id=time>").text(readableDate);
                var resultLicensePlate = $("<p id=plate>").text(image.licensePlate);
                resultItem.append(resultImage);

                resultItem.append(resultTimestamp);
                resultItem.append(resultLicensePlate);
                
                $("#results").append(resultItem);
            });

            //if no results, show no results
            if (filteredImages.length == 0) {
                var resultItem = $("<div>").addClass("result-item");
                var resultTimestamp = $("<p id=time>").text("No Results");
                resultItem.append(resultTimestamp);
                $("#results").append(resultItem);
            }
        }

        // When a date item is clicked
        $(".date-item").on("click", function () {
            var date = $(this).data("date");
            //hide the cameras list so it doesnt show up before the animation
            $("#cameras-list").hide();
            var cameraList = cameras[date];
            //animate the cameras list
            $("#cameras-list").animate({width: 'show'});

            $("#cameras-list").empty();

            cameraList.forEach(function (camera) {
                var cameraItem = $("<button>").addClass("btn btn-secondary camera-item m-1").data("camera", camera).text(camera);
                $("#cameras-list").append(cameraItem);
            });

            // When a camera item is clicked
            $(".camera-item").on("click", function () {
                var camera = $(this).data("camera");

                // Update the path to match your server directory structure
                var path = "/data/plateminder/data/images/" + date + "/" + camera;

                // Fetch images from the server using AJAX
                $.get("get_images.php", { path: path }, function (data) {
                    var images = JSON.parse(data);

                    $("#results").empty();

                    images.forEach(function (image) {
                        //convert the 14_26_56_113 timestamp to readable time
                        var timestamp = image.timestamp;
                        var timestampArray = timestamp.split("_");
                        //convert to EST
                        var hour = (parseInt(timestampArray[0]) - 4 + 24) % 24; // Add 24 and use modulo to handle negative hours
                        //am or pm
                        var ampm = "AM";
                        if (hour >= 12) {
                            ampm = "PM";
                        }
                        //convert to 12 hour time
                        if (hour > 12) {
                            hour = hour - 12;
                        } else if (hour === 0) {
                            hour = 12; // Convert 0 hours to 12 for 12-hour time format
                        }
                        var minute = timestampArray[1];
                        var second = timestampArray[2];
                        var millisecond = timestampArray[3];
                        //truncate milliseconds
                        millisecond = millisecond.substring(0, 2);
                        var readableDate = hour + ":" + minute + ":" + second + " " + ampm;
                        //console.log(readableDate);
                        //add date to the readable time
                        date2 = date.split("_").join("-");
                        readableDate = date2 + " " + readableDate;
                    
                        var resultItem = $("<div>").addClass("result-item");
                        var resultImage = $("<img>").attr("src", image.filepath).attr("width", "200"); // Adjust thumbnail width as needed
                        var resultTimestamp = $("<p id=time>").text(readableDate);
                        var resultLicensePlate = $("<p id=plate>").text(image.licensePlate);
                        resultItem.append(resultImage);
                    
                        resultItem.append(resultTimestamp);
                        resultItem.append(resultLicensePlate);
                        $("#results").append(resultItem);
                    });
                });
            });
        });
    $("#search-form").on("submit", function (event) {
        event.preventDefault();
        var searchInput = $("#search-input").val();
        searchImages(searchInput);
    });

    function showFullScreenImage(imageUrl) {
        // Create a new full-screen element
        var fullScreenDiv = document.createElement('div');
        fullScreenDiv.style.position = 'fixed';
        fullScreenDiv.style.top = '0';
        fullScreenDiv.style.left = '0';
        fullScreenDiv.style.width = '100%';
        fullScreenDiv.style.height = '100%';
        fullScreenDiv.style.backgroundColor = '#000';

        // Add an OpenSeadragon viewer to the full-screen element
        var viewer = OpenSeadragon({
          element: fullScreenDiv,
          prefixUrl: "",
          showNavigationControl: false
        });
    
        // Add the image to the viewer
        viewer.addTiledImage({
        tileSource: {
            type: 'image',
            url: imageUrl
        }
        });

        // Set the max zoom level
        viewer.viewport.maxZoomPixelRatio = 4;
       
        // Add the full-screen element to the page
        document.body.appendChild(fullScreenDiv);
    
        // Enter full-screen mode
        if (fullScreenDiv.requestFullscreen) {
          fullScreenDiv.requestFullscreen();
        } else if (fullScreenDiv.mozRequestFullScreen) { /* Firefox */
          fullScreenDiv.mozRequestFullScreen();
        } else if (fullScreenDiv.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
          fullScreenDiv.webkitRequestFullscreen();
        } else if (fullScreenDiv.msRequestFullscreen) { /* IE/Edge */
          fullScreenDiv.msRequestFullscreen();
        }
    
        // Add event listeners to exit full-screen mode
        fullScreenDiv.addEventListener("dblclick", exitFullScreen);
        //add right click to exit full screen
        //fullScreenDiv.addEventListener("contextmenu", exitFullScreen);
        document.addEventListener("keydown", function(event) {
          if (event.key === "Escape") {
            exitFullScreen();
          }
        });
    
        function exitFullScreen() {
          if (document.exitFullscreen) {
            document.exitFullscreen();
          } else if (document.mozCancelFullScreen) { /* Firefox */
            document.mozCancelFullScreen();
          } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
            document.webkitExitFullscreen();
          } else if (document.msExitFullscreen) { /* IE/Edge */
            document.msExitFullscreen();
          }
        }
    }

    //single click to highlight image div
    $(document).on('click', '.result-item', function() {
        $('.result-item').removeClass('highlight');
        $(this).addClass('highlight');
    });

    //open full screen image if single click on .highlight div
    $(document).on('click', '.highlight', function() {
        var imageUrl = $(this).find('img').attr('src');
        showFullScreenImage(imageUrl);
    });

    //when double click on image, open full screen image
    $(document).on('dblclick', '.result-item', function() {
        var imageUrl = $(this).find('img').attr('src');
        showFullScreenImage(imageUrl);
    });

    //when h1 is clicked, hide the images
    $(document).on('click', 'h1', function() {
        $('.result-item').hide();
    });

});
</script>


</html>
