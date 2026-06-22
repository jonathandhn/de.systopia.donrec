<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: Luciano Spiegel                                |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types=1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds French language helper functions
 */
class CRM_Donrec_Lang_Fr_Fr extends CRM_Donrec_Lang {

  /**
   * Get the (localised) name of the language
   *
   * @return string
   */
  public function getName() {
    return E::ts('Français (France)');
  }

  /**
   * Render a full text expressing the amount in the given currency
   *
   * @param string $amount
   * @param string $currency
   * @param array $params
   * @return string|false
   */
  public function amount2words($amount, $currency, $params = []) {
    return self::amountToFrenchWords($amount, $currency, $params);
  }

  /**
   * Convert an amount into French words for EUR receipts.
   *
   * Examples:
   *   1       => un euro
   *   1.01    => un euro et un centime
   *   21      => vingt-et-un euros
   *   1000000 => un million d'euros
   *
   * @param string|float|int $amount
   * @param string $currency
   * @param array $params
   * @return string|false
   */
  public static function amountToFrenchWords($amount, $currency = 'EUR', $params = []) {
    // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh

    // Donrec currently supports EUR only. Do not silently label an amount in
    // another currency as euros.
    if (strtoupper(trim((string) $currency)) !== 'EUR') {
      return FALSE;
    }

    if ($amount === NULL || $amount === '') {
      return FALSE;
    }

    $normalized = self::normalizeNumericInput($amount);
    if ($normalized === FALSE) {
      return FALSE;
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
      return FALSE;
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

  /**
   * Normalize a raw numeric input.
   *
   * Accepts:
   *   1234.56
   *   1234,56
   *   1 234,56
   *   1 234.56
   *
   * @param mixed $amount
   * @return string|false
   */
  protected static function normalizeNumericInput($amount) {
    $value = trim((string) $amount);

    if ($value === '') {
      return FALSE;
    }

    // Remove normal spaces and non-breaking spaces
    $value = str_replace(["\xc2\xa0", ' '], '', $value);

    // If both comma and dot exist, assume the last separator is decimal
    $hasComma = strpos($value, ',') !== FALSE;
    $hasDot = strpos($value, '.') !== FALSE;

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
      return FALSE;
    }

    return $value;
  }

  /**
   * Normalize formatter output for stable receipt rendering.
   *
   * @param string $text
   * @return string
   */
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
