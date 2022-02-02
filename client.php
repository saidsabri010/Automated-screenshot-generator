
<!DOCTYPE HTML>  
<html>
<head>
    <title>Screenshot API</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<style>
</style>
</head>
<body>  

<?php
include('ScreenshotMachine.php');
require __DIR__ . '/vendor/autoload.php';
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setRedirectUri('https://www.kooora.com/?region=-1&area=0');
    $client->setScopes(Google_Service_Drive::DRIVE);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}
$customer_key = "246ff8";
$secret_phrase = ""; //leave secret phrase empty, if not needed
$secret = "TOP SECRET";
$machine = new ScreenshotMachine($customer_key, $secret);

// all next parameters are optional, see our website screenshot API guide for more details
$website = $_POST['website'];
$options['url'] = $website;
$options['dimension'] = "1920x1080";  // or "1366xfull" for full length screenshot
$options['device'] = "desktop";
$options['format'] = "jpg";
$options['cacheLimit'] = "0";
$options['delay'] = "200";
$options['zoom'] = "100";
$api_url = $machine->generate_screenshot_api_url($options);
$output_file = uniqid('', true).".". "jpg";
file_put_contents($output_file, file_get_contents($api_url));


$client = getClient();
$service = new Google_Service_Drive($client);
//create folder
$folder = new Google_Service_Drive_DriveFile();
$folderId = '';
$folderName = 'screenshotImages';
$res = $service->files->listFiles(array("q" => "name='{$folderName}' and trashed=false"));
if (count($res->getFiles()) == 0){
    $folder->setName($folderName);
    $folder->setMimeType('application/vnd.google-apps.folder');
    $createdFolder = $service->files->create($folder);
    $folderId = $createdFolder->getId();
}else{
    $folderId = $res->getFiles()[0]->getId();
}


//Insert a file
$file = new Google_Service_Drive_DriveFile();
$file->setName(uniqid().'jpg');
$file->setDescription('A test document');
$file->setParents(array($folderId));
$file->setMimeType('image/jpeg');
$data = file_get_contents($output_file);

$createdFile = $service->files->create($file, array(
      'data' => $data,
      'mimeType' => 'image/jpeg',
      'uploadType' => 'multipart',
    ));

//put link to your html code
echo '<img src="' . $api_url . '">' . PHP_EOL;

//or save screenshot as an image

$output_file = uniqid('', true).".". "jpg";
echo 'Screenshot saved as ' . $output_file . PHP_EOL;
?>

<h2>Screenshot API</h2>
<form name="form" action="" method="POST">
  Website: <input placeholder = "url" type="text" name="website" id="website">
  <br><br>
  <input class="btn btn-primary" type="submit" name="submit" value="Capture">  
</form>


</body>
</html>