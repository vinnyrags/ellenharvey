<?php
/**
 * The base configuration for WordPress
 */

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 * Generated unique to this project; rotate on hand-off.
 */
define('AUTH_KEY',         '%z@L{F[KDt)JIT.T~K7L+uBVr2!NX_hQ}NU2.vjl{$;i9w#F))YW,5vlr.+*qFXG');
define('SECURE_AUTH_KEY',  '4MsB0 EZYY*+yw(FNR3|z;n6Ryf/ivcM[}-1+Mg+/0C!{|z<VTes>JQR0{%}HiA[');
define('LOGGED_IN_KEY',    '$0hYf+e+;+Bk2~xasCyB9ht*<-ipt,cnchqT[/sF3hn^-}Nv|Iw9=ox-9N21[#?{');
define('NONCE_KEY',        '2u-h,Wg_dr3m-3DrIUJ^38dVd/)-+C/9^sqV;W?Wi`,*Wzjs}8/w*}HmFH_+8(|T');
define('AUTH_SALT',        '7o6u2-L8o52^t]t{Jvg(7uN{!y-exsJuHZ7[0nx@PZN!.a$:RLMg|`?y&+*$3^J}');
define('SECURE_AUTH_SALT', 'At*Zc/,kWc}JECbYP$vZN{ crrC44C|JsH;Q7)!1.:-mLe+n;9(3msK)shSjgQKF');
define('LOGGED_IN_SALT',   'Mj->lRZs^6|l0T:k^m_fEBUts EK33D7zn_|1)K~Z6b6|Z,ao?IfFW$4;{A1t%Dc');
define('NONCE_SALT',       'gYqy&d&_|J+5tuEZh.YBDfVe+*v~cMC4>*~NLkmx(F]9-xu&#%yuS`nm_&oh^3vb');
/**#@-*/

/**
 * WordPress database table prefix.
 */
$table_prefix = 'wp_';

// =====================================================================
// Environment-specific settings (loaded from wp-config-env.php if present)
// =====================================================================
$env_settings = __DIR__ . '/wp-config-env.php';
if ( is_readable( $env_settings ) ) {
    require_once $env_settings;
}

// =====================================================================
// Custom Directory Structure Settings
// =====================================================================
if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
    define( 'WP_ENVIRONMENT_TYPE', 'development' );
}

if ( ! defined( 'WP_HOME' ) ) {
    define( 'WP_HOME', 'https://ellenharvey.ddev.site' );
}

if ( ! defined( 'WP_SITEURL' ) ) {
    define( 'WP_SITEURL', WP_HOME . '/wp' );
}

define( 'WP_CONTENT_DIR', dirname(__FILE__) . '/wp-content' );
define( 'WP_CONTENT_URL', WP_HOME . '/wp-content' );

// =====================================================================
// Debugging Settings
// =====================================================================
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', false );
}
if ( ! defined( 'WP_DEBUG_LOG' ) ) {
    define( 'WP_DEBUG_LOG', false );
}
if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
    define( 'WP_DEBUG_DISPLAY', false );
}
@ini_set( 'display_errors', 0 );

// Lock down WP admin file editor and plugin/theme installer.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}
if ( ! defined( 'DISALLOW_FILE_MODS' ) ) {
    define( 'DISALLOW_FILE_MODS', true );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/wp/' );
}

// Include for settings managed by ddev.
$ddev_settings = __DIR__ . '/wp-config-ddev.php';
if ( ! defined( 'DB_USER' ) && getenv( 'IS_DDEV_PROJECT' ) == 'true' && is_readable( $ddev_settings ) ) {
    require_once( $ddev_settings );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
