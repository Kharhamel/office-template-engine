<?php

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
