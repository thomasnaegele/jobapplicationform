<?php
include('PHPMailer-master/src/PHPMailer.php');
include('PHPMailer-master/src/Exception.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include('TCPDF-master/tcpdf.php');

    function postDataValid($string) {
        $allowedAscii = [65, 66, 67, 68, 69,
            70, 71, 72, 73, 74, 75, 76, 77, 78, 79,
            80, 81, 82, 83, 84, 85, 86, 87, 89,
            90, 97, 98, 99,
            100, 101, 102, 103, 104, 105, 106, 107, 108, 109,
            110, 111, 112, 113, 114, 115, 116, 117, 118, 119,
            120, 121, 122,
            32,
            48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
            45, 46, 64];
        $length = strlen($string);
        for ($i=0; $i<$length; $i++) {
            if (!in_array(ord($string[$i]), $allowedAscii)) {
                return false;
            }
        }
        return true;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (count($_POST) != 10) {
                throw new \Exception("Invalid form");
            }

            $correctPostDataKeys = [ "gender", "firstname", "lastname", "strasse", "plz", "ort", "email", "phone", "socialNr", "bdate", "passport"];
            $postDataValues = array();

            foreach ($_POST as $key => $value) {
                if (!in_array($key, $correctPostDataKeys)) {
                    throw  new \Exception("Invalid form");
                }
                else {
                    $postDataValues[$key] = $value;
                }
            }
            if ($postDataValues["gender"] != "Frau" && $postDataValues["gender"] != "Herr" ) {
                throw  new \Exception("Invalid form");
            }

            if (!postDataValid($postDataValues["firstname"]) || !postDataValid($postDataValues["lastname"])
                || !postDataValid($postDataValues["strasse"]) || !postDataValid($postDataValues["plz"])
                || !postDataValid($postDataValues["ort"]) || !postDataValid($postDataValues["email"])
                || !postDataValid($postDataValues["phone"]) || !postDataValid($postDataValues["socialNr"])
                || !postDataValid($postDataValues["bdate"]) ) {
                throw  new \Exception("Invalid form");
            }

            $imageFile = $_FILES["passport"]["tmp_name"];
            $imgEncoded = base64_encode(file_get_contents($imageFile));
            $imgDecoded = base64_decode($imgEncoded);

            if(preg_match('(<script>)', $imgDecoded) === 1) {
                throw  new \Exception("Invalid form");
            }

            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            $pdf->SetCreator($_POST['firstname']);
            $pdf->SetAuthor($_POST['firstname']);
            $pdf->SetTitle('Application Form');
            $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.'Application', PDF_HEADER_STRING);
            $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
            $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();
            $html = '
    <h2>Job application form</h2><br>
    <h4>Applicant title: '.$_POST["gender"].'</h4>
    <h4>Applicant name: '.$_POST["firstname"].' '.$_POST["lastname"].'</h4>
    <h4>Applicant address: '.$_POST["strasse"].' '.$_POST["plz"].' '.$_POST["ort"].'</h4>
    <h4>Applicant email: '.$_POST["email"].'</h4>
    <h4>Applicant phone: '.$_POST["phone"].'</h4>
    <h4>Social Nr: '.$_POST["socialNr"].'</h4>
    <h4>Applicant available from: '.$_POST["bdate"].'</h4>
';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Image('@'.base64_decode($imgEncoded));

            $filename = 'application2.pdf';
            $pdfString = $pdf->Output($_SERVER['DOCUMENT_ROOT'].$filename, 'S');
            $email = new PHPMailer();
            $email->isHTML(true);
            $email->SetFrom('krisztianbatori66@gmail.com', 'Your Name');
            $email->Subject   = 'Job Application 4';
            $email->Body      = "<h1>Test is another new text email of PHPMailer html</h1><p>This is a test</p>";
            $email->AddAddress( 'testdummy799@gmail.com' );
            $email->addStringAttachment($pdfString, $filename);
            $email->addAttachment($_FILES["cv"]["tmp_name"], "cv", "base64", "application/pdf");
            $email->Send();

            header('Location: http://192.168.64.2//PHPFormAdvanced/formpage.html?subsuccess=1');
            exit;

        } catch (\Exception $e) {
            header('Location: http://192.168.64.2//PHPFormAdvanced/formpage.html?subsuccess=2');
            exit;
        }
    }