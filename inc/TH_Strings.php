<?php

if ( !class_exists('TH_Strings' ) ) {
    class TH_Strings
    {
        public static function snake_to_proper_case($str)
        {
            $str = explode('_', $str);
            return ucfirst($str[0]) . ' ' . ucfirst($str[1]);
        }
    }
}