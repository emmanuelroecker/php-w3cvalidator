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
    private function file_get_contents_utf8($filename)
    {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Content-Type: text/html; charset=utf-8"
            )
        );

        $context = stream_context_create($opts);
        $result  = @file_get_contents($filename, false, $context);

        return $result;
    }

    public function testHtml()
    {
        $finder = new Finder();
        $files  = $finder->files()->in(__DIR__ . "/entry/");
        $files  = [$files, __DIR__ . "/glicer.css", __DIR__ . "/glicer.html"];

        $validator = new GlW3CValidator(__DIR__ . "/result");
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
            "test1/w3c_html_index.html",
            "test2/w3c_html_index.html",
            "w3c_html_glicer.html"
        ];

        $this->assertEquals(count($filestest), $count);
        $this->assertEquals(count($filestest), count($result));

        foreach ($filestest as $file) {
            $src = __DIR__ . "/expected/" . $file;
            $dst = __DIR__ . "/result/" . $file;

            $srccontent = file_get_contents($src);
            $dstcontent = file_get_contents($dst);

            //$srcencoding = mb_detect_encoding($srccontent, "UTF-8");
            //$dstencoding = mb_detect_encoding($dstcontent, "UTF-8");

            $this->assertEquals(0, strcmp($srccontent, $dstcontent),"$srccontent\n\n$dstcontent");

            //$this->assertEquals($srcencoding, $dstencoding, "$src:$srcencoding different to $dst:$dstencoding ");

            //$this->assertEquals($srccontent, $dstcontent, "$src different to $dst");

            //print_r($srccontent);
            //print_r($dstcontent);
        }
    }

    public function testHtmlCss()
    {
        $finder = new Finder();
        $files  = $finder->files()->in(__DIR__ . "/entry/");
        $files  = [$files, __DIR__ . "/glicer.css", __DIR__ . "/glicer.html"];

        $validator = new GlW3CValidator(__DIR__ . "/result");

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
            "test1/w3c_html_index.html",
            "test1/css/w3c_css_glicer.html",
            "test2/w3c_html_index.html",
            "test2/css/w3c_css_glicer.html",
            "w3c_html_glicer.html",
            "w3c_css_glicer.html"
        ];

        $this->assertEquals(count($filestest), $count);
        $this->assertEquals(count($filestest), count($result));

        foreach ($filestest as $file) {
            $src = __DIR__ . "/expected/" . $file;
            $dst = __DIR__ . "/result/" . $file;

            $srccontent = $this->file_get_contents_utf8($src);
            $dstcontent = $this->file_get_contents_utf8($dst);

            $srcencoding = mb_detect_encoding($srccontent, "UTF-8");
            $dstencoding = mb_detect_encoding($dstcontent, "UTF-8");

            $this->assertEquals($srcencoding, $dstencoding, "$src:$srcencoding different to $dst:$dstencoding ");

            $this->assertEquals($srccontent, $dstcontent, "$src different to $dst");

            //var_dump($srccontent);
            //var_dump($dstcontent);
        }
    }
}