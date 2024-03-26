<?php
    function updateTmplvar(mysqli $mysqli, string $newTable, string $oldTable, array $parentId): bool|mysqli_result
    {
        $str = implode(', ', $parentId);
        return mysqli_query($mysqli, "REPLACE INTO `$newTable` (`tmplvar_id`, `content_id`, `value`) SELECT `n.tmplvar_id`, `n.content_id`, `n.val` FROM `$oldTable` `n` WHERE `n.content_id` IN ($str)");
    }
