<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitcba65b7e929df318caf5e22ab3541b95
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitcba65b7e929df318caf5e22ab3541b95::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitcba65b7e929df318caf5e22ab3541b95::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitcba65b7e929df318caf5e22ab3541b95::$classMap;

        }, null, ClassLoader::class);
    }
}
