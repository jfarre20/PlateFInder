# PlateFInder
shows license plate captures from plateminder

Make sure you have PHP enabled on your web server

Dump everything into a www/platefinder directory


edit this line:

$data = getDatesAndCameras('/data/plateminder/data/images');


also edit this line for your timezone if your plateminder container is set to UTC like mine is

//convert to EST


                var hour = (parseInt(timestampArray[0]) - 4 + 24) % 24; // Add 24 and use modulo to handle negative hours

to point to where plateminder dumps the photos

ensure read permission

enjoy
