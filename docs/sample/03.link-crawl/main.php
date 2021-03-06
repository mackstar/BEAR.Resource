<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use BEAR\Resource\ResourceObject;
use Ray\Di\Injector;

main: {
    $resource = require __DIR__ . '/scripts/instance.php';
    $result = $resource
              ->get
              ->uri('app://self/author')
              ->linkCrawl('tree')
              ->eager
              ->request();
    /* @var $result \BEAR\Resource|ResourceObject */
}

output: {
    var_export($result->body) . PHP_EOL;
}

//array (
//    0 =>
//    array (
//        'id' => 1,
//        'name' => 'Athos',
//        'post' =>
//        array (
//            0 =>
//            array (
//                'id' => '1',
//                'author_id' => '1',
//                'body' => 'Anna post #1',
//                'meta' =>
//                array (
//                    0 =>
//                    array (
//                        'id' => '1',
//                        'post_id' => '1',
//                        'data' => 'meta 1',
//                    ),
//                ),
//                'tag' =>
//                array (
//                    0 =>
//                    array (
//                        'id' => '1',
//                        'post_id' => '1',
//                        'tag_id' => '1',
//                        'tag_name' =>
//                        array (
//                            0 =>
//                            array (
//                                'id' => '1',
//                                'name' => 'zim',
//                            ),
//                        ),
//                    ),
//                    1 =>
//                    array (
//                        'id' => '2',
//                        'post_id' => '1',
//                        'tag_id' => '2',
//                        'tag_name' =>
//                        array (
//                            0 =>
//                            array (
//                                'id' => '2',
//                                'name' => 'dib',
//                            ),
//                        ),
//                    ),
//                ),
//            ),
// ...