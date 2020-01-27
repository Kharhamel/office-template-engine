<?php


namespace OfficeTemplateEngine\lib\FileHelpers;

class PathFinder
{
    /**
     * Return the path of file $FullPath relatively to the path of file $RelativeTo.
     * For example:
     * 'dir1/dir2/file_a.xml' relatively to 'dir1/dir2/file_b.xml' is 'file_a.xml'
     * 'dir1/file_a.xml' relatively to 'dir1/dir2/file_b.xml' is '../file_a.xml'
     */
    public static function getRelativePath(string $FullPath, string $RelativeTo): string
    {

        $fp = explode('/', $FullPath);
        $fp_file = array_pop($fp);
        $fp_max = count($fp)-1;

        $rt = explode('/', $RelativeTo);
        $rt_file = array_pop($rt);
        $rt_max = count($rt)-1;

        // First different item
        $min = min($fp_max, $rt_max);
        while (($min>=0) && ($fp[0]==$rt[0])) {
            $min--;
            array_shift($fp);
            array_shift($rt);
        }

        $path  = str_repeat('../', count($rt));
        $path .= implode('/', $fp);
        if (count($fp)>0) {
            $path .= '/';
        }
        $path .= $fp_file;

        return $path;
    }

    /**
     * Return the absolute path of file $RelativePath which is relative to the full path $RelativeTo.
     * For example:
     * '../file_a.xml' relatively to 'dir1/dir2/file_b.xml' is 'dir1/file_a.xml'
     */
    public static function getAbsolutePath(string $RelativePath, string $RelativeTo): string
    {

        // May be reltaive to the root
        if (substr($RelativePath, 0, 1) == '/') {
            return substr($RelativePath, 1);
        }

        $rp = explode('/', $RelativePath);
        $rt = explode('/', $RelativeTo);

        // Get off the file name;
        array_pop($rt);

        while ($rp[0] == '..') {
            array_pop($rt);
            array_shift($rp);
        }

        while ($rp[0] == '.') {
            array_shift($rp);
        }

        $path = array_merge($rt, $rp);
        $path = implode('/', $path);

        return $path;
    }

    /**
     * Return the path of the Rel file in the archive for a given XML document.
     * @param string $DocPath      Full path of the sub-file in the archive
     */
    public static function relsGetPath(string $DocPath): string
    {
        $DocName = basename($DocPath);
        return str_replace($DocName, '_rels/'.$DocName.'.rels', $DocPath);
    }
}
