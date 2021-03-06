<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit397d77a0075806ef5fb38baa047a2ed8
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'ReCaptcha\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ReCaptcha\\' => 
        array (
            0 => __DIR__ . '/..' . '/google/recaptcha/src/ReCaptcha',
        ),
    );

    public static $prefixesPsr0 = array (
        'N' => 
        array (
            'NlpTools\\' => 
            array (
                0 => __DIR__ . '/..' . '/nlp-tools/nlp-tools/src',
            ),
        ),
        'F' => 
        array (
            'ForceUTF8\\' => 
            array (
                0 => __DIR__ . '/..' . '/neitanod/forceutf8/src',
            ),
        ),
    );

    public static $classMap = array (
        'Eventviva\\ImageResize' => __DIR__ . '/..' . '/eventviva/php-image-resize/lib/ImageResize.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit397d77a0075806ef5fb38baa047a2ed8::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit397d77a0075806ef5fb38baa047a2ed8::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit397d77a0075806ef5fb38baa047a2ed8::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit397d77a0075806ef5fb38baa047a2ed8::$classMap;

        }, null, ClassLoader::class);
    }
}
