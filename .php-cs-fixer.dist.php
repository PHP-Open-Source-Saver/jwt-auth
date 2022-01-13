<?php

declare(strict_types=1);

use PhpCsFixer\Config;

require __DIR__ . '/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in([
             __DIR__ . '/src',
             __DIR__ . '/tests',
         ]);

return (new Config())
    ->setFinder($finder)
    ->setRules([
                   '@Symfony' => true,
                   'header_comment' => [
                       'comment_type' => 'comment',
                       'header' => <<<HEREDOC
This file is part of jwt-auth.

(c) 2014-2021 Sean Tymon <tymon148@gmail.com>
(c) 2021 PHP Open Source Saver

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEREDOC,
                       'location' => 'after_declare_strict',

                   ]
               ]);
