<?php

use CRM_Donrec_ExtensionUtil as E;

class CRM_Donrec_Lang_Fr_Fr extends CRM_Donrec_Lang {

 public function getName() {
    return E::ts("Français (France)");
  }

public function amount2words($amount, $currency, $params = []) {
    return self::amountToFrenchWords($amount, $currency, $params);
  }

public static function amountToFrenchWords($amount, $currency = 'EUR', $params = []) {
    if ($amount === null || $amount === '') {
      return false;
    }

    $normalized = self::normalizeNumericInput($amount);
    if ($normalized === false) {
      return false;
    }

    $value = round((float) $normalized, 2);

    $euros = (int) floor($value);
    $cents = (int) round(($value - $euros) * 100);

    // Guard against float rounding oddities like 1.999999 => 2.00
    if ($cents === 100) {
      $euros++;
      $cents = 0;
    }

    if (!class_exists('NumberFormatter')) {
      return false;
    }

    $formatter = new NumberFormatter('fr_FR', NumberFormatter::SPELLOUT);

    $euroWords = self::normalizeFrenchNumberWords($formatter->format($euros));
    $centWords = self::normalizeFrenchNumberWords($formatter->format($cents));

    $parts = [];

    if ($euros === 0) {
      $parts[] = 'zéro euro';
    }
    else {
      $needsDe = preg_match('/\b(million|millions|milliard|milliards|billion|billions)\b/u', $euroWords);
      if ($needsDe) {
        $parts[] = $euroWords . " d'euros";
      }
      else {
        $parts[] = $euroWords . ' ' . ($euros > 1 ? 'euros' : 'euro');
      }
    }

    if ($cents > 0) {
      $parts[] = $centWords . ' ' . ($cents > 1 ? 'centimes' : 'centime');
    }

    return implode(' et ', $parts);
  }

protected static function normalizeNumericInput($amount) {
    $value = trim((string) $amount);

    if ($value === '') {
      return false;
    }

    // Remove normal spaces and non-breaking spaces
    $value = str_replace(["\xc2\xa0", ' '], '', $value);

    // If both comma and dot exist, assume the last separator is decimal
    $hasComma = strpos($value, ',') !== false;
    $hasDot = strpos($value, '.') !== false;

    if ($hasComma && $hasDot) {
      $lastComma = strrpos($value, ',');
      $lastDot = strrpos($value, '.');

      if ($lastComma > $lastDot) {
        // 1.234,56 => 1234.56
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
      }
      else {
        // 1,234.56 => 1234.56
        $value = str_replace(',', '', $value);
      }
    }
    elseif ($hasComma) {
      $value = str_replace(',', '.', $value);
    }

    if (!is_numeric($value)) {
      return false;
    }

    return $value;
  }

protected static function normalizeFrenchNumberWords($text) {
    $text = mb_strtolower((string) $text, 'UTF-8');
    $text = trim($text);

    // Normalize whitespace
    $text = preg_replace('/\s+/u', ' ', $text);

    // Normalize hyphens
    $text = str_replace('–', '-', $text);
    $text = str_replace('—', '-', $text);

    return $text;
  }
}

/**
 * -------------------------------------------------------------
 * TEST MODE
 * -------------------------------------------------------------
 *
 * This block allows running the file directly with:
 *   php Fr.php
 *
 * Or with CiviCRM CLI:
 *   cv scr /full/path/to/CRM/Donrec/Lang/Fr/Fr.php
 *
 * It does NOT generate any receipt.
 * It only:
 *   - tests a set of edge-case amounts,
 *   - fetches real amounts from civicrm_contribution,
 *   - prints the French words conversion.
 *
 */
if (PHP_SAPI === 'cli') {

  $edgeCases = [
    '0',
    '0.01',
    '0.02',
    '0.10',
    '0.20',
    '0.50',
    '0.99',
    '1',
    '1.01',
    '1.10',
    '2',
    '10',
    '11',
    '17',
    '20',
    '21',
    '30',
    '31',
    '41',
    '51',
    '61',
    '70',
    '71',
    '72',
    '80',
    '81',
    '90',
    '91',
    '99',
    '100',
    '101',
    '110',
    '121',
    '199.99',
    '200',
    '201',
    '999.99',
    '1000',
    '1001',
    '1010',
    '1100',
    '2000.02',
    '10000',
    '100000',
    '1000000',
    '1000000.01',
    '2000000',
    '1000000000',
  ];

  $amounts = $edgeCases;

  if (class_exists('CRM_Core_DAO')) {
    try {
      $sql = "
        SELECT DISTINCT total_amount
        FROM civicrm_contribution
        WHERE total_amount IS NOT NULL
          AND total_amount > 0
          AND is_test = 0
        ORDER BY id DESC
        LIMIT 200
      ";

      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $amounts[] = (string) $dao->total_amount;
      }
    }
    catch (Exception $e) {
      echo "Erreur lors de la lecture des contributions CiviCRM : " . $e->getMessage() . PHP_EOL;
    }
  }
  else {
    echo "Mode test CLI simple : CiviCRM non bootstrapé, seuls les cas limites sont testés." . PHP_EOL . PHP_EOL;
  }

  $amounts = array_values(array_unique($amounts, SORT_STRING));

  echo "Nombre de montants testés : " . count($amounts) . PHP_EOL . PHP_EOL;

  foreach ($amounts as $amount) {
    $words = CRM_Donrec_Lang_Fr_Fr::amountToFrenchWords($amount, 'EUR');

    if ($words === false || $words === null || $words === '') {
      echo str_pad((string) $amount, 12, ' ', STR_PAD_RIGHT) . " => [ERREUR]" . PHP_EOL;
    }
    else {
      echo str_pad((string) $amount, 12, ' ', STR_PAD_RIGHT) . " => " . $words . PHP_EOL;
    }
  }

  exit(0);
}