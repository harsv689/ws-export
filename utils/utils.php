<?php
/**
* @author Thomas Pellissier Tanon
* @copyright 2011 Thomas Pellissier Tanon
* @licence http://www.gnu.org/licenses/gpl.html GNU General Public Licence
*/

/**
* return an uuid
* @var $prefix a prefix for the uuid
*/
function uuid($prefix = '') {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid  = substr($chars,0,8) . '-';
        $uuid .= substr($chars,8,4) . '-';
        $uuid .= substr($chars,12,4) . '-';
        $uuid .= substr($chars,16,4) . '-';
        $uuid .= substr($chars,20,12);
        return $prefix . $uuid;
}

/**
* return an url to a page of Wikisource
* @var $lang the language of the wiki
* @var $lang the name of the page
*/
function wikisourceUrl($lang, $page = '') {
        if($page != '')
                return 'http://' . $lang . '.wikisource.org/wiki/' . urlencode($page);
        else
                return 'http://' . $lang . '.wikisource.org';
}

/**
* return the content of a file
* @var $file the path to the file
*/
function getFile($file) {
        $content = '';
        if ($fp = fopen($file, 'r')) {
                while(!feof($fp)) {
                        $content .= fgets($fp, 4096);
                }
        }
        return $content;
}

/**
* a mimetype from a file
* @var $file the file
*/
function getMimeType($file) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->buffer($file);
}

/**
* get an xhtml page from a text content
* @var $lang the code lang of the content
* @var $content
*/
function getXhtmlFromContent($lang, $content) {
        return '<?xml version="1.0" encoding="UTF-8" ?><!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $lang . '"><head><meta content="application/xhtml+xml;charset=UTF-8" http-equiv="content-type"/><title></title></head><body>' . $content . '</body></html>';
}
