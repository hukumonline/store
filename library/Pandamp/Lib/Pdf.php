<?php

/**
 * Description of Pdf
 *
 * @author nihki <nihki@madaniyah.com>
 */

define('K_TCPDF_EXTERNAL_CONFIG', true);
define ("K_PATH_MAIN", ROOT_DIR."/library/PdfTool/tcpdf/");
define ("K_PATH_URL", ROOT_URL."/library/PdfTool/tcpdf/");
define ("K_PATH_FONTS", K_PATH_MAIN."fonts/");
define ("K_PATH_CACHE", K_PATH_MAIN."cache/");
define ("K_PATH_URL_CACHE", K_PATH_URL."cache/");
define ("K_PATH_IMAGES", K_PATH_MAIN."images/");
define ("K_BLANK_IMAGE", K_PATH_IMAGES."_blank.png");
define ("PDF_PAGE_FORMAT", "A4");
define ("PDF_PAGE_ORIENTATION", "P");
define ("PDF_CREATOR", "HUKUMONLINE");
define ("PDF_AUTHOR", "HUKUMONLINE");
define ("PDF_HEADER_LOGO", "logo_hukumonline.jpg");
define ("PDF_HEADER_LOGO_WIDTH", 30);
define ("PDF_UNIT", "mm");
define ("PDF_MARGIN_HEADER", 5);
define ("PDF_MARGIN_FOOTER", 10);
define ("PDF_MARGIN_TOP", 27);
define ("PDF_MARGIN_BOTTOM", 25);
define ("PDF_MARGIN_LEFT", 15);
define ("PDF_MARGIN_RIGHT", 15);
define ("PDF_FONT_NAME_MAIN", "vera"); //vera
define ('PDF_FONT_MONOSPACED', 'courier');
define ("PDF_FONT_SIZE_MAIN", 10);
define ("PDF_FONT_NAME_DATA", "vera"); //vera
define ("PDF_FONT_SIZE_DATA", 8);
define ("PDF_IMAGE_SCALE_RATIO", 4);
define("HEAD_MAGNIFICATION", 1.1);
define("K_CELL_HEIGHT_RATIO", 1.25);
define("K_TITLE_MAGNIFICATION", 1.3);
define("K_SMALL_RATIO", 2/3);

require_once('PdfTool/tcpdf/tcpdf.php');

class Pandamp_Lib_Pdf extends TCPDF
{
    public function CreateTextBox($textval, $x = 0, $y, $width = 0, $height = 10, $fontsize = 10, $fontstyle = '', $align = 'L') {
        $this->SetXY($x+20, $y); // 20 = margin left
        $this->SetFont(PDF_FONT_NAME_MAIN, $fontstyle, $fontsize);
        $this->Cell($width, $height, $textval, 0, false, $align);
    }
}
