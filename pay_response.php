<?php

define('PAYPAL_CLIENT_ID', 'AZs5wgt5b3tRYxIvY5MhO5hxUn_UC_d91ecoMy8KGeyYyTR_bmZBa85oNKvs69N-JNzzV04Alujonm4N');
define('PAYPAL_SECRET', 'EK_AuxssZsPbHcbHyEMaQBu_r5v9Y74EqXbw4oeJU4oQTwCL2gkDitR-LyEwPKwA5UOHe6p1wnVUvbmy');
define('PAYPAL_BASE_URL', 'https://api.paypal.com');
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');
require('fpdf186/fpdf.php'); // Inclure le fichier FPDF


date_default_timezone_set("Africa/Casablanca");
session_start();

unset($_SESSION['room']);

$paramList=$_POST;


// Vérifiez si les données de paiement sont présentes dans la session
if(isset($_SESSION['payment_details'])) {
    // Créer un nouveau objet FPDF
    $pdf = new FPDF();
    $pdf->AddPage();

    // Ajouter un en-tête avec le nom de l'entreprise et le logo
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, utf8_decode('Nom de l\'entreprise'), 0, 1, 'C');
    $pdf->Ln(5); // Saut de ligne

    // Ajouter un pied de page avec l'adresse et les coordonnées de l'entreprise
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, utf8_decode('Taza  | Tél: +212 624054918 | Email: redouane.elbouzrazi@usmba.ac.ma'), 0, 1, 'C');
    $pdf->Ln(10); // Saut de ligne

    // Ajouter le titre du tableau
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, utf8_decode('Détails du paiement'), 0, 1, 'C');
    $pdf->Ln(10); // Saut de ligne

    // Modifier les libellés
    $labels = array(
        'name' => 'Name',
        'phonenum' => 'Phone Number',
        'address' => 'Address',
        'checkin' => 'Check-In Date',
        'checkout' => 'Check-Out Date',
        'totalAmount' => 'Total Payment Amount'
    );

    // Ajouter les informations dans un tableau
    $payment_details = $_SESSION['payment_details'];
    $pdf->SetFont('Arial', '', 12);
    foreach ($labels as $key => $label) {
        $pdf->Cell(70, 10, utf8_decode($label), 1);
        $pdf->Cell(0, 10, utf8_decode($payment_details[$key]), 1);
        $pdf->Ln();
    }

    // Ajouter un tampon au bas de la page
    $pdf->Image('images/logo/tmp.png', 130, 150, 40); // Remplacez 'images/tampon.png' par le chemin de votre propre image tampon

    // Définir le nom du fichier
    $file_name = 'recu_paiement_' . date('YmdHis') . '.pdf';

    // En-têtes pour forcer le téléchargement du fichier
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');

    // Afficher le PDF
    $pdf->Output();

    // Arrêter le script après avoir téléchargé le fichier
    exit();
} else {
    // Rediriger l'utilisateur vers la page de paiement si les données de paiement ne sont pas présentes dans la session
    header('Location: pay_now.php');
    exit();
}
?>
