<?php

/**
 * Constants to drive the plugin.
 */
define('OPENTBS_DOWNLOAD', 1);   // download (default) = TBS_OUTPUT
define('OPENTBS_NOHEADER', 4);   // option to use with DOWNLOAD: no header is sent
define('OPENTBS_FILE', 8);       // output to file   = TBSZIP_FILE
define('OPENTBS_DEBUG_XML', 16); // display the result of the current subfile
define('OPENTBS_STRING', 32);    // output to string = TBSZIP_STRING
define('OPENTBS_DEBUG_AVOIDAUTOFIELDS', 64); // avoit auto field merging during the Show() method
define('OPENTBS_INFO', 'clsOpenTBS.Info');       // command to display the archive info
define('OPENTBS_RESET', 'clsOpenTBS.Reset');      // command to reset the changes in the current archive
define('OPENTBS_ADDFILE', 'clsOpenTBS.AddFile');    // command to add a new file in the archive
define('OPENTBS_DELETEFILE', 'clsOpenTBS.DeleteFile'); // command to delete a file in the archive
define('OPENTBS_REPLACEFILE', 'clsOpenTBS.ReplaceFile'); // command to replace a file in the archive
define('OPENTBS_EDIT_ENTITY', 'clsOpenTBS.EditEntity'); // command to force an attribute
define('OPENTBS_FILEEXISTS', 'clsOpenTBS.FileExists');
define('OPENTBS_CHART', 'clsOpenTBS.Chart');
define('OPENTBS_CHART_INFO', 'clsOpenTBS.ChartInfo');
define('OPENTBS_DEFAULT', '');   // Charset
define('OPENTBS_ALREADY_XML', false);
define('OPENTBS_ALREADY_UTF8', 'already_utf8');
define('OPENTBS_DEBUG_XML_SHOW', 'clsOpenTBS.DebugXmlShow');
define('OPENTBS_DEBUG_XML_CURRENT', 'clsOpenTBS.DebugXmlCurrent');
define('OPENTBS_DEBUG_INFO', 'clsOpenTBS.DebugInfo');
define('OPENTBS_DEBUG_CHART_LIST', 'clsOpenTBS.DebugInfo'); // deprecated
define('OPENTBS_FORCE_DOCTYPE', 'clsOpenTBS.ForceDocType');
define('OPENTBS_DELETE_ELEMENTS', 'clsOpenTBS.DeleteElements');
define('OPENTBS_SELECT_SHEET', 'clsOpenTBS.SelectSheet');
define('OPENTBS_SELECT_SLIDE', 'clsOpenTBS.SelectSlide');
define('OPENTBS_SELECT_MAIN', 'clsOpenTBS.SelectMain');
define('OPENTBS_DISPLAY_SHEETS', 'clsOpenTBS.DisplaySheets');
define('OPENTBS_DELETE_SHEETS', 'clsOpenTBS.DeleteSheets');
define('OPENTBS_DELETE_COMMENTS', 'clsOpenTBS.DeleteComments');
define('OPENTBS_MERGE_SPECIAL_ITEMS', 'clsOpenTBS.MergeSpecialItems');
define('OPENTBS_CHANGE_PICTURE', 'clsOpenTBS.ChangePicture');
define('OPENTBS_COUNT_SLIDES', 'clsOpenTBS.CountSlides');
define('OPENTBS_COUNT_SHEETS', 'clsOpenTBS.CountSheets');
define('OPENTBS_SEARCH_IN_SLIDES', 'clsOpenTBS.SearchInSlides');
define('OPENTBS_DISPLAY_SLIDES', 'clsOpenTBS.DisplaySlides');
define('OPENTBS_DELETE_SLIDES', 'clsOpenTBS.DeleteSlides');
define('OPENTBS_SELECT_FILE', 'clsOpenTBS.SelectFile');
define('OPENTBS_ADD_CREDIT', 'clsOpenTBS.AddCredit');
define('OPENTBS_SYSTEM_CREDIT', 'clsOpenTBS.SystemCredit');
define('OPENTBS_RELATIVE_CELLS', 'clsOpenTBS.RelativeCells');
define('OPENTBS_FIRST', 1); //
define('OPENTBS_GO', 2);    // = TBS_GO
define('OPENTBS_ALL', 4);   // = TBS_ALL
// Types of file to select
define('OPENTBS_GET_HEADERS_FOOTERS', 'clsOpenTBS.SelectHeaderFooter');
define('OPENTBS_SELECT_HEADER', 'clsOpenTBS.SelectHeader');
define('OPENTBS_SELECT_FOOTER', 'clsOpenTBS.SelectFooter');
// Sub-types of file
define('OPENTBS_EVEN', 128);


function CheckArgList(string $Str): array
{
    $ArgLst = [];
    if ($Str[strlen($Str) - 1] ===')') {
        $pos = strpos($Str, '(');
        if ($pos!==false) {
            $ArgLst = explode(',', substr($Str, $pos+1, strlen($Str)-$pos-2));
            $Str = substr($Str, 0, $pos);
        }
    }
    return [$Str, $ArgLst];
}
