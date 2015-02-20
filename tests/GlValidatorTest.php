<?php
/**
 * Test GlValidator
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   GlHtml\Tests
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   MIT
 * @link      http://www.glicer.com/solver
 *
 * Created : 20/02/15
 * File : GlValidatorTest.php
 *
 */
namespace GlValidator\Tests;

use GlValidator\GlW3CValidator;

/**
 * @covers \GlValidator\GlW3CValidator
 */
class GlValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testHtmlCss()
    {
        $validator = new GlW3CValidator(__DIR__ . "/result/");

        $count  = 0;
        $result = $validator->validate(
                            [__DIR__ . "/entry/"],
                                function ($filename) use (&$count) {
                                    $filename = strtr($filename, ["\\" => "/"]);
                                    $dir = strtr(__DIR__ , ["\\" => "/"]);
                                    switch ($count) {
                                        case 0:
                                            $this->assertEquals($dir . "/entry/test1/css/glicer.css", $filename);
                                            break;
                                        case 1:
                                            $this->assertEquals($dir . "/entry/test1/index.html", $filename);
                                            break;
                                        case 2:
                                            $this->assertEquals($dir . "/entry/test2/css/glicer.css", $filename);
                                            break;
                                        case 3:
                                            $this->assertEquals($dir . "/entry/test2/index.html", $filename);
                                            break;
                                        default:
                                    }
                                    $count++;
                                }
        );

        $this->assertEquals(4, $count);
        $this->assertEquals(4, count($result));

        $filestest = ["test1/w3c_html_index.html","test1/css/w3c_css_glicer.html","test2/w3c_html_index.html","test2/css/w3c_css_glicer.html"];

        foreach ($filestest as $file) {
            $this->assertFileEquals(__DIR__ . "/expected/" . $file,__DIR__. "/result/" . $file);
        }
    }
}