<?php
/**
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   GlValidator
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   MIT
 * @link      http://dev.glicer.com/
 *
 * Created : 19/02/15
 * File : GlW3CValidator.php
 *
 */

namespace GlValidator;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GlHtml\GlHtml;

/**
 * Class GlW3CValidator
 * @package GlValidator
 */
class GlW3CValidator
{
    const MAX_RETRY = 3;

    private $types = [
        'html' => [
            'validator' => '/',
            'selector'  => '.success',
            'resulttag' => '#results',
            'field'     => 'file',
            'css'       => [
                '/style.css'
            ]
        ],
        'css'  => [
            'validator' => '/validator',
            'selector'  => '#congrats',
            'resulttag' => '#results_container',
            'field'     => 'file',
            'css'       => [
                '/style/base.css',
                '/style/results.css'
            ]
        ]
    ];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $resultrootdir;

    /**
     * @param string $resultrootdir
     * @param string $urlHtmlValidator
     * @param string $urlCssValidator
     */
    public function __construct(
        $resultrootdir,
        $urlHtmlValidator = "https://validator.w3.org/nu",
        $urlCssValidator = "http://jigsaw.w3.org/css-validator"
    ) {
        $this->fs            = new Filesystem();
        $this->resultrootdir = $resultrootdir;

        $this->types['html']['w3curl'] = $urlHtmlValidator;
        $this->types['css']['w3curl']  = $urlCssValidator;

        if (!($this->fs->exists($resultrootdir))) {
            $this->fs->mkdir($resultrootdir);
        }
    }

    /**
     * @param string $w3curl
     * @param string $validator
     * @param string $selector
     * @param string $field
     * @param string $htmltag
     * @param string $file
     * @param string $title
     * @param array  $csslist
     *
     * @return string
     */
    private function sendToW3C($w3curl, $validator, $selector, $field, $htmltag, $file, $title, $csslist)
    {
        $client = new Client();

        $retry    = self::MAX_RETRY;
        $response = null;
        while ($retry--) {
            $response = $client->post($w3curl . $validator,
                [
                    'exceptions' => false,
                    'verify'     => false,
                    'multipart'  => [['name' => $field, 'contents' => fopen($file, 'r')]]
                ]
            );
            if ($response->getStatusCode() == 200) {
                break;
            }
        }

        $html = $response->getBody()->getContents();

        $html = new GlHtml($html);

        if ($html->get($selector)) {
            return null;
        }

        $html->delete('head style');
        $style = '<style type="text/css" media="all">';
        foreach ($csslist as $css) {
            $style .= '@import url("' . $w3curl . $css . '");';
        }
        $style .= '</style>';
        $html->get("head")[0]->add($style);


        $stats = $html->get("p.stats");
        if (isset($stats) && count($stats) > 0) {
            $stats[0]->delete();
        }

        $head = $html->get("head")[0]->getHtml();

        $resulttag = $html->get($htmltag);
        if (count($resulttag) <= 0) {
            $result = '<p class="failure">There were errors.</p>';
        } else {
            $result = $resulttag[0]->getHtml();
        }

        $view = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">' .
            $head . "</head><body><h2>$title</h2>" .
            $result . "</body></html>";

        return $view;
    }

    /**
     * @param SplFileInfo $fileinfo
     *
     * @throws \Exception
     * @return string
     */
    private function validateFile(SplFileInfo $fileinfo)
    {
        $ext   = $fileinfo->getExtension();
        $title = strtr($fileinfo->getRelativePathname(), ["\\" => "/"]);
        $view  = $this->sendToW3C(
                      $this->types[$ext]['w3curl'],
                          $this->types[$ext]['validator'],
                          $this->types[$ext]['selector'],
                          $this->types[$ext]['field'],
                          $this->types[$ext]['resulttag'],
                          strtr($fileinfo->getRealPath(), ["\\" => "/"]),
                          $title,
                          $this->types[$ext]['css']
        );

        if ($view === null) {
            return null;
        }

        $filedir = $this->resultrootdir . '/' . strtr($fileinfo->getRelativepath(), ["\\" => "/"]);
        if (!$this->fs->exists($filedir)) {
            $this->fs->mkdir($filedir);
        }
        $resultname = $filedir . "/w3c_" . $ext . "_" . $fileinfo->getBaseName($ext) . 'html';
        file_put_contents($resultname, $view);

        return $resultname;
    }

    /**
     * @param array    $files
     * @param array    $types
     * @param callable $callback
     *
     * @throws \Exception
     * @return array
     */
    public function validate(array $files, array $types, callable $callback)
    {
        $filter = '/\.(' . implode('|', $types) . ')$/';

        $results = [];
        foreach ($files as $file) {
            if ($file instanceof Finder) {
                $this->validateFinder($file, $filter, $callback, $results);
            } else {
                if (is_string($file)) {
                    $this->validateDirect($file, $filter, $callback, $results);
                } else {
                    throw new \Exception('Must be a string or a finder');
                }
            }
        }

        return $results;
    }


    /**
     * @param Finder   $files
     * @param string   $filter
     * @param callable $callback
     * @param array    $result
     */
    private function validateFinder(Finder $files, $filter, callable $callback, array &$result)
    {
        $files->name($filter);
        /**
         * @var SplFileInfo $file
         */
        foreach ($files as $file) {
            $callback($file);
            $result[strtr($file->getRelativePath() . '/' . $file->getFilename(), ["\\" => "/"])] = $this->validateFile(
                                                                                                        $file
            );
        }
    }

    /**
     * @param string   $file
     * @param string   $filter
     * @param callable $callback
     * @param array    $result
     */
    private function validateDirect($file, $filter, callable $callback, array &$result)
    {
        if (is_dir($file)) {
            $finder = new Finder();
            $finder->files()->in($file)->name($filter);
            /**
             * @var SplFileInfo $finderfile
             */
            foreach ($finder as $finderfile) {
                $callback($finderfile);
                $result[strtr(
                    $finderfile->getRelativePath() . '/' . $finderfile->getFilename(),
                    ["\\" => "/"]
                )] = $this->validateFile($finderfile);
            }
        } else {
            if (preg_match($filter, $file)) {
                $finderfile = new SplFileInfo($file, "", "");
                $callback($finderfile);
                $result[strtr(
                    $finderfile->getRelativePath() . '/' . $finderfile->getFilename(),
                    ["\\" => "/"]
                )] = $this->validateFile($finderfile);
            }
        }
    }
}
