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
 * @link      http://www.glicer.com
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
use GuzzleHttp\Post\PostFile;
use GlHtml\GlHtml;

/**
 * Class GlW3CValidator
 * @package GlValidator
 */
class GlW3CValidator
{
    private $types = [
        'html' => [
            'w3curl'    => "http://validator.w3.org/check",
            'resulttag' => '#result',
            'field'     => 'uploaded_file',
            'css'       => [
                'url("http://validator.w3.org/style/base")',
                'url("http://validator.w3.org/style/results")'
            ]
        ],
        'css'  => [
            'w3curl'    => "http://jigsaw.w3.org/css-validator/validator",
            'resulttag' => '#results_container',
            'field'     => 'file',
            'css'       => [
                'url("http://jigsaw.w3.org/css-validator/style/base.css")',
                'url("http://jigsaw.w3.org/css-validator/style/results.css")'
            ]
        ]
    ];

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    private $fs;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var string
     */
    private $resultrootdir;

    public function __construct($resultrootdir)
    {
        $this->fs            = new Filesystem();
        $this->client        = new Client();
        $this->resultrootdir = $resultrootdir;

        $fs = new Filesystem();
        $fs->remove([$resultrootdir]);
        if (!is_dir($resultrootdir)) {
            $fs->mkdir($resultrootdir);
        }
    }

    /**
     * @param string $w3curl
     * @param string $field
     * @param string $htmltag
     * @param string $file
     * @param string $title
     * @param array  $csslist
     *
     * @return string
     */
    private function sendToW3C($w3curl, $field, $htmltag, $file, $title, $csslist)
    {
        $request  = $this->client->createRequest('POST', $w3curl);
        $postBody = $request->getBody();
        $postBody->addFile(
                 new PostFile($field, fopen(
                     $file,
                     'r'
                 ))
        );
        $response = $this->client->send($request);
        $html     = $response->getBody()->getContents();

        $html = new GlHtml($html);

        $html->delete('head style');
        $style = '<style type="text/css" media="all">';
        foreach ($csslist as $css) {
            $style .= '@import ' . $css . ';';
        }
        $style .= '</style>';
        $html->get("head")[0]->add($style);

        $head   = $html->get("head")[0]->getHtml();
        $result = $html->get($htmltag)[0]->getHtml();

        $view = "<!DOCTYPE html><html><head>" .
            $head . "</head><body><h2>$title</h2>" .
            $result . "</body></html>";

        return $view;
    }

    /**
     * @param SplFileInfo $fileinfo
     *
     * @return string
     */
    private function validateFile(SplFileInfo $fileinfo)
    {
        $ext = $fileinfo->getExtension();;
        $view = $this->sendToW3C(
                     $this->types[$ext]['w3curl'],
                         $this->types[$ext]['field'],
                         $this->types[$ext]['resulttag'],
                         $fileinfo->getRealpath(),
                         $fileinfo->getRelativePathname(),
                         $this->types[$ext]['css']
        );

        $filedir = $this->resultrootdir . '/' . $fileinfo->getRelativepath();
        if (!is_dir($filedir)) {
            $this->fs->mkdir($filedir);
        }
        $resultname = $filedir . "/w3c_" . $ext . "_" . $fileinfo->getBaseName($ext) . 'html';
        $list[]     = $resultname;
        file_put_contents($resultname, $view);

        return $resultname;
    }

    /**
     * @param array    $files
     * @param callable $callback
     *
     * @return array
     */
    public function validate($files, callable $callback)
    {
        $result = [];
        foreach ($files as $fileelement) {
            if (is_dir($fileelement)) {
                $finder = new Finder();
                $finder->files()->in($fileelement)->name('/\.(css|html)$/');
                /**
                 * @var SplFileInfo $finderfile
                 */
                foreach ($finder as $finderfile) {
                    $callback($finderfile->getRealPath());
                    $result[] = $this->validateFile($finderfile);
                }
            } else {
                $callback($fileelement);
                $finderfile = new SplFileInfo($fileelement, "", "");
                $result[]   = $this->validateFile($finderfile);
            }
        }

        return $result;
    }
} 