<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use PHPUnit\Framework\TestCase;

/**
 * French amount-to-words test suite.
 *
 * @group headless
 */
class CRM_Donrec_Lang_FrTest extends TestCase {

  protected function setUp(): void {
    parent::setUp();

    if (!class_exists('NumberFormatter')) {
      $this->markTestSkipped('The PHP intl extension is required.');
    }
  }

  /**
   * @dataProvider amountProvider
   */
  public function testAmountToFrenchWords($amount, $expected) {
    $this->assertSame(
      $expected,
      CRM_Donrec_Lang_Fr_Fr::amountToFrenchWords($amount, 'EUR')
    );
  }

  public static function amountProvider() {
    return [
      'zero' => ['0', 'zéro euro'],
      'one euro' => ['1', 'un euro'],
      'plural euros' => ['2', 'deux euros'],
      'one cent' => ['1.01', 'un euro et un centime'],
      'plural cents' => ['40.25', 'quarante euros et vingt-cinq centimes'],
      'decimal comma' => ['40,25', 'quarante euros et vingt-cinq centimes'],
      'French thousands separator' => ['1 234,56', 'mille deux cent trente-quatre euros et cinquante-six centimes'],
      'one million' => ['1000000', "un million d'euros"],
      'rounding to next euro' => ['1.999', 'deux euros'],
    ];
  }

  /**
   * @dataProvider invalidAmountProvider
   */
  public function testInvalidAmountIsRejected($amount) {
    $this->assertFalse(
      CRM_Donrec_Lang_Fr_Fr::amountToFrenchWords($amount, 'EUR')
    );
  }

  public static function invalidAmountProvider() {
    return [
      'null' => [NULL],
      'empty string' => [''],
      'non-numeric value' => ['not an amount'],
    ];
  }

  public function testUnsupportedCurrencyIsRejected() {
    $this->assertFalse(
      CRM_Donrec_Lang_Fr_Fr::amountToFrenchWords('40.00', 'USD')
    );
  }

  public function testCurrencyCodeIsCaseInsensitive() {
    $this->assertSame(
      'quarante euros',
      CRM_Donrec_Lang_Fr_Fr::amountToFrenchWords('40.00', 'eur')
    );
  }

}
