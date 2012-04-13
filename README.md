# Script to convert WordPress' content encoding

This script converts the encoding of all database entries of wordpress into another encoding.
In most cases you would convert into UTF-8.

## IMPORTANT!

Remember to dumpy your database first

`mysqldump --opt DB_NAME`

Or use phpMyAdmin:

 * <http://www.zerokspot.com/docs/howto.phpmyadmin.backup_and_restore/>
 * <http://www.phpmyadmin.net/documentation/#faq6_3>

Do NOT run this script more then once

## Instructions

Place this script inside the wp-content folder of your blog and access it via the browser, e.g. `http://yoursite.com/blog/wp-content/convert-encoding.php`

