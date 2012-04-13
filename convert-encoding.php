<?php

// +----------------------------------------------------------------------+
// | Convert wordpress content encoding                                   |
// +----------------------------------------------------------------------+
// | Copyright (C) 2005 Markus Tacker <m@tacker.org>                      |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the                |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA               |
// +----------------------------------------------------------------------+

    /**
    * Converts wordpress encodings
    *
    * IMPORTANT!
    * Remember to dumpy your database first
    *
    * mysqldump --opt DB_NAME
    *
    * Or use phpMyAdmin:
    *  http://www.zerokspot.com/docs/howto.phpmyadmin.backup_and_restore/
    *  http://www.phpmyadmin.net/documentation/#faq6_3
    *
    * Do NOT run this script more then once
    *
    * Place this script inside the wp-content folder of your blog and
    * access it via the browser, e.g. http://yoursite.com/blog/wp-content/convert-encoding.php
    *
    * Remember to remove it afterwards.
    *
    * @author Markus Tacker <m@tacker.org>
    * @link http://m.tacker.org/blog/64.script-to-convert-wordpress-content-encoding.html
    */

    // You have to remove the line below to make this script work
    // By removing the line you accept the license agreement stated above
    die('Please follow the instructions in ' . $_SERVER['PHP_SELF']);

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    /**
    * @var string Target encoding
    */
    $new_encoding = 'UTF-8';

    /**
    * @var string New collation for the table
    */
    $new_mysql_collation = 'utf8_general_ci';

    /**
    * @var string New character set for the table
    */
    $new_mysql_encoding = 'utf8';

    /**
    * You would set this switch to true if you have used this script before
    * and Your titles contain invalid non-ascii charactes like "fÃ¼r"
    * Your database entries are utf-8 encoded and need to be decoded, not converted.
    *
    * @var bool UTF-8 decode your content instead
    */
    $decode = false;

    require_once '../wp-config.php';

    // Connect
    $DBC = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
    mysql_select_db(DB_NAME, $DBC);

    $result = mysql_query('SHOW TABLES FROM ' . DB_NAME, $DBC);

    echo '<pre>';
    echo 'Mode is <strong>' . (($decode) ? 'decode' : 'convert') . "</strong>\n";
    echo 'New encoding is <strong>' . $new_encoding . "</strong>\n";
    echo 'New encoding for mysql is <strong>' . $new_mysql_encoding . "</strong>\n";
    echo 'New collation for mysql is <strong>' . $new_mysql_collation . "</strong>\n";
    while ($row = mysql_fetch_row($result)) {
        // Skip Non-WP tables
        if (!empty($table_prefix) && substr($row[0], 0, strlen($table_prefix)) != $table_prefix ) continue;
        // Convert the tables encoding
        $sql = 'ALTER TABLE ' . ek($row[0]) . ' DEFAULT CHARACTER SET ' .  $new_mysql_encoding . ' COLLATE ' . $new_mysql_collation;
        mysql_query($sql);
        // Convert the fields
        $sql = 'SHOW CREATE TABLE ' . ek($row[0]);
        $result2 = mysql_query($sql);
        $table_sql = mysql_fetch_assoc($result2);
        foreach (explode("\n", $table_sql['Create Table']) as $line) {
            $line = trim($line);
            if (!preg_match('/ character set ([^ ]+)/', $line, $match_cs)) continue;
            $line = preg_replace('/collate [0-9a-z_]+/i', '', $line); // Remove old collate
            preg_match('/^`[^`]+`/', $line, $match_field);
            $sql = 'ALTER TABLE ' . ek($row[0])
            . ' CHANGE ' . $match_field[0] . ' '
            . str_replace($match_cs[0], ' character set ' . $new_mysql_encoding . ' COLLATE ' .  $new_mysql_collation, substr($line, 0, -1));
            mysql_query($sql);
        }
        // Convert its data
        $result_data = mysql_query('SELECT * FROM ' . $row[0]);
        echo $row[0] . ' ';
        while ($data = mysql_fetch_assoc($result_data)) {
            $sql = 'UPDATE ' . $row[0];
            // Build set
            $set = array();
            foreach ($data as $key => $val) {
                if ($decode) {
                    $set[] = ek($key) . '=' . ev(utf8_decode($data[$key]));
                } else {
                    $set[] = ek($key) . '=' . ev(mb_convert_encoding($data[$key], $new_encoding));
                }
            }
            $sql .= ' SET ' . join(', ', $set);
            // Build where
            $where = array();
            foreach ($data as $key => $val) {
                if (!preg_match('/^[0-9]+$/', $val)) continue; // Use only numbers in where
                $where[] = ek($key) . '=' . ev($data[$key]);
            }
            if (empty($where)) continue 2; // Table has no numeric fields, skip it
            $sql .= ' WHERE ' . join(' AND ', $where);
            $query_result = mysql_query($sql, $DBC);
            if (!$query_result) {
                die( 'Query failed: ' . $sql . ' (' . mysql_error() . ')' );
            }
            echo '.';
            flush();
        }
        echo "\n";
    }
    echo "All done.\n";
    echo '<span style="color: #ff0000;">Remember to remove this script!</span>' . "\n";
    echo '</pre>';

    // Disconnect
    mysql_close($DBC);

    function ek($string)
    {
        global $DBC;
        return "`" . mysql_real_escape_string($string, $DBC) . "`";
    }

    function ev($string)
    {
        global $DBC;
        return "'" . mysql_real_escape_string($string, $DBC) . "'";
    }