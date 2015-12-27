<?php

/**
 * {SHORT_DESCRIPTION}
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   Contact
 * @author    Emmanuel ROECKER <emmanuel.roecker@gmail.com>
 * @author    Rym BOUCHAGOUR <rym.bouchagour@free.fr>
 * @copyright 2012-2013 GLICER
 * @license   Proprietary property of GLICER
 * @link      http://www.glicer.com
 *
 * Created : 27/12/15
 * File : test.php
 *
 */

$src = file_get_contents(
    "/home/travis/build/emmanuelroecker/php-w3cvalidator/tests/expected/test1/w3c_html_index.html"
);
$dst = file_get_contents("/home/travis/build/emmanuelroecker/php-w3cvalidator/tests/result/test1/w3c_html_index.html");

echo strcmp($src, $dst);


