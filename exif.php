<?php
# Uncomment the following two lines to report errors to the Browser. (By Benlamine Abdelmourhit {B.A}) 
#error_reporting(E_ALL);
#ini_set("display_errors", 1);
# Extract some EXIF information from JPEG photos and use them to write a caption for the image. {B.A}
# Use random images from a directory. (The folder where this PHP file is stored.) From http://thenewcode.com/205/Generate-random-images-with-PHP    {B.A}
$randomdir = dir('./');
$count = 1;
$pattern = "/(jpg|jpeg)/i";
while($file = $randomdir->read()) { 
	$ext = pathinfo($file, PATHINFO_EXTENSION);
	if (preg_match($pattern, $ext)) {
		$imagearray[$count] = $file;
		$count++;
}
}
$random = rand(1, $count - 1);
$image = $imagearray[$random];
# Check if the EXIF function exist {B.A}
if (function_exists('exif_read_data')) {
$exif = exif_read_data($image);
# We separate the following EXIF tags (comment, copyright, GPS and Flash) from the other EXIF values. {B.A}
isset($exif['UserComment']) ? $comment = $exif['UserComment'] : $comment = "No comment for this image !";
# For the alt attribute content. {B.A}
$readpause = "/,|\.|!|\?|;/";
$alt = strtok($comment,$readpause);
if (isset($exif['Copyright'])) { 
$exifcopyright = $exif['Copyright'];
$copyright = strpos($exifcopyright,"©") ? $exifcopyright : "© " . $exifcopyright;
} else {
$exifcopyright = $copyright = ""; 
}
# From https://stackoverflow.com/questions/2526304/php-extract-gps-exif-data
function gps2Num($coordPart) {
$parts = explode('/', $coordPart);
    if (count($parts) <= 0)
        return 0;
    if (count($parts) == 1)
        return $parts[0];
    return floatval($parts[0]) / floatval($parts[1]);
}
function getGps($exifCoord, $hemi) {
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;
    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;
    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}
if (isset($exif["GPSLatitude"],$exif["GPSLatitudeRef"],$exif["GPSLongitude"],$exif["GPSLongitudeRef"])) { 
$longitude = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
$latitude = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
$location = "Localization : <a href='https://www.openstreetmap.org/search?query=" . $latitude . " " . $longitude . "' target='blank' >" . $latitude . " " . $longitude . "</a>"; 
} else {
$location = "";
}
$flash = isset($exif['Flash']) ? $exif['Flash'] : "" ;
if ($flash === 0) {
$flash = "(without Flash)";
} elseif ($flash === 1) {
$flash = "(with Flash)";
} else {
$flash = "";
}
# We group the following EXIF tags to complete the caption, if one is absent we print nothing but the caption fallback value. {B.A}
if (isset($exif['Make'],$exif['Model'],$exif['DateTime'],$exif['FileSize'],$exif["COMPUTED"]["Width"],$exif["COMPUTED"]["Height"],$exif['MimeType'],$exif['FileName'])) {
$manufacturer = $exif['Make']; $model = $exif['Model']; $DateTime = isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : $exif['DateTime']; $size = $exif['FileSize'];  $Width = $exif["COMPUTED"]["Width"]; $Height = $exif["COMPUTED"]["Height"]; $mimetype = $exif['MimeType']; $filename = $exif['FileName'];
# Print the image size in KB if greater than 1MB format it in MB. We never see an image of 1GB, don't we ? {B.A}
$sizef = $size > 1048576 ? sprintf("%.2f MB",$size / 1048576) : sprintf("%.2f KB",$size / 1024);
# Format the date and time of the image. {B.A}
$dtz = new DateTimeZone('UTC');
$DateTimeN = new DateTime($DateTime,$dtz);
$DateTimef = $DateTimeN->format("H:i l d F Y");
# Format the extension, in this case it will be always JPEG. {B.A}
$mimetypef = "<abbr title='Joint Photographic Experts Group'>" . mb_strtoupper(preg_replace("/image\//"," ", $mimetype)) . "</abbr>";
$caption = "This photo with filename : " . $filename . ", was taken at " . $DateTimef . " using the device " . $manufacturer . " " . $model . " the original resolution is " . $Width . "px by " . $Height . "px and the file size is " . $sizef . " in " . $mimetypef . " image format. " . $flash . " <br/>" . $comment;
} else {
# Reinitialize these EXIF tags variables to empty String, and inform that there is no content. {B.A}
$manufacturer = $model = $DateTime = $DateTimef = $size = $sizef = $Width = $Height = $mimetype = $mimetypef = $filename = "";
$comment ? $caption = "No Metadata information in this image !<br/>" . $comment : $caption = $alt = "No information about this image !";
} 
} else {
$caption = "Metadata information about this image couldn't be extracted !";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>EXIF by PHP</title>
<meta charset="UTF-8"/>
<meta name="developer" content="Benlamine Abdelmourhit"/>
    <style type="text/css">
    figure {margin:auto}
    img {
    display:block;
    width:90%;
    height:auto;
    margin:auto;
    }
    </style>
  </head>
  <body>
  
<figure>
  <img src="<?= $image ?>" alt="<?= $alt ?>"/>
  <figcaption><?= $caption ?><br/><?= $location ?><br/><span><?= $copyright ?></span></figcaption> 
  </figure>
  
  </body>
</html>