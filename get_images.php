<?php
function getImages($path) {
    $images = [];

    if (!is_dir($path)) {
        return $images;
    }

    $dirIterator = new DirectoryIterator($path);

    foreach ($dirIterator as $file) {
        if ($file->isFile()) {
            $filename = $file->getFilename();
            preg_match('/(\d+_\d+_\d+_\d+)_([A-Z0-9]+)\.jpeg$/', $filename, $matches);

            if (count($matches) === 3) {
                $timestamp = $matches[1];
                $licensePlate = $matches[2];

                $images[] = [
                    'filepath' => $path . '/' . $filename,
                    'timestamp' => $timestamp,
                    'licensePlate' => $licensePlate,
                ];
            }
        }
    }

    return $images;
}

$path = $_GET['path'];
$images = getImages($path);
echo json_encode($images);
?>
