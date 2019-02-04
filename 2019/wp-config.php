<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'a6351_r5zs1');

/** MySQL database username */
define('DB_USER', 'a6351_r5zs1');

/** MySQL database password */
define('DB_PASSWORD', 'J&7ZFeeyemNIAgxb10&63');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'ttZJQHDlHsXxXypKsV8m3XdT6T6xIBThhXZXvztsyqJqFJ6ubxmstx0mr9eYOhtd');
define('SECURE_AUTH_KEY',  '1jg5YMxZGSA0IR1QOpPTjQpdwMc4gNEEAX4XJFKdJczzhnBSVNaVomg98bmHNm7U');
define('LOGGED_IN_KEY',    'Txn3XHzRZIo2VXa8CUIbOLo8cOTKuClUL5yMs76rWYJVjbw6RHZbaInSKhh6sevs');
define('NONCE_KEY',        'QKpp9fgEJ8zpScRUxNoVmU1wSXtguFGfSg1s2Uz3Q6g3R082BsB9cVGNQxRQQYeZ');
define('AUTH_SALT',        'Mh5zeJ0HttEkj9EW5xnEDuuRmqcd1G0xJe9vAnCmfMhxDmaRkC9M5grZlL8s7J8Z');
define('SECURE_AUTH_SALT', '8wL8tuMUq6D2vWXHXjzOuhgZNivT7rX9zykBkTbYdiv5AihdE2PnaOilkt9kfIWF');
define('LOGGED_IN_SALT',   'PL7iDYofQZHSSRZbux7PzpTnruiVv9ca6nk4gOIAEzsbUg9Os8vC8njP2BuBg4ZU');
define('NONCE_SALT',       'fwajefYFCeVixFNPoMOUmgMmbdSQJwICoZfzSamMfnBvs2gKWAQihSu2zUg5SVMg');

/**
 * Other customizations.
 */
define('FS_METHOD','direct');define('FS_CHMOD_DIR',0755);define('FS_CHMOD_FILE',0644);
define('WP_TEMP_DIR',dirname(__FILE__).'/wp-content/uploads');

/**
 * Turn off automatic updates since these are managed upstream.
 */
define('AUTOMATIC_UPDATER_DISABLED', true);


/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'pbfk_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
