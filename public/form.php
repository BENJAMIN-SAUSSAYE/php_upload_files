<?php
define('KB', 1024);
define('MB', 1048576);
define('GB', 1073741824);
define('TB', 1099511627776);

//echo '<pre>', print_r($_FILES), '</pre>';
//init variables
$firstname = $lastname = $age = '';

// init array
$errors = []; // Store errors here
$uploaded = []; // Store uploaded files here -> OK
$failed = []; // Store failed upload files here -> KO
$uploaddir = 'uploads/'; // dossier de destination
$phpFileUploadErrors = array(
    0 => 'There is no error, the file uploaded with success',
    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
    3 => 'The uploaded file was only partially uploaded',
    4 => 'No file was uploaded',
    6 => 'Missing a temporary folder',
    7 => 'Failed to write file to disk.',
    8 => 'A PHP extension stopped the file upload.',
);

// on check si on est en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datas = array_map('trim', $_POST);
    if (!empty($datas)) {
        // on regarde si un ajout de homer est demandé
        if (!empty($datas['firstname'])) {
            $firstname = $datas['firstname'];
            $lastname = $datas['lastname'];
            $age = $datas['age'];

            //TODO : STORE IN DATABASE !!!!
        }

        // on regarde si une suppression de fichier est demandé
        if (!empty($datas['deletefile'])) {
            if (file_exists($datas['deletefile'])) {
                echo 'file exist';
                $deleteOperation = unlink($datas['deletefile']);
            }
        }
    }

    // on check si au minimum un fichier est present
    if (!empty($_FILES['files']['name'][0])) {
        // on recupère le tableau de fichiers
        $files = $_FILES['files'];
        // on init les variables pour l'upload des fichiers
        $fileExtensionsAllowed = [
            'jpeg', 'jpg', 'png', 'gif', 'webp',
            // 'svg', 'ico', 'tiff', 
        ]; // These will be the only file extensions authorized
        //Type image
        $fileTypeMimeAllowed = [
            'image/gif',                // : GIF ; défini dans la RFC 204516 et la RFC 20462.
            'image/jpeg',               // : JPEG image JFIF ; défini dans la RFC 204516 et la RFC 20462 (attention, sur le navigateur Internet Explorer le type MIME peut être « image/pjpeg »17).
            'image/png',                // : Portable Network Graphics ; enregistré18 (attention, à l'instar du jpeg sur le navigateur Internet Explorer le type MIME peut être « image/x-png »).
            'image/webp',               // :  
            /*
            'image/tiff',               // : Tagged Image File Format ; défini dans la RFC 330219.
            'image/vnd.microsoft.icon', // : icône ICO; enregistré20
            'image/x-icon',             // : x-icon est aussi très utilisé
            'image/vnd.djvu',           // : DjVu ; format d'image et de document multipage21.
            'image/svg+xml',            // : image vectorielle SVG ; 
            */
        ];
        $maximumAllowedSizeFiles = 1 * MB; //2Mo Par défaut, l'upload de fichier en PHP est limité à 2Mo. Configurable dans le fichier php.ini via la directive upload_max_filesize.

        // on parcours chaque fichier
        foreach ($files['name'] as $position => $fileName) {
            // on récupère chaque proprieté du fichier en cours de traitement
            $fileTmpName = $files['tmp_name'][$position];
            $fileSize = $files['size'][$position];
            $fileError = $files['error'][$position];
            $fileFullPath = $files['full_path'][$position];
            $fileType = $files['type'][$position];

            // on recupère l'extension du fichier sans le point avant, pour le control suivant
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // on check que l'extension est acceptée
            if (in_array($fileExtension, $fileExtensionsAllowed)) {
                if (in_array($fileType, $fileTypeMimeAllowed)) {
                    // on check qu'il n'y a pas de code erreur en retour
                    if ($fileError === 0) {
                        // on check la taille du fichier
                        if ($fileSize <= $maximumAllowedSizeFiles) {
                            // on génère un nom unique pour l'enregistrement
                            $fileNameNew = uniqid('', true) . '_' . $fileName;
                            $fileDestination = $uploaddir . $fileNameNew;
                            // on déplace le fichier du dossier temporaire vers le dossier d'uplaod
                            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                                $uploaded[$position] = $fileDestination;
                            } else {
                                $failed[$position] = "[{$fileName}] failed to upload.";
                            }
                        } else {
                            $failed[$position] = "[{$fileName}] have a file size superior of {$maximumAllowedSizeFiles}.";
                        }
                    } else {
                        $failed[$position] = "[{$fileName}] have an error with code :{$fileError}. Message :{$phpFileUploadErrors[$fileError]}.";
                    }
                } else {
                    $failed[$position] = "[{$fileName}] type MIME {$fileType} is not allowed.";
                }
            } else {
                $failed[$position] = "[{$fileName}] file extension {$fileExtension} is not allowed.";
            }
        }
    }
}


// On recupère les fichiers uploader pour proposer la suppression
$allFiles = scandir($uploaddir, SCANDIR_SORT_DESCENDING);
$files = array_diff($allFiles, array('.', '..'));

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@1.*/css/pico.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>Upload Files</title>
</head>

