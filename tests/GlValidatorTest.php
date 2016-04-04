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
 * @link      http://dev.glicer.com/
 *
 * Created : 20/02/15
 * File : GlValidatorTest.php
 *
 */
namespace GlValidator\Tests;

use GlValidator\GlW3CValidator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @covers \GlValidator\GlW3CValidator
 */
class GlValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testHtml()
    {
        $finder = new Finder();
        $files  = $finder->files()->sortByName()->in(__DIR__ . "/entry/");
        $files  = [$files, __DIR__ . "/glicer.css", __DIR__ . "/glicer.html"];

        $validator = new GlW3CValidator(__DIR__ . "/result", URL_HTML_VALIDATOR, URL_CSS_VALIDATOR);
        $count     = 0;
        $result    = $validator->validate(
                               $files,
                                   ['html'],
                                   function (SplFileInfo $file) use (&$count) {
                                       $filename = strtr($file->getRelativePathname(), ["\\" => "/"]);
                                       if (strlen($filename) == "") {
                                           $filename = $file->getBasename();
                                       }
                                       switch ($count) {
                                           case 0:
                                               $this->assertEquals("test1/index.html", $filename);
                                               break;
                                           case 1:
                                               $this->assertEquals("test2/index.html", $filename);
                                               break;
                                           case 2:
                                               $this->assertEquals("glicer.html", $filename);
                                               break;
                                           default:
                                               $this->fail(
                                                    "Error count $count with $filename"
                                               );
                                       }
                                       $count++;
                                   }
        );

        $filestest = [
            "test2/w3c_html_index.html"
        ];
               
        $this->assertEquals(NULL,$result['test1/index.html']);
        $this->assertNotEquals(NULL, $result['test2/index.html']);
        $this->assertEquals(NULL, $result['/glicer.html']);
        
        foreach ($filestest as $file) {
            $src = __DIR__ . "/expected/" . $file;
            $dst = __DIR__ . "/result/" . $file;

            $this->assertFileEquals($src, $dst, "$src different to $dst");
        }
    }

    public function testHtmlCss()
    {
        $finder = new Finder();
        $files  = $finder->files()->sortByName()->in(__DIR__ . "/entry/");
        $files  = [$files, __DIR__ . "/glicer.css", __DIR__ . "/glicer.html"];

        $validator = new GlW3CValidator(__DIR__ . "/result", URL_HTML_VALIDATOR, URL_CSS_VALIDATOR);

        $count  = 0;
        $result = $validator->validate(
                            $files,
                                ['html', 'css'],
                                function (SplFileInfo $file) use (&$count) {

                                    $filename = strtr($file->getRelativePathname(), ["\\" => "/"]);
                                    if (strlen($filename) == "") {
                                        $filename = $file->getBasename();
                                    }
                                    switch ($count) {
                                        case 0:
                                            $this->assertEquals("test1/css/glicer.css", $filename);
                                            break;
                                        case 1:
                                            $this->assertEquals("test1/index.html", $filename);
                                            break;
                                        case 2:
                                            $this->assertEquals("test2/css/glicer.css", $filename);
                                            break;
                                        case 3:
                                            $this->assertEquals("test2/index.html", $filename);
                                            break;
                                        case 4:
                                            $this->assertEquals("glicer.css", $filename);
                                            break;
                                        case 5:
                                            $this->assertEquals("glicer.html", $filename);
                                            break;
                                        default:
                                            $this->fail(
                                                 "Error count $count with $filename"
                                            );
                                    }
                                    $count++;
                                }
        );

        $filestest = [
            "test1/css/w3c_css_glicer.html",
            "test2/w3c_html_index.html",
            "test2/css/w3c_css_glicer.html",
            "w3c_css_glicer.html"
        ];

        $this->assertNotEquals(NULL, $result["test1/css/glicer.css"]);
        $this->assertEquals(NULL,$result["test1/index.html"]);
        $this->assertNotEquals(NULL, $result["test2/css/glicer.css"]);
        $this->assertNotEquals(NULL, $result["test2/index.html"]);
        $this->assertNotEquals(NULL, $result["/glicer.css"]);
        $this->assertEquals(NULL, $result["/glicer.html"]);
        
        foreach ($filestest as $file) {
            $src = __DIR__ . "/expected/" . $file;
            $dst = __DIR__ . "/result/" . $file;

            $this->assertFileEquals($src, $dst, "$src different to $dst");
        }
    }
}