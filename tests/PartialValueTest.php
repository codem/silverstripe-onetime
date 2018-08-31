<?php
namespace Codem\OneTime;
use SapphireTest;
use Exception;
use Config;

class PartialValueTest extends SapphireTest {

  protected $usesDatabase = true;

  /**
   * Throw random partial values at PartialValue... it should never return the string provided to it
   */
  public function testPartialValue() {
    try {
      // create some strings of varying length
      $max = 40;
      $chr = "A";
      for($i = 1; $i <= $max; $i++) {
        $string = str_repeat( $chr, $i );

        $pv = new PartialValue();
        $result = $pv->Get($string, PartialValue::FILTER_HIDE_MIDDLE);
        if($result == $string) {
          throw new Exception("Result matches input at index {$i} / filter=" . PartialValue::FILTER_HIDE_MIDDLE);
        }

        $result = $pv->Get($string);
        if($result == $string) {
          throw new Exception("Result matches input at index {$i} / no filter");
        }

        $this->checkMaxCharactersExposed($result, $chr);

      }
    } catch (Exception $e) {
      $this->assertTrue(false, $e->getMessage() );
    }

  }

  private function checkMaxCharactersExposed($in,$chr ) {
    $max = Config::inst()->get( PartialValue::class, 'max_characters_exposed');
    $count = substr_count ( $in , $chr );
    if($count > $max) {
      throw new Exception("String {$in} has > {$max} characters exposed");
    }
  }
}
