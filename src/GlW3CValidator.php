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
    const MAX_RETRY     = 3;
    const WAITING_RETRY = 10;

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

        if (!($this->fs->exists($resultrootdir))) {
            $this->fs->mkdir($resultrootdir);
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
        $request  = $this->client->createRequest('POST', $w3curl, ['exceptions' => false]);
        $postBody = $request->getBody();
        $postBody->addFile(
                 new PostFile($field, fopen(
                     $file,
                     'r'
                 ))
        );

        $retry    = self::MAX_RETRY;
        $response = null;
        while ($retry--) {
            $response = $this->client->send($request);
            if ($response->getStatusCode() == 200) {
                break;
            }
            sleep(self::WAITING_RETRY);
        }

        $html = $response->getBody()->getContents();

        //echo $html;

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
     * @throws \Exception
     * @return string
     */
    private function validateFile(SplFileInfo $fileinfo)
    {
        $title   = $fileinfo->getRelativePathname();
        $filedir = $this->resultrootdir . '/' . $fileinfo->getRelativepath();

        $ext = $fileinfo->getExtension();;
        $view = $this->sendToW3C(
                     $this->types[$ext]['w3curl'],
                         $this->types[$ext]['field'],
                         $this->types[$ext]['resulttag'],
                         $fileinfo->getRealPath(),
                         $title,
                         $this->types[$ext]['css']
        );

        if (!$this->fs->exists($filedir)) {
            $this->fs->mkdir($filedir);
        }
        $resultname = $filedir . "/w3c_" . $ext . "_" . $fileinfo->getBaseName($ext) . 'html';
        $list[]     = $resultname;
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
            $result[] = $this->validateFile($file);
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
                $result[] = $this->validateFile($finderfile);
            }
        } else {
            if (preg_match($filter, $file)) {
                $finderfile = new SplFileInfo($file, "", "");
                $callback($finderfile);
                $result[] = $this->validateFile($finderfile);
            }
        }
    }
} 