<body>
    <main class="container">
        <div class="grid">
            <section>
                <?php if (!empty($failed)) : ?>
                    <?php foreach ($failed as $key => $Item) : ?>
                        <div><mark><?= $Item ?></mark></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
        <div class="grid">
            <section>
                <hgroup>
                    <h2>Homer</h2>
                    <h3>veut ajouter sa photo de profil</h3>
                </hgroup>

                <form action="" method="post" enctype="multipart/form-data">
                    <label for="firstname">Prénom</label>
                    <input type="text" maxlength="50" id="firstname" name="firstname" placeholder="Prénom" value="<?= $firstname ?? '' ?>" required>
                    <label for="lastname">Nom</label>
                    <input type="text" maxlength="50" id="lastname" name="lastname" placeholder="Nom" value="<?= $lastname ?? '' ?>" required>
                    <label for="age">Nom</label>
                    <input type="number" maxlength="3" id="age" name="age" placeholder="age" value="<?= $age ?? '' ?>" required>
                    <label for="files">Fichiers images</label>
                    <input type="file" id="files" name="files[]" required>
                    <button>Valider
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle-dotted" viewBox="0 0 16 16">
                            <path d="M8 0c-.176 0-.35.006-.523.017l.064.998a7.117 7.117 0 0 1 .918 0l.064-.998A8.113 8.113 0 0 0 8 0zM6.44.152c-.346.069-.684.16-1.012.27l.321.948c.287-.098.582-.177.884-.237L6.44.153zm4.132.271a7.946 7.946 0 0 0-1.011-.27l-.194.98c.302.06.597.14.884.237l.321-.947zm1.873.925a8 8 0 0 0-.906-.524l-.443.896c.275.136.54.29.793.459l.556-.831zM4.46.824c-.314.155-.616.33-.905.524l.556.83a7.07 7.07 0 0 1 .793-.458L4.46.824zM2.725 1.985c-.262.23-.51.478-.74.74l.752.66c.202-.23.418-.446.648-.648l-.66-.752zm11.29.74a8.058 8.058 0 0 0-.74-.74l-.66.752c.23.202.447.418.648.648l.752-.66zm1.161 1.735a7.98 7.98 0 0 0-.524-.905l-.83.556c.169.253.322.518.458.793l.896-.443zM1.348 3.555c-.194.289-.37.591-.524.906l.896.443c.136-.275.29-.54.459-.793l-.831-.556zM.423 5.428a7.945 7.945 0 0 0-.27 1.011l.98.194c.06-.302.14-.597.237-.884l-.947-.321zM15.848 6.44a7.943 7.943 0 0 0-.27-1.012l-.948.321c.098.287.177.582.237.884l.98-.194zM.017 7.477a8.113 8.113 0 0 0 0 1.046l.998-.064a7.117 7.117 0 0 1 0-.918l-.998-.064zM16 8a8.1 8.1 0 0 0-.017-.523l-.998.064a7.11 7.11 0 0 1 0 .918l.998.064A8.1 8.1 0 0 0 16 8zM.152 9.56c.069.346.16.684.27 1.012l.948-.321a6.944 6.944 0 0 1-.237-.884l-.98.194zm15.425 1.012c.112-.328.202-.666.27-1.011l-.98-.194c-.06.302-.14.597-.237.884l.947.321zM.824 11.54a8 8 0 0 0 .524.905l.83-.556a6.999 6.999 0 0 1-.458-.793l-.896.443zm13.828.905c.194-.289.37-.591.524-.906l-.896-.443c-.136.275-.29.54-.459.793l.831.556zm-12.667.83c.23.262.478.51.74.74l.66-.752a7.047 7.047 0 0 1-.648-.648l-.752.66zm11.29.74c.262-.23.51-.478.74-.74l-.752-.66c-.201.23-.418.447-.648.648l.66.752zm-1.735 1.161c.314-.155.616-.33.905-.524l-.556-.83a7.07 7.07 0 0 1-.793.458l.443.896zm-7.985-.524c.289.194.591.37.906.524l.443-.896a6.998 6.998 0 0 1-.793-.459l-.556.831zm1.873.925c.328.112.666.202 1.011.27l.194-.98a6.953 6.953 0 0 1-.884-.237l-.321.947zm4.132.271a7.944 7.944 0 0 0 1.012-.27l-.321-.948a6.954 6.954 0 0 1-.884.237l.194.98zm-2.083.135a8.1 8.1 0 0 0 1.046 0l-.064-.998a7.11 7.11 0 0 1-.918 0l-.064.998zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z" />
                        </svg>
                    </button>
                </form>
            </section>
        </div>

        <div class="container">
            <?php if (!empty($files)) : ?>
                <?php foreach ($files as $keyfile => $file) : ?>
                    <figure class="lstimg grid">
                        <img src="<?= $uploaddir . $file ?>" alt="" width="10%" />
                        <form action="" method="post">
                            <input type="hidden" name="deletefile" value="<?= $uploaddir . $file ?>">
                            <button class="contrast">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                                </svg>
                            </button>
                        </form>
                    </figure>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>