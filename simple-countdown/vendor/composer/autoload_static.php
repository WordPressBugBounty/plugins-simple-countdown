<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit31847a6ee30e855ba3e2939c411046f0
{
    public static $files = array (
        'f227d3a8dbab83bf9dd2cef7e4f7f6f7' => __DIR__ . '/../..' . '/includes/Cpts/CptsBase/cptsList.php',
        '29a2431db9e51934ac1d4e5040f7ae21' => __DIR__ . '/../..' . '/includes/Pages/PagesBase/pagesList.php',
        'd51b87259d596828a0aae2efc233b6e2' => __DIR__ . '/../..' . '/includes/Settings/Fields/countdown-timer-cpt-fields.php',
        'b5c1dc982be5c5d10c2a6102a158d35c' => __DIR__ . '/../..' . '/includes/Settings/Fields/quick-countdown-timer-fields.php',
    );

    public static $prefixLengthsPsr4 = array (
        'G' => 
        array (
            'GPLSCore\\GPLS_PLUGIN_WPSCTR\\' => 28,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\' => 
        array (
            0 => __DIR__ . '/../..' . '/includes',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Base' => __DIR__ . '/../..' . '/includes/Base.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Core\\Core' => __DIR__ . '/../..' . '/includes/Core/Core.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Pages\\PagesBase\\AdminPage' => __DIR__ . '/../..' . '/includes/Pages/PagesBase/AdminPage.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Pages\\QuickCountdownTimerPage' => __DIR__ . '/../..' . '/includes/Pages/QuickCountdownTimerPage.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Plugin' => __DIR__ . '/../..' . '/includes/Plugin.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\QuickCountDownTimer' => __DIR__ . '/../..' . '/includes/QuickCountDownTimer.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\CountDownTimerCPTSettings' => __DIR__ . '/../..' . '/includes/Settings/CountDownTimerCPTSettings.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\QuickCountDownTimerSettings' => __DIR__ . '/../..' . '/includes/Settings/QuickCountDownTimerSettings.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsBase\\Settings' => __DIR__ . '/../..' . '/includes/Settings/SettingsBase/Settings.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsBase\\SettingsFormTrait' => __DIR__ . '/../..' . '/includes/Settings/SettingsBase/SettingsFormTrait.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsBase\\SettingsSubmitTrait' => __DIR__ . '/../..' . '/includes/Settings/SettingsBase/SettingsSubmitTrait.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsBase\\SettingsUtilsTrait' => __DIR__ . '/../..' . '/includes/Settings/SettingsBase/SettingsUtilsTrait.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\CheckboxField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/CheckboxField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\EmailField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/EmailField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\Field' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/Field.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\FieldBase' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/FieldBase.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\RadioField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/RadioField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\RepeaterField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/RepeaterField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\SelectField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/SelectField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\TextAreaField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/TextAreaField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\TextField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/TextField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Settings\\SettingsFields\\UrlField' => __DIR__ . '/../..' . '/includes/Settings/SettingsFields/UrlField.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Utils\\GeneralUtilsTrait' => __DIR__ . '/../..' . '/includes/Utils/GeneralUtilsTrait.php',
        'GPLSCore\\GPLS_PLUGIN_WPSCTR\\Utils\\NoticeUtilsTrait' => __DIR__ . '/../..' . '/includes/Utils/NoticeUtilsTrait.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit31847a6ee30e855ba3e2939c411046f0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit31847a6ee30e855ba3e2939c411046f0::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit31847a6ee30e855ba3e2939c411046f0::$classMap;

        }, null, ClassLoader::class);
    }
}
