<?php

//  *shh*  exchange the url to more readable string and remove special characters
function clean_url($str)
{
    // *shh* convert the arabic and persian numbers in strings, to english integers
    $str = FunctionsHelper::convert_to_english($str);

    return str_replace(['٫', '+', '٪', 'ـ', ' ', ':', '\'', '"', ',', ';', '<', '>', '`', '&', '?', '(', ')', '$', '-', '_', '*', '/', '\\', '.', '@', '=', '[', ']', '|', '~', '^', ';', '#', '%', '!', '{', '}', '٬', 'ِ', 'ْ', 'ّ', 'ُ', 'ً', 'َ', '»', '«', '،', '؛', '؟', 'هٔ', 'ي', 'أ', 'ؤ', 'ئ', 'ء', '،', '‌', '-',':'], ' ', $str);
}


