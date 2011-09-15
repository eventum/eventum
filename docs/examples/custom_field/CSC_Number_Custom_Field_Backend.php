<?php

class CSC_Number_Custom_Field_Backend
{
    function formatValue($value, $fld_id, $iss_id)
    {
        if (!empty($value)) {
            $numbers = explode(',', $value);
            $links = array();
            foreach ($numbers as $number) {
                $links[] = "<a href=\"https://example.com/view.php?id=$number\" target=\"csc_$number\" class=\"link\">$number</a>";
            }
            return join(", ", $links);
        } else {
            return $value;
        }
    }
}
